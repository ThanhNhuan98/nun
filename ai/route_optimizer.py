import json
import sys
import io
import httpx # Tối ưu: Dùng httpx để hỗ trợ gọi API bất đồng bộ (async)
import concurrent.futures
try:
    from ortools.constraint_solver import pywrapcp, routing_enums_pb2
    OR_TOOLS_AVAILABLE = True
except ImportError:
    OR_TOOLS_AVAILABLE = False
from math import asin, cos, radians, sin, sqrt
from datetime import datetime
import asyncio

# Bán kính Trái Đất tính bằng km (Hằng số toàn cục)
EARTH_RADIUS_KM = 6371.0

def haversine_distance(lat1, lon1, lat2, lon2):
    """
    Tính khoảng cách đường chim bay (Haversine) giữa 2 tọa độ GPS.
    Dùng làm phương án dự phòng khi gọi API OSRM bị lỗi hoặc để tính khoảng cách lọc thô nhanh chóng.
    """
    
    # Chuyển đổi toàn bộ tọa độ từ độ (degrees) sang radian để phục vụ tính toán lượng giác
    lat1, lon1, lat2, lon2 = map(radians, [float(lat1), float(lon1), float(lat2), float(lon2)])

    # Tính độ chênh lệch giữa các kinh độ và vĩ độ
    dlat = lat2 - lat1
    dlon = lon2 - lon1

    # Áp dụng công thức Haversine để tìm góc ở tâm
    a = sin(dlat / 2) ** 2 + cos(lat1) * cos(lat2) * sin(dlon / 2) ** 2
    c = 2 * asin(sqrt(a))

    # Trả về khoảng cách thực tế (km)
    return EARTH_RADIUS_KM * c

def traffic_meta(scheduled_time_str=""):
    """
    Phân tích thời gian hẹn/thời gian hiện tại để đưa ra hệ số nhân giá (Surge Multiplier).
    Cơ chế định giá động: Tăng giá vào giờ cao điểm, đêm muộn hoặc cuối tuần.
    """
    target_time = datetime.now()
    
    if scheduled_time_str:
        try:
            target_time = datetime.fromisoformat(scheduled_time_str)
        except ValueError:
            pass # Giữ nguyên thời gian hiện tại nếu chuỗi không hợp lệ
    
    # Đổi thời gian thành số phút từ đầu ngày (0h00 -> 0 phút, 12h00 -> 720 phút)
    minutes = target_time.hour * 60 + target_time.minute

    # Giờ cao điểm các ngày trong tuần: 7h00 - 9h00 (420-540p) và 16h30 - 19h00 (990-1140p)
    if target_time.isoweekday() <= 5 and ((420 <= minutes <= 540) or (990 <= minutes <= 1140)):
        return 1.20, "Giờ cao điểm"
    # Đêm muộn: Từ 21h00 (1260p) đến 5h00 sáng hôm sau (300p)
    if minutes >= 1260 or minutes <= 300:
        return 1.10, "Đêm muộn"
    # Cuối tuần: Thứ 7 (6) và Chủ Nhật (7)
    if target_time.isoweekday() >= 6:
        return 1.05, "Cuối tuần"
    return 1.00, "Bình thường"

async def osrm_table_async(locations, client: httpx.AsyncClient):
    """
    Gọi API OSRM (Bản đồ) để lấy toàn bộ ma trận thời gian (Duration Matrix) giữa nhiều điểm trong 1 lần gọi.
    Dữ liệu này là đầu vào bắt buộc để thuật toán Google OR-Tools tính toán đường đi.
    Tham số `locations`: danh sách các tuple (lat, lon)
    """
    # OSRM yêu cầu định dạng "kinh_độ,vĩ_độ;kinh_độ,vĩ_độ..."
    coords = ";".join([f"{lon},{lat}" for lat, lon in locations])
    # Sử dụng máy chủ OSRM public, hoặc đổi thành URL local của bạn (VD: http://localhost:5000)
    url = f"http://router.project-osrm.org/table/v1/driving/{coords}?annotations=duration"

    try:
        # Tối ưu: Dùng httpx.AsyncClient để gọi API không block luồng chính
        response = await client.get(url, timeout=10.0)
        response.raise_for_status() # Ném lỗi nếu HTTP status là 4xx hoặc 5xx
        payload = response.json()
        if payload.get("code") == "Ok":
            return payload.get("durations")
    except Exception:
        pass # Lỗi mạng hoặc API quá tải
    # Trả về None để hệ thống tự động fallback sang tính toán bằng Haversine
    return None

async def osrm_route_async(lat1, lon1, lat2, lon2, client: httpx.AsyncClient, vehicle_speed=28.0):
    """
    Gọi API OSRM để tìm lộ trình lái xe thực tế nối 2 điểm.
    Được gọi khi Khách hàng tạo đơn để tính chính xác quãng đường (distance_km) và thời gian (duration_s).
    """
    url = f"http://router.project-osrm.org/route/v1/driving/{lon1},{lat1};{lon2},{lat2}?overview=false"

    try:
        response = await client.get(url, timeout=10.0)
        response.raise_for_status()
        payload = response.json()
    except (httpx.RequestError, httpx.HTTPStatusError, json.JSONDecodeError):
        payload = None

    # Nếu gọi API lỗi hoặc OSRM không tìm được đường, dùng phương án dự phòng (Haversine)
    if not payload or not payload.get("routes"):
        distance_km = haversine_distance(lat1, lon1, lat2, lon2)
        duration_s = (distance_km / vehicle_speed) * 3600 if distance_km > 0 else 0
        return {
            "distance_km": distance_km,
            "duration_s": duration_s,
            "source": "haversine",
            "is_fallback": True,
        }

    # Thành công: Bóc tách khoảng cách và thời gian từ Response của OSRM
    route = payload["routes"][0]
    return {
        "distance_km": float(route.get("distance", 0)) / 1000,
        "duration_s": float(route.get("duration", 0)),
        "source": "osrm",
        "is_fallback": False,
    }

def calculate_fee_breakdown(distance_km, weight, pricing, service_type="standard", surge_multiplier=1.0):
    """
    Tính toán cước phí chi tiết bao gồm phí cơ bản (phụ thuộc vào khoảng cách, khối lượng, loại dịch vụ) 
    và phụ phí kẹt xe/giờ cao điểm (surge_fee).
    Biến `pricing` được truyền động từ Database PHP sang.
    """
    config = pricing.get(service_type, pricing.get("standard", {"base": 12000, "weight": 5000, "distance": 3000}))
    # Tính phí cơ sở trước khi nhân phụ phí
    base_fee = config["base"] + (weight * config["weight"]) + (distance_km * config["distance"])
    # Nhân hệ số giờ cao điểm và làm tròn
    total_fee = int(round(base_fee * surge_multiplier))
    rounded_base_fee = int(round(base_fee))
    # Tính phần phụ phí chênh lệch
    surge_fee = max(0, total_fee - rounded_base_fee)
    return {
        "base_fee": rounded_base_fee,
        "surge_fee": surge_fee,
        "shipping_fee": total_fee,
    }



def solve_pdp_for_batch(batch_orders_data, driver_location, max_weight_capacity, vehicle_speed=28.0, osrm_matrix=None):
    """
    Giải bài toán Tối ưu Lộ trình Nhận-Giao (VRPPD) bằng thư viện Google OR-Tools.
    Tìm đường đi ngắn nhất qua nhiều điểm, cho phép giao/nhận xen kẽ mà KHÔNG VƯỢT QUÁ tải trọng của xe.
    """
    if not batch_orders_data:
        return {'route_details': [], 'total_duration_s': 0}

    num_orders = len(batch_orders_data)

    # TỐI ƯU HÓA 1: Tắt sớm (Early Exit)
    # Nếu cụm chỉ có 1 đơn hàng (1 điểm lấy, 1 điểm giao), không cần nạp vào OR-Tools
    if num_orders == 1:
        order = batch_orders_data[0]
        route_details = []
        # Đã xóa node driver ở đây vì PHP tự động render "Vị trí của bạn" trên Radar
        route_details.append({'type': 'pickup', 'order_id': order['id'], 'address': order.get('pickup_address', '')})
        route_details.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})
        
        # SỬA LỖI ĐIỂM 0: Tính toán thời gian thực tế thay vì trả về 0
        dist = haversine_distance(order['sender_lat'], order['sender_lng'], order['receiver_lat'], order['receiver_lng'])
        duration_s = int((dist / vehicle_speed) * 3600)

        return {'route_details': route_details, 'total_duration_s': duration_s}

    # Khởi tạo các mảng dữ liệu cung cấp cho OR-Tools
    locations = []
    demands = [0] # Nhu cầu tải trọng của xe hiện tại (khởi điểm là 0)
    
    if driver_location:
        # Điểm bắt đầu là vị trí tài xế
        locations.append((driver_location['lat'], driver_location['lng']))
        node_map = [{'type': 'driver', 'order_id': None, 'address': 'Vị trí của bạn'}]
    else:
        # Nếu không có vị trí tài xế, mượn vị trí lấy hàng của đơn đầu tiên làm xuất phát
        first_order = batch_orders_data[0]
        locations.append((first_order['sender_lat'], first_order['sender_lng']))
        node_map = [{'type': 'driver', 'order_id': None, 'address': 'Vị trí bắt đầu'}]
        
    # Nếu server chưa cài thư viện ortools, ghép nối thủ công theo thứ tự: Lấy -> Giao liên tục
    if not OR_TOOLS_AVAILABLE:
        route_details = []
        for order in batch_orders_data:
            route_details.append({'type': 'pickup', 'order_id': order['id'], 'address': "⚠️ [CHƯA CÀI OR-TOOLS] " + order.get('pickup_address', '')})
            route_details.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})
        return {'route_details': route_details, 'total_duration_s': 0}

    # BƯỚC 1: Xây dựng các điểm LẤY HÀNG (pickup). Trọng lượng là SỐ DƯƠNG (chất thêm hàng lên xe)
    for order in batch_orders_data:
        locations.append((order['sender_lat'], order['sender_lng']))
        demands.append(float(order.get('weight', 0) or 0))
        node_map.append({'type': 'pickup', 'order_id': order['id'], 'address': order.get('pickup_address', '')})

    # BƯỚC 2: Xây dựng các điểm GIAO HÀNG (delivery). Trọng lượng là SỐ ÂM (dỡ hàng khỏi xe)
    for i, order in enumerate(batch_orders_data):
        locations.append((order['receiver_lat'], order['receiver_lng']))
        demands.append(-float(order.get('weight', 0) or 0))
        node_map.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})

    num_locations = len(locations)

    # Tối ưu: Nhận ma trận thời gian đã được tính toán sẵn từ bên ngoài
    duration_matrix = osrm_matrix
    
    # TỐI ƯU HÓA: Sử dụng mảng 2 chiều (List of Lists) thay vì Dictionary of Dictionaries 
    # Tăng tốc độ truy xuất O(1) trên RAM cho hàm callback của C++ OR-Tools
    dist_matrix = [[0] * num_locations for _ in range(num_locations)]
    
    for from_node in range(num_locations):
        for to_node in range(num_locations):
            # Tối ưu: Đánh chặn ngoại lệ trùng tọa độ (Lấy của A trùng Giao của B) -> Gắn cost = 0 ngay lập tức
            if from_node == to_node or locations[from_node] == locations[to_node]:
                dist_matrix[from_node][to_node] = 0
            else:
                # Ưu tiên dùng API OSRM
                if duration_matrix and duration_matrix[from_node][to_node] is not None:
                    dist_matrix[from_node][to_node] = int(duration_matrix[from_node][to_node])
                else:
                    # Dự phòng (Fallback): Tính thời gian ước tính với vận tốc ~28km/h theo đường chim bay
                    dist = haversine_distance(locations[from_node][0], locations[from_node][1], locations[to_node][0], locations[to_node][1])
                    dist_matrix[from_node][to_node] = int((dist / vehicle_speed) * 3600)

    try:
        # Khởi tạo thuật toán VRP (Vehicle Routing Problem) với 1 xe máy
        manager = pywrapcp.RoutingIndexManager(num_locations, 1, 0)
        routing = pywrapcp.RoutingModel(manager)

        # Hàm callback trả về thời gian đi từ điểm A sang điểm B
        def time_callback(from_index, to_index):
            from_node = manager.IndexToNode(from_index)
            to_node = manager.IndexToNode(to_index)
            
            # TỐI ƯU HÓA OPEN-ROUTE: Tài xế giao xong đơn cuối cùng KHÔNG CẦN quay về điểm xuất phát.
            # Ép chi phí (thời gian) từ điểm bất kỳ về Depot (Node 0) bằng 0.
            if to_node == 0:
                return 0
            return dist_matrix[from_node][to_node]

        transit_callback_index = routing.RegisterTransitCallback(time_callback)
        routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

        # Thêm chiều THỜI GIAN (Time Dimension) để kiểm soát tổng giờ chạy xe
        routing.AddDimension(
            transit_callback_index,
            0,
            86400, # Giới hạn tối đa 24h (86400s)
            True,  # Buộc thời gian khởi điểm bằng 0
            'Time' 
        )
        time_dimension = routing.GetDimensionOrDie('Time')

        # Quy đổi trọng lượng (kg) thành số nguyên gram để thuật toán tính toán chính xác
        int_demands = [int(d * 1000) for d in demands]
        int_capacity = int(max_weight_capacity * 1000)
        
        # SỬA LỖI: Đảm bảo sức chứa xe tối thiểu bằng tổng lượng hàng Lấy để tránh bị Vô nghiệm (Infeasible)
        max_batch_demand = sum([d for d in int_demands if d > 0])
        if int_capacity < max_batch_demand:
            int_capacity = max_batch_demand

        # Hàm callback theo dõi tải trọng hiện tại trên xe
        def demand_callback(from_index):
            from_node = manager.IndexToNode(from_index)
            return int_demands[from_node]

        demand_callback_index = routing.RegisterUnaryTransitCallback(demand_callback)
        # Thêm chiều SỨC CHỨA (Capacity Dimension) ngăn xe chở quá tải
        routing.AddDimensionWithVehicleCapacity(
            demand_callback_index,
            0,
            [int_capacity],
            True,
            'Capacity'
        )

        # ĐỊNH NGHĨA RÀNG BUỘC (CONSTRAINTS) CHO TỪNG ĐƠN HÀNG
        for i in range(num_orders):
            pickup_index = manager.NodeToIndex(i + 1)
            delivery_index = manager.NodeToIndex(i + 1 + num_orders)
            routing.AddPickupAndDelivery(pickup_index, delivery_index)
            # Ràng buộc 1: Điểm Lấy hàng và Giao hàng phải được thực hiện bởi CÙNG MỘT XE (ở đây chỉ có 1 xe)
            routing.solver().Add(routing.VehicleVar(pickup_index) == routing.VehicleVar(delivery_index))
            # Ràng buộc 2: Thời điểm Lấy hàng BẮT BUỘC PHẢI DIỄN RA TRƯỚC thời điểm Giao hàng
            routing.solver().Add(time_dimension.CumulVar(pickup_index) <= time_dimension.CumulVar(delivery_index))

        # Cấu hình chiến lược tìm kiếm
        search_parameters = pywrapcp.DefaultRoutingSearchParameters()
        # QUAN TRỌNG: Phải dùng PARALLEL_CHEAPEST_INSERTION để AI có thể đan xen Lấy/Giao
        search_parameters.first_solution_strategy = routing_enums_pb2.FirstSolutionStrategy.PARALLEL_CHEAPEST_INSERTION
        # Cải thiện phương án bằng thuật toán Metaheuristic có định hướng (Thoát khỏi tối ưu cục bộ)
        search_parameters.local_search_metaheuristic = routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
        
        # TỐI ƯU HÓA 2: Giới hạn thời gian động (Dynamic Time Limit)
        if num_orders <= 3:
            search_parameters.time_limit.seconds = 0
            search_parameters.time_limit.nanos = 500000000 
        else:
            # Tăng thời gian giải thuật lên 2 giây cho cụm 4-5 đơn để AI kịp tìm tuyến đường lồng ghép tối ưu nhất
            search_parameters.time_limit.seconds = 2 

        # Bắt đầu giải bài toán
        solution = routing.SolveWithParameters(search_parameters)

        if solution:
            # Bóc tách kết quả đường đi từ solution
            route_details = []
            total_duration_s = 0
            
            # Kiểm tra xem AI có bị ép phải bỏ qua điểm nào không
            for i in range(1, num_locations):
                if solution.Value(routing.NextVar(manager.NodeToIndex(i))) == manager.NodeToIndex(i):
                    raise Exception("AI bị giới hạn, không thể ghép toàn bộ các đơn.")
                    
            index = routing.Start(0)
            while not routing.IsEnd(index):
                node_index = manager.IndexToNode(index)
                if node_index != 0:
                    route_details.append(node_map[node_index])
                previous_index = index
                index = solution.Value(routing.NextVar(index))
                total_duration_s += routing.GetArcCostForVehicle(previous_index, index, 0)
                
            return {
                'route_details': route_details,
                'total_duration_s': total_duration_s
            }
        else:
            raise Exception("Thuật toán bế tắc (Infeasible).")
    except Exception as e:
        error_msg = str(e)

    # Lỗi giải thuật: Phục hồi về lộ trình tuyến tính thông thường
    route_details = []
    fallback_duration = 0
    for order in batch_orders_data:
        # In thẳng lỗi ra màn hình để báo hiệu AI đang gặp sự cố và chạy Fallback
        route_details.append({'type': 'pickup', 'order_id': order['id'], 'address': f"⚠️ [AI LỖI: {error_msg}] " + order.get('pickup_address', '')})
        route_details.append({'type': 'delivery', 'order_id': order['id'], 'address': order.get('delivery_address', '')})
        dist = haversine_distance(order['sender_lat'], order['sender_lng'], order['receiver_lat'], order['receiver_lng'])
        fallback_duration += int((dist / vehicle_speed) * 3600)
    return {'route_details': route_details, 'total_duration_s': fallback_duration}

async def optimize_batches_async(driver_location, orders, max_orders_per_batch, max_weight_capacity, vehicle_speed=28.0):
    """
    Thuật toán Gom cụm (Clustering).
    Nhận danh sách toàn bộ đơn hàng đang chờ, nhóm các đơn hàng nằm gần nhau (< 3km) vào cùng một chuyến đi.
    Xử lý loại trừ (Mutual Exclusion) để đảm bảo đơn Siêu tốc không bị ghép chung với đơn khác.
    """
    MAX_PICKUP_DISTANCE_KM = 3.0  # Các đơn hàng phải lấy cách nhau tối đa 3km để được ghép chung

    try:
        max_weight_capacity = float(max_weight_capacity or 0)

        # Định nghĩa Key ưu tiên phân bổ: Mức độ ưu tiên dịch vụ (0 là Siêu tốc) -> Thời gian hẹn
        def get_urgency_key(order):
            time_str = order.get('scheduled_at')
            if not time_str:
                time_str = order.get('created_at') or "9999-12-31 23:59:59"
            
            method = order.get('shipping_method', 'standard')
            priority = 0 if method == 'express' else (1 if method == 'fast' else 2)
            return (priority, time_str)
            
        def get_raw_time(order):
            return order.get('scheduled_at') or order.get('created_at') or "9999-12-31 23:59:59"

        # Sắp xếp toàn bộ mảng đơn hàng từ ưu tiên cao nhất/sắp trễ giờ nhất lên đầu
        orders.sort(key=get_urgency_key)

        unassigned_orders = orders.copy()
        clustered_batches = []

        # BƯỚC 1: Tách rời logic Gom cụm (Clustering) ra khỏi Tối ưu lộ trình (Routing)
        # Gom các đơn hàng thỏa mãn điều kiện không gian và tải trọng thành các list riêng biệt
        while unassigned_orders:
            if not unassigned_orders:
                break

            # Lấy đơn hàng đầu tiên (khẩn cấp nhất) làm Hạt giống (Seed) để xây dựng chuyến ghép
            seed_order_data = unassigned_orders.pop(0)
            current_batch_data = [seed_order_data]
            current_batch_weight = float(seed_order_data.get('weight', 0) or 0)
            
            center_lat = float(seed_order_data['sender_lat'])
            center_lng = float(seed_order_data['sender_lng'])

            orders_to_remove = []
            
            is_seed_express = seed_order_data.get('shipping_method') == 'express'

            # Chống ghép chuyến: Nếu Hạt giống là đơn Siêu tốc (Express), Bỏ qua bước gom cụm
            if not is_seed_express:
                for order in unassigned_orders:
                    if len(current_batch_data) >= max_orders_per_batch:
                        break 
                        
                    # Không được phép bốc đơn Siêu Tốc vào chuyến ghép này
                    if order.get('shipping_method') == 'express':
                        continue

                    o_lat = float(order['sender_lat'])
                    o_lng = float(order['sender_lng'])
                    
                    # Tính khoảng cách từ điểm lấy Hạt giống đến điểm lấy của đơn hiện hành
                    dist = haversine_distance(center_lat, center_lng, o_lat, o_lng)

                    candidate_weight = float(order.get('weight', 0) or 0)
                    # Kiểm tra xem nếu bốc thêm đơn này xe có quá tải không
                    exceeds_weight = (
                        max_weight_capacity > 0
                        and (current_batch_weight + candidate_weight) > max_weight_capacity
                    )

                    # Đủ điều kiện: Ghép vào chung 1 xe
                    if dist <= MAX_PICKUP_DISTANCE_KM and not exceeds_weight:
                        current_batch_data.append(order)
                        current_batch_weight += candidate_weight
                        orders_to_remove.append(order)

            # Xóa các đơn đã được ghép khỏi hàng chờ phân bổ (Tối ưu hóa: Dùng Set để lọc O(N) thay vì O(N^2) của remove trong vòng lặp)
            if orders_to_remove:
                removed_ids = {o['id'] for o in orders_to_remove}
                unassigned_orders = [o for o in unassigned_orders if o['id'] not in removed_ids]

            clustered_batches.append({
                "data": current_batch_data,
                "weight": current_batch_weight
            })

        # BƯỚC 2: Xử lý song song các Batch bằng ThreadPoolExecutor
        # Tránh nút thắt cổ chai khi có quá nhiều cụm đơn cần gọi API OSRM và OR-Tools
        batches = []

        # Tối ưu: Dùng context manager của httpx để quản lý kết nối hiệu quả
        async def process_single_batch(batch_info, index, client: httpx.AsyncClient):
            batch_data = batch_info["data"]
            batch_weight = batch_info["weight"]
            
            # Tối ưu: Lấy ma trận OSRM một lần duy nhất cho mỗi batch
            all_locations = [ (driver_location['lat'], driver_location['lng']) ] if driver_location else []
            for o in batch_data:
                all_locations.extend([(o['sender_lat'], o['sender_lng']), (o['receiver_lat'], o['receiver_lng'])])
            osrm_matrix = await osrm_table_async(all_locations, client) if all_locations else None

            # Tối ưu hóa sâu: Đưa tác vụ CPU-bound (thuật toán OR-Tools) vào ThreadPoolExecutor
            # Điều này giúp Event Loop của FastAPI không bị đóng băng khi tính toán ma trận, duy trì khả năng nhận hàng ngàn Request cùng lúc.
            loop = asyncio.get_running_loop()
            pdp_solution = await loop.run_in_executor(
                None, 
                solve_pdp_for_batch, 
                batch_data, driver_location, max_weight_capacity, vehicle_speed, osrm_matrix
            )
            total_duration_s = pdp_solution['total_duration_s']
            
            access_duration_s = 0
            # Tính toán khoảng thời gian cần thiết để tài xế chạy đến điểm bắt đầu đầu tiên của chuyến
            if pdp_solution.get('route_details') and driver_location:
                first_step = pdp_solution['route_details'][0]
                f_oid = first_step['order_id']
                f_order = next((o for o in batch_data if o['id'] == f_oid), None)
                if f_order:
                    f_lat = float(f_order['sender_lat'] if first_step['type'] == 'pickup' else f_order['receiver_lat'])
                    f_lng = float(f_order['sender_lng'] if first_step['type'] == 'pickup' else f_order['receiver_lng'])
                    d_lat = float(driver_location['lat'])
                    d_lng = float(driver_location['lng'])
                    dist = haversine_distance(d_lat, d_lng, f_lat, f_lng)
                    access_duration_s = int((dist / vehicle_speed) * 3600)

            return {
                "batch_id": f"BATCH_{index + 1}",
                "order_ids": [o['id'] for o in batch_data],
                "route_details": pdp_solution['route_details'],
                "total_orders": len(batch_data),
                "total_weight": batch_weight,
                "total_duration_s": total_duration_s,
                "access_duration_s": access_duration_s,
                "most_urgent_time": min([get_raw_time(o) for o in batch_data]),
                "priority": min([get_urgency_key(o)[0] for o in batch_data])
            }

        # Tối ưu: Sử dụng asyncio.gather để chạy các tác vụ bất đồng bộ song song
        async def run_all_batches():
            async with httpx.AsyncClient() as client:
                tasks = [process_single_batch(b, i, client) for i, b in enumerate(clustered_batches)]
                return await asyncio.gather(*tasks)

        # Chờ tất cả các cụm (batches) được xử lý hoàn tất
        batches = await run_all_batches()
                
        # Sắp xếp lại theo thứ tự BATCH_1, BATCH_2 cho chuẩn xác
        batches.sort(key=lambda x: int(x["batch_id"].split("_")[1]))

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
    # Ép chuẩn định dạng UTF-8 để khắc phục lỗi mất dấu Tiếng Việt khi PHP đọc kết quả qua Command Line
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

    try:
        # Chế độ chạy 1: Xử lý Gom cụm chuyến đi (Clustering AI) truyền qua arguments
        if len(sys.argv) > 1 and sys.argv[1] == "--batch":
            json_data = sys.argv[2]
            payload = json.loads(json_data)
            result = asyncio.run(optimize_batches_async(
                payload.get('driver_location'),
                payload.get('orders', []),
                payload.get('max_orders_per_batch', 5),
                payload.get('max_weight_capacity', 0),
                payload.get('vehicle_speed', 28.0)
            ))
            print(json.dumps(result, ensure_ascii=False))
            sys.exit(0)

        # Chế độ chạy 2: Xử lý Gom cụm (Clustering AI) truyền qua file nháp (Tối ưu để không đứt chuỗi shell do data lớn)
        if len(sys.argv) > 1 and sys.argv[1] == "--batch-file":
            file_path = sys.argv[2]
            with open(file_path, 'r', encoding='utf-8') as f:
                json_data = f.read()
            payload = json.loads(json_data)
            result = asyncio.run(optimize_batches_async(
                payload.get('driver_location'),
                payload.get('orders', []),
                payload.get('max_orders_per_batch', 5),
                payload.get('max_weight_capacity', 0),
                payload.get('vehicle_speed', 28.0)
            ))
            print(json.dumps(result, ensure_ascii=False))
            sys.exit(0)


        # Chế độ chạy 3: Tính cước vận chuyển và quãng đường (Đơn lẻ, truyền theo danh sách tham số)
        sender_lat = float(sys.argv[1])
        sender_lng = float(sys.argv[2])
        receiver_lat = float(sys.argv[3])
        receiver_lng = float(sys.argv[4])
        weight = float(sys.argv[5])
        service_type = sys.argv[6] if len(sys.argv) > 6 else "standard"
        scheduled_at = sys.argv[7] if len(sys.argv) > 7 else ""
        
        # Đọc cấu hình JSON nếu có truyền thêm tham số thứ 8
        pricing = {
            "standard": {"base": 12000, "weight": 5000, "distance": 3000},
            "fast": {"base": 18000, "weight": 6200, "distance": 3800},
            "express": {"base": 25000, "weight": 7500, "distance": 4800},
        }
        vehicle_speed = 28.0
        
        if len(sys.argv) > 8:
            config_payload = json.loads(sys.argv[8])
            pricing = config_payload.get('pricing', pricing)
            vehicle_speed = config_payload.get('vehicle_speed', 28.0)

        async def main_route():
            async with httpx.AsyncClient() as client:
                return await osrm_route_async(sender_lat, sender_lng, receiver_lat, receiver_lng, client, vehicle_speed)
        route = asyncio.run(main_route())
        surge_multiplier, surge_label = traffic_meta(scheduled_at)
        fee = calculate_fee_breakdown(route["distance_km"], weight, pricing, service_type, surge_multiplier)

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
