/**
 * Lớp hỗ trợ xử lý Bản đồ dùng chung cho toàn hệ thống
 * Hỗ trợ Leaflet.js (có thể tùy biến sang Google Maps nếu cần)
 */
class MapHelper {
    constructor(containerId, defaultLat = 16.463713, defaultLng = 107.590866, zoomLevel = 13) {
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

    normalizeLatLng(lat, lng) {
        let normalizedLat = Number.parseFloat(lat);
        let normalizedLng = Number.parseFloat(lng);

        if (!Number.isFinite(normalizedLat) || !Number.isFinite(normalizedLng)) {
            return null;
        }

        const isValid = (valueLat, valueLng) => (
            valueLat >= -90 && valueLat <= 90 &&
            valueLng >= -180 && valueLng <= 180 &&
            !(valueLat === 0 && valueLng === 0)
        );

        if (!isValid(normalizedLat, normalizedLng) && isValid(normalizedLng, normalizedLat)) {
            [normalizedLat, normalizedLng] = [normalizedLng, normalizedLat];
        }

        return isValid(normalizedLat, normalizedLng)
            ? { lat: normalizedLat, lng: normalizedLng }
            : null;
    }

    calculateBearing(lat1, lng1, lat2, lng2) {
        const toRad = Math.PI / 180;
        const toDeg = 180 / Math.PI;
        const dLng = (lng2 - lng1) * toRad;
        const y = Math.sin(dLng) * Math.cos(lat2 * toRad);
        const x = Math.cos(lat1 * toRad) * Math.sin(lat2 * toRad) -
                  Math.sin(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.cos(dLng);
        const brng = Math.atan2(y, x) * toDeg;
        return (brng + 360) % 360;
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
        this.map.on('click', async (e) => {
            const { lat, lng } = e.latlng;
            this.addMarker(markerId, lat, lng, 'Vị trí đã chọn', true);
            
            let address = '';
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi`);
                const data = await res.json();
                address = data.display_name || '';
            } catch(err) {}

            // Cập nhật tọa độ khi kéo thả marker
            this.markers[markerId].on('dragend', async (event) => {
                const newLatLng = event.target.getLatLng();
                let dragAddress = '';
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${newLatLng.lat}&lon=${newLatLng.lng}&accept-language=vi`);
                    const data = await res.json();
                    dragAddress = data.display_name || '';
                } catch(err) {}

                if (typeof callback === 'function') {
                    callback(newLatLng.lat, newLatLng.lng, dragAddress);
                }
            });

            if (typeof callback === 'function') {
                callback(lat, lng, address);
            }
        });
    }

    /**
     * Tìm kiếm địa chỉ và trả về tên đầy đủ (Geocoding)
     */
    async searchAddress(query) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&accept-language=vi`);
            const data = await response.json();
            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon),
                    displayName: data[0].display_name
                };
            }
            return null;
        } catch (error) {
            console.error('Lỗi tìm kiếm địa chỉ:', error);
            return null;
        }
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
        let {
            orderStatus, trackingCode,
            driverLat, driverLng,
            senderLat, senderLng,
            receiverLat, receiverLng,
            onDriverLocationUpdate
        } = config;

        const sender = this.normalizeLatLng(senderLat, senderLng);
        const receiver = this.normalizeLatLng(receiverLat, receiverLng);
        const driver = this.normalizeLatLng(driverLat, driverLng);

        if (!sender || !receiver) {
            this.map.setView([16.463713, 107.590866], 13);
            L.popup().setLatLng([16.463713, 107.590866])
                .setContent("Đơn hàng này chưa có dữ liệu tọa độ.").openOn(this.map);
            return;
        }

        senderLat = sender.lat;
        senderLng = sender.lng;
        receiverLat = receiver.lat;
        receiverLng = receiver.lng;

        this.map.setView([senderLat, senderLng], 14);

        const createCustomMarkerIcon = (icon, color) => L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:${color};width:36px;height:36px;border-radius:50%;border:3px solid #fff;box-shadow:0 4px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;position:relative;"><span class="material-symbols-outlined marker-rotate-icon" style="color:#fff;font-size:20px;transition: transform 0.4s ease;">${icon}</span><div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);border-width:8px 6px 0;border-style:solid;border-color:#fff transparent transparent transparent;"></div><div style="position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border-width:6px 4px 0;border-style:solid;border-color:${color} transparent transparent transparent;"></div></div>`,
            iconSize: [36, 44], iconAnchor: [18, 44], popupAnchor: [0, -44]
        });

        const driverIcon = createCustomMarkerIcon('two_wheeler', '#2563eb');
        const senderIcon = createCustomMarkerIcon('storefront', '#f59e0b');
        const receiverIcon = createCustomMarkerIcon('location_on', '#10b981');

        let routingControl = null;
        let dLat = driver ? driver.lat : 0;
        let dLng = driver ? driver.lng : 0;
        let driverMarker = null; // Tách marker tài xế ra độc lập để không bị giật lag

        // BẢN VÁ: Ghim cứng 2 điểm Lấy và Giao hàng ngay từ đầu, không phụ thuộc vào tiến trình vẽ đường của OSRM
        L.marker([senderLat, senderLng], {icon: orderStatus === 'returning' ? receiverIcon : senderIcon})
            .bindPopup(`<b>${orderStatus === 'returning' ? 'Điểm hoàn hàng' : 'Điểm lấy hàng'}</b>`).addTo(this.map);
        L.marker([receiverLat, receiverLng], {icon: orderStatus === 'returning' ? senderIcon : receiverIcon})
            .bindPopup(`<b>Điểm giao hàng</b>`).addTo(this.map);

        // Canh vừa khung hình 2 điểm trước khi OSRM vẽ xong đường
        this.map.fitBounds(L.latLngBounds([senderLat, senderLng], [receiverLat, receiverLng]), { padding: [40, 40] });

        const drawRoute = () => {
            const waypoints = [];
            const hasDriverLoc = (dLat !== 0 && dLng !== 0);
            
            // Chỉ vẽ 1 tuyến đường cố định (Từ điểm lấy -> Điểm giao) để tránh gọi OSRM API liên tục
            if (orderStatus === 'returning') {
                waypoints.push(L.latLng(receiverLat, receiverLng));
                waypoints.push(L.latLng(senderLat, senderLng));
            } else {
                waypoints.push(L.latLng(senderLat, senderLng));
                waypoints.push(L.latLng(receiverLat, receiverLng));
            }

            if (routingControl) {
                routingControl.setWaypoints(waypoints);
            } else if (typeof L.Routing !== 'undefined') {
                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1', language: 'vi' }),
                    addWaypoints: false, routeWhileDragging: false, fitSelectedRoutes: false, show: false,
                    lineOptions: { styles: [{color: '#3b82f6', weight: 5}] },
                    createMarker: function() { return null; } // Tắt marker tự động của OSRM vì đã vẽ thủ công ở trên
                }).addTo(this.map);

                // BẢN VÁ: Tắt fitSelectedRoutes mặc định của OSRM và tự tính toán Bounds
                // Bao gồm cả tuyến đường (từ Điểm A -> B) và vị trí hiện tại của Tài xế
                routingControl.on('routesfound', (e) => {
                    const route = e.routes[0];
                    const bounds = L.latLngBounds(route.coordinates);
                    if (driverMarker) {
                        bounds.extend(driverMarker.getLatLng());
                    }
                    this.map.fitBounds(bounds, { padding: [40, 40] });
                });
            }

            // Cập nhật marker xe máy
            if (hasDriverLoc && ['accepted', 'picking_up', 'in_transit', 'shipping', 'returning'].includes(orderStatus)) {
                if (!driverMarker) {
                    driverMarker = L.marker([dLat, dLng], {icon: driverIcon, zIndexOffset: 1000})
                                    .bindPopup('<b>Vị trí tài xế</b>')
                                    .addTo(this.map);
                } else {
                    driverMarker.setLatLng([dLat, dLng]);
                }
            }
        };

        drawRoute();

        // Expose function để WebSockets gọi trực tiếp mà không tải lại toàn bộ đường đi
        this.updateDriverMarker = (lat, lng) => {
            const nextDriver = this.normalizeLatLng(lat, lng);
            
            if (nextDriver) {
                // Cập nhật hướng xoay của xe máy
                if (dLat !== 0 && dLng !== 0 && (dLat !== nextDriver.lat || dLng !== nextDriver.lng)) {
                    const angle = this.calculateBearing(dLat, dLng, nextDriver.lat, nextDriver.lng);
                    if (driverMarker && driverMarker._icon) {
                        const iconSpan = driverMarker._icon.querySelector('.marker-rotate-icon');
                        if (iconSpan) iconSpan.style.transform = `rotate(${angle}deg)`;
                    }
                }
                
                dLat = nextDriver.lat;
                dLng = nextDriver.lng;
                if (typeof onDriverLocationUpdate === 'function') {
                    onDriverLocationUpdate(dLat, dLng);
                }
                
                if (['accepted', 'picking_up', 'in_transit', 'shipping', 'returning'].includes(orderStatus)) {
                    if (driverMarker) {
                        // Bật CSS Transition để xe lướt mượt mà tới vị trí mới
                        if (driverMarker._icon) {
                            driverMarker._icon.style.transition = 'transform 1s linear';
                        }
                        driverMarker.setLatLng([dLat, dLng]);
                        
                        // Tắt Transition sau khi xe chạy xong để giữ yên vị trí khi người dùng Zoom bản đồ
                        setTimeout(() => {
                            if (driverMarker && driverMarker._icon) driverMarker._icon.style.transition = 'none';
                        }, 1000);
                    } else {
                        // Tự động sinh ra tài xế nếu lúc tải trang tọa độ bị rỗng (0)
                        driverMarker = L.marker([dLat, dLng], {icon: driverIcon, zIndexOffset: 1000}).bindPopup('<b>Vị trí tài xế</b>').addTo(this.map);
                        this.map.fitBounds(this.map.getBounds().extend([dLat, dLng]), { padding: [40, 40] });
                    }
                }
            }
        };

        // BẢN VÁ: Tắt transition ngay lập tức nếu Khách hàng lăn chuột thu phóng (zoom) hoặc kéo bản đồ
        this.map.on('zoomstart movestart', () => {
            if (driverMarker && driverMarker._icon) {
                driverMarker._icon.style.transition = 'none';
            }
        });

        if (['accepted', 'picking_up', 'in_transit', 'shipping', 'returning'].includes(orderStatus) && trackingCode) {
            this.trackingInterval = setInterval(async () => {
                try {
                    const res = await fetch('/api/orders/driver-location/' + trackingCode);
                    const data = await res.json();
                    if (data && data.success) {
                        this.updateDriverMarker(data.lat, data.lng);
                    }
                } catch(e) {}
            }, 10000);
        }

        // Phương thức để focus và highlight tài xế trên bản đồ
        this.focusDriver = () => {
            if (driverMarker && dLat && dLng) {
                this.map.flyTo([dLat, dLng], 16, { duration: 1.5 });
                driverMarker.openPopup();
                // Thêm hiệu ứng pulse cho marker
                driverMarker.setIcon(createCustomMarkerIcon('two_wheeler', '#dc2626'));
                setTimeout(() => {
                    driverMarker.setIcon(createCustomMarkerIcon('two_wheeler', '#2563eb'));
                }, 1000);
            }
        };
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
