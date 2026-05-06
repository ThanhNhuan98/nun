/**
 * Lớp hỗ trợ xử lý Bản đồ dùng chung cho toàn hệ thống
 * Hỗ trợ Leaflet.js (có thể tùy biến sang Google Maps nếu cần)
 */
class MapHelper {
    constructor(containerId, defaultLat = 10.762622, defaultLng = 106.660172, zoomLevel = 13) {
        this.containerId = containerId;
        this.map = L.map(this.containerId).setView([defaultLat, defaultLng], zoomLevel);
        this.markers = {};

        // Thay đổi sang layer của Google Maps để đồng bộ giao diện đẹp hơn
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '&copy; Google Maps'
        }).addTo(this.map);
    }

    /**
     * Thêm một điểm đánh dấu (Marker) lên bản đồ
     */
    addMarker(id, lat, lng, popupText = '', isDraggable = false) {
        if (this.markers[id]) {
            this.map.removeLayer(this.markers[id]);
        }

        const marker = L.marker([lat, lng], { draggable: isDraggable }).addTo(this.map);
        
        if (popupText) {
            marker.bindPopup(popupText).openPopup();
        }

        this.markers[id] = marker;
        return marker;
    }

    /**
     * Kích hoạt chế độ chọn vị trí trên bản đồ (Dành cho trang Tạo đơn hàng)
     * @param {Function} callback Hàm gọi lại khi người dùng chọn xong tọa độ
     */
    enableLocationPicker(markerId = 'picker', callback) {
        this.map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            this.addMarker(markerId, lat, lng, 'Vị trí đã chọn', true);
            
            // Cập nhật tọa độ khi kéo thả marker
            this.markers[markerId].on('dragend', (event) => {
                const newLatLng = event.target.getLatLng();
                if (typeof callback === 'function') {
                    callback(newLatLng.lat, newLatLng.lng);
                }
            });

            if (typeof callback === 'function') {
                callback(lat, lng);
            }
        });
    }

    /**
     * Vẽ đường đi giữa 2 điểm (Dành cho trang Tracking / Lộ trình)
     */
    drawRoute(startLat, startLng, endLat, endLng) {
        // Nếu dùng Leaflet Routing Machine
        if (typeof L.Routing !== 'undefined') {
            L.Routing.control({
                waypoints: [
                    L.latLng(startLat, startLng),
                    L.latLng(endLat, endLng)
                ],
                routeWhileDragging: false
            }).addTo(this.map);
        }
    }

    /**
     * Khởi tạo bản đồ theo dõi hành trình đơn hàng (Tracking) dùng chung
     */
    initTracking(config) {
        const {
            orderStatus, trackingCode,
            driverLat, driverLng,
            senderLat, senderLng,
            receiverLat, receiverLng
        } = config;

        if (!senderLat || !senderLng || !receiverLat || !receiverLng) {
            this.map.setView([10.762622, 106.660172], 13);
            L.popup().setLatLng([10.762622, 106.660172])
                .setContent("Đơn hàng này chưa có dữ liệu tọa độ.").openOn(this.map);
            return;
        }

        this.map.setView([senderLat, senderLng], 14);

        const driverIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-orange.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41]
        });
        const senderIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41]
        });
        const receiverIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41]
        });

        let routingControl = null;
        let dLat = parseFloat(driverLat) || 0;
        let dLng = parseFloat(driverLng) || 0;

        const drawRoute = () => {
            const waypoints = [];
            const hasDriverLoc = (dLat !== 0 && dLng !== 0);
            
            if (['accepted', 'picking_up'].includes(orderStatus)) {
                waypoints.push(hasDriverLoc ? L.latLng(dLat, dLng) : L.latLng(senderLat, senderLng));
                waypoints.push(L.latLng(senderLat, senderLng));
            } else if (['in_transit', 'shipping'].includes(orderStatus)) {
                waypoints.push(hasDriverLoc ? L.latLng(dLat, dLng) : L.latLng(senderLat, senderLng));
                waypoints.push(L.latLng(receiverLat, receiverLng));
            } else {
                waypoints.push(L.latLng(senderLat, senderLng));
                waypoints.push(L.latLng(receiverLat, receiverLng));
            }

            if (routingControl) {
                routingControl.setWaypoints(waypoints);
                return;
            }

            if (typeof L.Routing !== 'undefined') {
                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                    addWaypoints: false, routeWhileDragging: false, fitSelectedRoutes: true, show: false,
                    lineOptions: { styles: [{color: '#3b82f6', weight: 5}] },
                    createMarker: function(i, wp) {
                        let iconToUse = senderIcon;
                        if (i === 0 && hasDriverLoc && ['accepted', 'picking_up', 'in_transit', 'shipping'].includes(orderStatus)) {
                            iconToUse = driverIcon;
                        } else if (i === 1 && (!hasDriverLoc || ['in_transit', 'shipping'].includes(orderStatus) || !['accepted', 'picking_up', 'in_transit', 'shipping'].includes(orderStatus))) {
                            iconToUse = receiverIcon;
                        }
                        return L.marker(wp.latLng, {icon: iconToUse}).bindPopup(iconToUse === senderIcon ? 'Điểm lấy hàng' : (iconToUse === receiverIcon ? 'Điểm giao hàng' : 'Vị trí tài xế'));
                    }
                }).addTo(this.map);
            }
        };

        drawRoute();

        if (['accepted', 'picking_up', 'in_transit', 'shipping'].includes(orderStatus) && trackingCode) {
            this.trackingInterval = setInterval(async () => {
                try {
                    const res = await fetch('/api/orders/driver-location/' + trackingCode);
                    const data = await res.json();
                    if (data && data.success && (dLat !== data.lat || dLng !== data.lng)) {
                        dLat = data.lat; dLng = data.lng; drawRoute();
                    }
                } catch(e) {}
            }, 10000);
        }
    }

    /**
     * Hủy bản đồ và xóa bộ nhớ (Dùng khi AJAX Load lại)
     */
    destroy() {
        if (this.trackingInterval) {
            clearInterval(this.trackingInterval);
        }
        if (this.map) {
            this.map.remove();
        }
    }
}
