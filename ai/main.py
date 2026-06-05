from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import uvicorn
import httpx

# Thầy giả định file thuật toán hiện tại của em tên là route_optimizer.py
# Em cần sửa lại file đó thành một module có chứa hàm dùng để import
from route_optimizer import optimize_batches_async, osrm_route_async, traffic_meta, calculate_fee_breakdown

app = FastAPI(title="NUN Express AI Routing API", version="1.0.0")

# Định nghĩa cấu trúc dữ liệu đầu vào dựa trên dữ liệu PHP đang gửi
class RoutingRequest(BaseModel):
    driver_location: Dict[str, Any]
    orders: List[Dict[str, Any]]
    max_orders_per_batch: int
    max_weight_capacity: float
    vehicle_speed: Optional[float] = 28.0

class FeeRequest(BaseModel):
    sender_lat: float
    sender_lng: float
    receiver_lat: float
    receiver_lng: float
    weight: float
    service_type: str = "standard"
    scheduled_at: str = ""
    pricing: Dict[str, Dict[str, float]]
    vehicle_speed: Optional[float] = 28.0

@app.post("/api/v1/optimize-routes")
async def optimize_routes(request: RoutingRequest):
    try:
        # Hỗ trợ tương thích ngược cho cả Pydantic V1 và V2
        data = request.model_dump() if hasattr(request, 'model_dump') else request.dict()
        
        # Tối ưu hóa sâu: Gọi thẳng hàm async thực thụ, tác vụ I/O sẽ chạy bất đồng bộ
        # còn tác vụ CPU-bound đã được đẩy vào ThreadPool bên trong hàm này
        result = await optimize_batches_async(
            driver_location=data['driver_location'],
            orders=data['orders'],
            max_orders_per_batch=data['max_orders_per_batch'],
            max_weight_capacity=data['max_weight_capacity'],
            vehicle_speed=data.get('vehicle_speed', 28.0)
        )
        
        # Trả về kết quả JSON cho PHP
        return result
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/v1/calculate-fee")
async def calculate_fee(request: FeeRequest):
    try:
        data = request.model_dump() if hasattr(request, 'model_dump') else request.dict()
        
        # Tối ưu hóa: Dùng httpx.AsyncClient để gọi API OSRM bất đồng bộ
        async with httpx.AsyncClient() as client:
            route = await osrm_route_async(data['sender_lat'], data['sender_lng'], data['receiver_lat'], data['receiver_lng'], client, data.get('vehicle_speed', 28.0))
            
        surge_multiplier, surge_label = traffic_meta(data['scheduled_at'])
        fee = calculate_fee_breakdown(route["distance_km"], data['weight'], data['pricing'], data['service_type'], surge_multiplier)

        return {
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
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    # Chạy server ở port 8000
    uvicorn.run(app, host="127.0.0.1", port=8000)