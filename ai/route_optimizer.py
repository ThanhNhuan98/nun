import json
import sys
import io
import urllib.error
import urllib.parse
import urllib.request
try:
    from ortools.constraint_solver import pywrapcp, routing_enums_pb2
    OR_TOOLS_AVAILABLE = True
except ImportError:
    OR_TOOLS_AVAILABLE = False
from math import asin, cos, radians, sin, sqrt
from datetime import datetime


def haversine_distance(lat1, lon1, lat2, lon2):
    radius_km = 6371
    lat1, lon1, lat2, lon2 = map(radians, [float(lat1), float(lon1), float(lat2), float(lon2)])

    dlat = lat2 - lat1
    dlon = lon2 - lon1

    a = sin(dlat / 2) ** 2 + cos(lat1) * cos(lat2) * sin(dlon / 2) ** 2
    c = 2 * asin(sqrt(a))

    return radius_km * c

def traffic_meta(scheduled_time_str=""):
    # Mặc định lấy giờ hiện tại
    target_time = datetime.now()
    
    # Nếu có truyền thời gian hẹn từ PHP, đổi sang thời gian hẹn
    if scheduled_time_str:
        try:
            target_time = datetime.fromisoformat(scheduled_time_str)
        except ValueError:
            pass # Nếu lỗi parse ngày tháng, fallback về giờ hiện tại
            
    minutes = target_time.hour * 60 + target_time.minute

    if target_time.isoweekday() <= 5 and ((420 <= minutes <= 540) or (990 <= minutes <= 1140)):
        return 1.20, "Giờ cao điểm"
    if minutes >= 1260 or minutes <= 300:
        return 1.10, "Đêm muộn"
    if target_time.isoweekday() >= 6:
        return 1.05, "Cuối tuần"
    return 1.00, "Bình thường"

def osrm_table(locations):
    """
    Lấy toàn bộ ma trận thời gian (duration) giữa nhiều điểm trong 1 lần gọi API
    locations: danh sách các tuple (lat, lon)
    """
    coords = ";".join([f"{lon},{lat}" for lat, lon in locations])
    url = f"https://router.project-osrm.org/table/v1/driving/{coords}?annotations=duration"

    try:
        with urllib.request.urlopen(url, timeout=10) as response:
            payload = json.loads(response.read().decode("utf-8"))
            if payload.get("code") == "Ok":
                return payload.get("durations")
    except Exception:
        pass
    return None

def osrm_route(lat1, lon1, lat2, lon2):
    query = urllib.parse.quote(f"{lon1},{lat1};{lon2},{lat2}", safe=";,")
    url = f"https://router.project-osrm.org/route/v1/driving/{query}?overview=false"

    try:
        with urllib.request.urlopen(url, timeout=10) as response:
            payload = json.loads(response.read().decode("utf-8"))
    except (urllib.error.URLError, TimeoutError, ValueError):
        payload = None

    if not payload or not payload.get("routes"):
        distance_km = haversine_distance(lat1, lon1, lat2, lon2)
        duration_s = (distance_km / 28) * 3600 if distance_km > 0 else 0
        return {
            "distance_km": distance_km,
            "duration_s": duration_s,
            "source": "haversine",
            "is_fallback": True,
        }

    route = payload["routes"][0]
    return {
        "distance_km": float(route.get("distance", 0)) / 1000,
        "duration_s": float(route.get("duration", 0)),
        "source": "osrm",
        "is_fallback": False,
    }

def calculate_fee_breakdown(distance_km, weight, service_type="standard", surge_multiplier=1.0):
    services = {
        "standard": {"base": 12000, "weight": 5000, "distance": 3000},
        "fast": {"base": 18000, "weight": 6200, "distance": 3800},
        "express": {"base": 25000, "weight": 7500, "distance": 4800},
    }
    config = services.get(service_type, services["standard"])
    base_fee = config["base"] + (weight * config["weight"]) + (distance_km * config["distance"])
    total_fee = int(round(base_fee * surge_multiplier))
    rounded_base_fee = int(round(base_fee))
    surge_fee = max(0, total_fee - rounded_base_fee)
    return {
        "base_fee": rounded_base_fee,
        "surge_fee": surge_fee,
        "shipping_fee": total_fee,
    }


# ==========================================
# [NÂNG CẤP] AI LOGIC: GIẢI QUYẾT BÀI TOÁN LẤY & GIAO HÀNG (PDP) BẰNG GOOGLE OR-TOOLS
# ==========================================
def solve_pdp_for_batch(batch_orders_data, driver_location, max_weight_capacity):
    """
    Giải quyết bài toán Vehicle Routing Problem with Pickups and Deliveries (VRPPD).
    Cho phép giao/nhận xen kẽ để tối ưu lộ trình.
    """
    if not batch_orders_data:
        return {'route_details': [], 'total_duration_s': 0}

    locations = []
    demands = [0]
    
    if driver_location:
        locations.append((driver_location['lat'], driver_location['lng']))
        node_map = [{'type': 'driver', 'order_id': None, 'address': 'Vị trí của bạn'}]
    else:
        first_order = batch_orders_data[0]
        locations.append((first_order['sender_lat'], first_order['sender_lng']))
        node_map = [{'type': 'driver', 'order_id': None, 'address': 'Vị trí bắt đầu'}]
        
    if not OR_TOOLS_AVAILABLE:
        route_details = []
        for order in batch_orders_data:
            route_details.append({'type': 'pickup', 'order_id': order['id'], 'address': order.get('pickup_address', '')})
            route_details.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})
        return {'route_details': route_details, 'total_duration_s': 0}

    for order in batch_orders_data:
        locations.append((order['sender_lat'], order['sender_lng']))
        demands.append(float(order.get('weight', 0) or 0))
        node_map.append({'type': 'pickup', 'order_id': order['id'], 'address': order.get('pickup_address', '')})

    num_orders = len(batch_orders_data)
    for i, order in enumerate(batch_orders_data):
        locations.append((order['receiver_lat'], order['receiver_lng']))
        demands.append(-float(order.get('weight', 0) or 0))
        node_map.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})

    num_locations = len(locations)

    duration_matrix = osrm_table(locations)
    dist_matrix = {}
    for from_node in range(num_locations):
        dist_matrix[from_node] = {}
        for to_node in range(num_locations):
            if from_node == to_node:
                dist_matrix[from_node][to_node] = 0
            else:
                if duration_matrix and duration_matrix[from_node][to_node] is not None:
                    dist_matrix[from_node][to_node] = int(duration_matrix[from_node][to_node])
                else:
                    dist = haversine_distance(locations[from_node][0], locations[from_node][1], locations[to_node][0], locations[to_node][1])
                    dist_matrix[from_node][to_node] = int((dist / 28) * 3600)

    try:
        manager = pywrapcp.RoutingIndexManager(num_locations, 1, 0)
        routing = pywrapcp.RoutingModel(manager)

        def time_callback(from_index, to_index):
            from_node = manager.IndexToNode(from_index)
            to_node = manager.IndexToNode(to_index)
            return dist_matrix[from_node][to_node]

        transit_callback_index = routing.RegisterTransitCallback(time_callback)
        routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

        routing.AddDimension(
            transit_callback_index,
            0,
            86400,
            True,
            'Time'
        )
        time_dimension = routing.GetDimensionOrDie('Time')

        # Xử lý bắt buộc của OR-Tools: Sức chứa phải là số nguyên (Gam thay vì Kg)
        int_demands = [int(d * 1000) for d in demands]
        int_capacity = int(max_weight_capacity * 1000)

        def demand_callback(from_index):
            from_node = manager.IndexToNode(from_index)
            return int_demands[from_node]

        demand_callback_index = routing.RegisterUnaryTransitCallback(demand_callback)
        routing.AddDimensionWithVehicleCapacity(
            demand_callback_index,
            0,
            [int_capacity],
            True,
            'Capacity'
        )

        for i in range(num_orders):
            pickup_index = manager.NodeToIndex(i + 1)
            delivery_index = manager.NodeToIndex(i + 1 + num_orders)
            routing.AddPickupAndDelivery(pickup_index, delivery_index)
            routing.solver().Add(routing.VehicleVar(pickup_index) == routing.VehicleVar(delivery_index))
            routing.solver().Add(time_dimension.CumulVar(pickup_index) <= time_dimension.CumulVar(delivery_index))

        search_parameters = pywrapcp.DefaultRoutingSearchParameters()
        search_parameters.first_solution_strategy = routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC
        search_parameters.local_search_metaheuristic = routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
        search_parameters.time_limit.seconds = 2

        solution = routing.SolveWithParameters(search_parameters)

        if solution:
            route_details = []
            total_duration_s = solution.ObjectiveValue()
            index = routing.Start(0)
            while not routing.IsEnd(index):
                node_index = manager.IndexToNode(index)
                if node_index != 0:
                    route_details.append(node_map[node_index])
                index = solution.Value(routing.NextVar(index))
            return {
                'route_details': route_details,
                'total_duration_s': total_duration_s
            }
    except Exception:
        pass

    route_details = []
    for order in batch_orders_data:
        route_details.append({'type': 'pickup', 'order_id': order['id'], 'address': order.get('pickup_address', '')})
        route_details.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})
    return {'route_details': route_details, 'total_duration_s': 0}

# AI LOGIC: THUẬT TOÁN GHÉP CHUYẾN
def batch_orders(orders_json):
    MAX_PICKUP_DISTANCE_KM = 3.0  

    try:
        # [MỚI] Phân tích cấu trúc dữ liệu mới
        payload = json.loads(orders_json)
        driver_location = payload.get('driver_location')
        orders = payload.get('orders', [])
        MAX_ORDERS_PER_BATCH = payload.get('max_orders_per_batch', 5)
        max_weight_capacity = float(payload.get('max_weight_capacity', 0) or 0)

        # Sắp xếp đơn hàng ưu tiên theo thời gian hẹn lấy hàng sát nhất
        def get_urgency_key(order):
            time_str = order.get('scheduled_at')
            if not time_str:
                time_str = order.get('created_at') or "9999-12-31 23:59:59"
            
            method = order.get('shipping_method', 'standard')
            priority = 0 if method == 'express' else (1 if method == 'fast' else 2)
            return (priority, time_str)
            
        def get_raw_time(order):
            return order.get('scheduled_at') or order.get('created_at') or "9999-12-31 23:59:59"

        orders.sort(key=get_urgency_key)

        unassigned_orders = orders.copy()
        batches = []

        while unassigned_orders:
            if not unassigned_orders:
                break

            seed_order_data = unassigned_orders.pop(0)
            current_batch_data = [seed_order_data]
            current_batch_weight = float(seed_order_data.get('weight', 0) or 0)
            
            # Tọa độ trung tâm của cụm (ban đầu là tọa độ của đơn hạt giống)
            center_lat = float(seed_order_data['sender_lat'])
            center_lng = float(seed_order_data['sender_lng'])

            orders_to_remove = []
            
            # MUTUAL EXCLUSION: Nếu đơn hạt giống là Siêu tốc, không cho phép ghép thêm bất kỳ đơn nào khác
            is_seed_express = seed_order_data.get('shipping_method') == 'express'

            if not is_seed_express:
                # Tìm các đơn hàng khác ở gần đơn hạt giống
                for order in unassigned_orders:
                    if len(current_batch_data) >= MAX_ORDERS_PER_BATCH:
                        break # Đã đầy xe
                        
                    # Không cho phép đơn tiêu chuẩn/fast lôi kéo một đơn Siêu tốc vào cụm của mình
                    if order.get('shipping_method') == 'express':
                        continue

                    o_lat = float(order['sender_lat'])
                    o_lng = float(order['sender_lng'])
                    
                    # Tính khoảng cách từ điểm lấy hàng này đến trung tâm cụm
                    dist = haversine_distance(center_lat, center_lng, o_lat, o_lng)

                    candidate_weight = float(order.get('weight', 0) or 0)
                    exceeds_weight = (
                        max_weight_capacity > 0
                        and (current_batch_weight + candidate_weight) > max_weight_capacity
                    )

                    if dist <= MAX_PICKUP_DISTANCE_KM and not exceeds_weight:
                        current_batch_data.append(order)
                        current_batch_weight += candidate_weight
                        orders_to_remove.append(order)

            # Xóa các đơn đã được ghép khỏi danh sách chờ
            for o in orders_to_remove:
                unassigned_orders.remove(o)

            # [NÂNG CẤP] Gọi PDP solver để tìm lộ trình lấy/giao xen kẽ tối ưu
            pdp_solution = solve_pdp_for_batch(current_batch_data, driver_location, max_weight_capacity)
            total_duration_s = pdp_solution['total_duration_s']
            
            # Tối ưu hóa: Tính thời gian tiếp cận ngay tại Python bằng công thức Haversine 
            # để PHP không cần gọi API OSRM nhiều lần gây giật lag
            access_duration_s = 0
            if pdp_solution.get('route_details') and driver_location:
                first_step = pdp_solution['route_details'][0]
                f_oid = first_step['order_id']
                f_order = next((o for o in current_batch_data if o['id'] == f_oid), None)
                if f_order:
                    f_lat = float(f_order['sender_lat'] if first_step['type'] == 'pickup' else f_order['receiver_lat'])
                    f_lng = float(f_order['sender_lng'] if first_step['type'] == 'pickup' else f_order['receiver_lng'])
                    d_lat = float(driver_location['lat'])
                    d_lng = float(driver_location['lng'])
                    dist = haversine_distance(d_lat, d_lng, f_lat, f_lng)
                    access_duration_s = int((dist / 28) * 3600)

            batches.append({
                "batch_id": f"BATCH_{len(batches) + 1}",
                "order_ids": [o['id'] for o in current_batch_data],
                "route_details": pdp_solution['route_details'],
                "total_orders": len(current_batch_data),
                "total_weight": current_batch_weight,
                "total_duration_s": total_duration_s,
                "access_duration_s": access_duration_s,
                "most_urgent_time": min([get_raw_time(o) for o in current_batch_data]),
                "priority": min([get_urgency_key(o)[0] for o in current_batch_data])
            })

        return {
            "status": "success",
            "total_batches": len(batches),
            "batches": batches
        }

    except Exception as e:
        return {
            "status": "error",
            "message": str(e)
        }


if __name__ == "__main__":
    # Ép Python xuất dữ liệu ra Standard Output (CMD) dưới dạng UTF-8 để không lỗi tiếng Việt trên Windows
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

    try:
        if len(sys.argv) > 1 and sys.argv[1] == "--batch":
            json_data = sys.argv[2]
            result = batch_orders(json_data)
            print(json.dumps(result, ensure_ascii=False))
            sys.exit(0)

        # [MỚI] Bổ sung nhánh đọc từ file
        if len(sys.argv) > 1 and sys.argv[1] == "--batch-file":
            file_path = sys.argv[2]
            with open(file_path, 'r', encoding='utf-8') as f:
                json_data = f.read()
            result = batch_orders(json_data)
            print(json.dumps(result, ensure_ascii=False))
            sys.exit(0)


        sender_lat = float(sys.argv[1])
        sender_lng = float(sys.argv[2])
        receiver_lat = float(sys.argv[3])
        receiver_lng = float(sys.argv[4])
        weight = float(sys.argv[5])
        service_type = sys.argv[6] if len(sys.argv) > 6 else "standard"
        scheduled_at = sys.argv[7] if len(sys.argv) > 7 else ""

        route = osrm_route(sender_lat, sender_lng, receiver_lat, receiver_lng)
        surge_multiplier, surge_label = traffic_meta(scheduled_at)
        fee = calculate_fee_breakdown(route["distance_km"], weight, service_type, surge_multiplier)

        result = {
            "status": "success",
            "distance_km": round(route["distance_km"], 2),
            "duration_minutes": int(round(route["duration_s"] / 60)),
            "distance_source": route["source"],
            "surge_multiplier": surge_multiplier,
            "surge_label": surge_label,
            "base_fee": fee["base_fee"],
            "surge_fee": fee["surge_fee"],
            "shipping_fee": fee["shipping_fee"],
            "is_fallback": route["is_fallback"],
        }

    except Exception as error:
        result = {
            "status": "error",
            "message": str(error),
        }

    print(json.dumps(result, ensure_ascii=False))
