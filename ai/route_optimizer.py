import json
import sys
import io
import urllib.error
import urllib.parse
import urllib.request
from ortools.constraint_solver import pywrapcp, routing_enums_pb2
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
# [MỚI] AI LOGIC: GIẢI QUYẾT TSP BẰNG GOOGLE OR-TOOLS
# ==========================================
def solve_tsp_for_batch(batch_orders_data):
    """
    Tìm lộ trình tối ưu (TSP) cho một nhóm các đơn hàng.
    Input: danh sách các đối tượng order.
    Output: dictionary với 'route' (list of IDs) và 'total_duration_s' (int).
    """
    if not batch_orders_data:
        return {'route': [], 'total_duration_s': 0}
        
    if len(batch_orders_data) == 1:
        return {
            'route': [o['id'] for o in batch_orders_data],
            'total_duration_s': 0
        }
        
    # TỐI ƯU 1: Với nhóm chỉ có 2 đơn, đi thẳng từ A -> B, không cần khởi tạo OR-Tools C++ engine
    if len(batch_orders_data) == 2:
        o1, o2 = batch_orders_data[0], batch_orders_data[1]
        route_info = osrm_route(o1['lat'], o1['lng'], o2['lat'], o2['lng'])
        return {
            'route': [o1['id'], o2['id']],
            'total_duration_s': int(route_info.get('duration_s', 0))
        }

    # 1. Tạo ma trận khoảng cách/thời gian
    num_locations = len(batch_orders_data)
    locations = [(o['lat'], o['lng']) for o in batch_orders_data]
    
    # Gọi OSRM Table API để lấy toàn bộ ma trận trong 1 lần thay vì N^2 lần
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
                    # Fallback (dự phòng) bằng haversine nếu API lỗi
                    dist = haversine_distance(locations[from_node][0], locations[from_node][1], locations[to_node][0], locations[to_node][1])
                    dist_matrix[from_node][to_node] = int((dist / 28) * 3600)

    # 2. Khởi tạo bài toán routing
    manager = pywrapcp.RoutingIndexManager(num_locations, 1, 0) # (số điểm, số xe, điểm bắt đầu)
    routing = pywrapcp.RoutingModel(manager)

    # 3. Tạo callback để cung cấp ma trận khoảng cách cho solver
    def distance_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        return dist_matrix[from_node][to_node]

    transit_callback_index = routing.RegisterTransitCallback(distance_callback)
    routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

    # 4. Thiết lập tham số và giải
    search_parameters = pywrapcp.DefaultRoutingSearchParameters()
    # Sử dụng thuật toán PATH_CHEAPEST_ARC để tìm giải pháp ban đầu
    search_parameters.first_solution_strategy = (routing_enums_pb2.FirstSolutionStrategy.PATH_CHEAPEST_ARC)
    
    # TỐI ƯU: Chỉ dùng Guided Local Search và giới hạn 1 giây nếu số lượng điểm lớn.
    # Với các cụm nhỏ (<=5 điểm), OR-Tools giải quyết trong vài mili-giây, không cần chờ.
    if num_locations > 5:
        search_parameters.local_search_metaheuristic = (routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH)
        search_parameters.time_limit.seconds = 1
    solution = routing.SolveWithParameters(search_parameters)

    # 5. Trích xuất kết quả
    if solution:
        index = routing.Start(0)
        route = []
        total_duration_s = solution.ObjectiveValue() # Lấy tổng chi phí (thời gian) từ solver
        while not routing.IsEnd(index):
            node_index = manager.IndexToNode(index)
            route.append(batch_orders_data[node_index]['id'])
            index = solution.Value(routing.NextVar(index))
        return {
            'route': route,
            'total_duration_s': total_duration_s
        }
    
    # Nếu không giải được, trả về thứ tự ban đầu
    return {
        'route': [o['id'] for o in batch_orders_data],
        'total_duration_s': 0
    }

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
            return time_str

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
            center_lat = float(seed_order_data['lat'])
            center_lng = float(seed_order_data['lng'])

            orders_to_remove = []

            # Tìm các đơn hàng khác ở gần đơn hạt giống
            for order in unassigned_orders:
                if len(current_batch_data) >= MAX_ORDERS_PER_BATCH:
                    break # Đã đầy xe

                o_lat = float(order['lat'])
                o_lng = float(order['lng'])
                
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
                    
                    # TỐI ƯU 2: KHÔNG cập nhật lại trung tâm cụm.
                    # Giữ nguyên tâm là đơn hàng hạt giống (đơn cần lấy gấp nhất).
                    # Điều này đảm bảo tất cả các đơn ghép vào đều chắc chắn nằm gọn trong bán kính 3km từ đơn ưu tiên, tránh tình trạng "trôi tâm" tạo ra lộ trình quá dài.

            # Xóa các đơn đã được ghép khỏi danh sách chờ
            for o in orders_to_remove:
                unassigned_orders.remove(o)

            # [TỐI ƯU] Gọi TSP solver để tìm lộ trình tốt nhất cho batch này
            tsp_solution = solve_tsp_for_batch(current_batch_data)
            optimized_route_ids = tsp_solution['route']
            total_duration_s = tsp_solution['total_duration_s']

            # [MỚI] Tính thời gian di chuyển từ vị trí tài xế đến điểm lấy hàng đầu tiên
            access_duration_s = 0
            first_order_id = optimized_route_ids[0] if optimized_route_ids else None
            if driver_location and first_order_id:
                # Tìm dữ liệu của đơn hàng đầu tiên
                first_order_data = next((o for o in current_batch_data if o['id'] == first_order_id), None)
                if first_order_data:
                    route_to_first = osrm_route(
                        driver_location['lat'], driver_location['lng'],
                        first_order_data['lat'], first_order_data['lng']
                    )
                    access_duration_s = route_to_first.get('duration_s', 0)

            batches.append({
                "batch_id": f"BATCH_{len(batches) + 1}",
                "order_ids": [o['id'] for o in current_batch_data], # Giữ danh sách ID gốc
                "optimized_route": optimized_route_ids, # Thêm lộ trình đã tối ưu
                "total_orders": len(current_batch_data),
                "total_weight": current_batch_weight,
                # [MỚI] Thêm các chỉ số mới
                "total_duration_s": total_duration_s,
                "access_duration_s": access_duration_s,
                "most_urgent_time": min([get_urgency_key(o) for o in current_batch_data])
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
