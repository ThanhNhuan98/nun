<?php
/**
 * @var string $pageTitle
 * @var array $errors
 * @var array $old
 */
require_once __DIR__ . '/../../layouts/user_header.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />

<div class="admin-container">
    
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 700; color: var(--text-main); margin: 0 0 8px 0;"><?= app_e($pageTitle ?? 'Tạo Đơn Hàng Mới') ?></h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Điền thông tin chi tiết về địa điểm và gói hàng để tạo đơn.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-banner danger" style="background: var(--danger-light); color: var(--danger); padding: 16px; border-radius: 4px; border: 1px solid #fca5a5; margin-bottom: 24px;">
            <div style="font-weight: bold; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-outlined">error</span> Có lỗi xảy ra
            </div>
            <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= app_e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div id="draft-notice" style="display: none; background: #e0f2fe; color: #0284c7; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; border: 1px solid #bae6fd; font-size: 14px; align-items: center; justify-content: space-between;">
        <span style="display: flex; align-items: center; gap: 6px;"><span class="material-symbols-outlined" style="font-size: 18px;">restore_page</span> Đã khôi phục dữ liệu bạn đang nhập dở.</span>
        <button type="button" onclick="clearDraft()" style="background: none; border: none; color: #0284c7; font-weight: bold; cursor: pointer; text-decoration: underline;">Xóa nháp & Tạo mới</button>
    </div>

    <form action="/user/orders/create" method="POST" class="order-form">
        <div class="order-create-layout">
            
            <div class="order-form-col">
                
                <div class="order-card-v2">
                    <div class="order-card-header-v2">
                        <span class="material-symbols-outlined">location_on</span> Địa điểm
                    </div>
                    
                    <div class="location-timeline">
                        <div class="location-step">
                            <div class="location-step-dot blue"></div>
                            <div class="location-step-title">Lấy hàng tại</div>
                            
                            <input type="hidden" name="sender_lat" id="sender_lat" value="<?= app_e($old['sender_lat'] ?? '') ?>">
                            <input type="hidden" name="sender_lng" id="sender_lng" value="<?= app_e($old['sender_lng'] ?? '') ?>">
                            
                            <div class="form-grid-2">
                                <div>
                                    <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Tên người gửi</label>
                                    <input type="text" name="sender_name" class="form-control" placeholder="Nhập tên" value="<?= app_e($old['sender_name'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Số điện thoại</label>
                                    <input type="text" name="sender_phone" class="form-control" placeholder="Nhập SĐT" value="<?= app_e($old['sender_phone'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Địa chỉ chi tiết *</label>
                                <div class="form-input-with-icon" style="position: relative;">
                                    <span class="material-symbols-outlined" style="position: absolute; left: 10px; top: 10px; color: var(--text-muted); font-size: 18px;">search</span>
                                    <input type="text" name="pickup_address" id="pickup_address" class="form-control" style="padding-left: 36px;" placeholder="Tìm kiếm địa chỉ..." value="<?= app_e($old['pickup_address'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="location-step">
                            <div class="location-step-dot red"></div>
                            <div class="location-step-title">Giao hàng đến</div>
                            
                            <input type="hidden" name="receiver_lat" id="receiver_lat" value="<?= app_e($old['receiver_lat'] ?? '') ?>">
                            <input type="hidden" name="receiver_lng" id="receiver_lng" value="<?= app_e($old['receiver_lng'] ?? '') ?>">
                            
                            <div class="form-grid-2">
                                <div>
                                    <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Tên người nhận *</label>
                                    <input type="text" name="receiver_name" class="form-control" placeholder="Nhập tên" value="<?= app_e($old['receiver_name'] ?? '') ?>" required>
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Số điện thoại *</label>
                                    <input type="text" name="receiver_phone" class="form-control" placeholder="Nhập SĐT" value="<?= app_e($old['receiver_phone'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Địa chỉ chi tiết *</label>
                                <div class="form-input-with-icon" style="position: relative;">
                                    <span class="material-symbols-outlined" style="position: absolute; left: 10px; top: 10px; color: var(--text-muted); font-size: 18px;">search</span>
                                    <input type="text" name="delivery_address" id="delivery_address" class="form-control" style="padding-left: 36px;" placeholder="Tìm kiếm địa chỉ..." value="<?= app_e($old['delivery_address'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-card-v2">
                    <div class="order-card-header-v2">
                        <span class="material-symbols-outlined">inventory_2</span> Thông tin gói hàng
                    </div>
                    
                    <div>
                        <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Trọng lượng (kg) *</label>
                        <input type="number" step="0.1" min="0.1" name="weight" class="form-control" placeholder="0.0" value="<?= app_e($old['weight'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="order-card-v2">
                    <div class="order-card-header-v2">
                        <span class="material-symbols-outlined">more_horiz</span> Chi tiết thêm
                    </div>
                    
                    <div class="form-grid-2">
                        <div>
                            <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Lịch hẹn lấy hàng *</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="<?= app_e($old['scheduled_at'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Gói dịch vụ</label>
                            <select name="shipping_method" class="form-control">
                                <option value="standard" <?= app_selected($old['shipping_method'] ?? 'standard', 'standard') ?>>Giao tiêu chuẩn</option>
                                <option value="fast" <?= app_selected($old['shipping_method'] ?? '', 'fast') ?>>Giao nhanh</option>
                                <option value="express" <?= app_selected($old['shipping_method'] ?? '', 'express') ?>>Giao siêu tốc</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Phương thức thanh toán *</label>
                        <div class="radio-inline-group">
                            <label class="radio-inline-item">
                                <input type="radio" name="payment_method" value="cash" <?= app_checked($old['payment_method'] ?? 'cash', 'cash') ?>> Tiền mặt (Người nhận trả)
                            </label>
                            <label class="radio-inline-item">
                                <input type="radio" name="payment_method" value="transfer" <?= app_checked($old['payment_method'] ?? '', 'transfer') ?>> Chuyển khoản trước
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 12px; margin-bottom: 6px;">Ghi chú cho tài xế</label>
                        <textarea name="note" rows="3" class="form-control" placeholder="Ví dụ: Gọi trước khi đến, hẻm nhỏ..."><?= app_e($old['note'] ?? '') ?></textarea>
                    </div>
                </div>

            </div>

            <div class="order-summary-col">
                
                <div class="map-widget">
                    <div id="mapContainer"></div>
                    <div class="map-mode-overlay">
                        <button type="button" id="btnSenderMap" onclick="setMapMode('sender')" class="btn-map-toggle active">Đ. Lấy Hàng</button>
                        <button type="button" id="btnReceiverMap" onclick="setMapMode('receiver')" class="btn-map-toggle">Đ. Giao Hàng</button>
                    </div>
                </div>

                <div class="summary-widget">
                    <div class="summary-header">
                        <span class="summary-title">Tạm tính</span>
                        <span class="summary-badge">NUN AI</span>
                    </div>

                    <div class="summary-stats">
                        <div class="summary-stat-item">
                            <span class="summary-stat-label"><span class="material-symbols-outlined" style="font-size: 16px;">route</span> Khoảng cách</span>
                            <span id="previewDistance" class="summary-stat-value">-- km</span>
                        </div>
                        <div class="summary-stat-item">
                            <span class="summary-stat-label"><span class="material-symbols-outlined" style="font-size: 16px;">schedule</span> T.Gian dự kiến</span>
                            <span id="previewTime" class="summary-stat-value">-- phút</span>
                        </div>
                    </div>

                    <div class="summary-fee-row">
                        <span>Cước vận chuyển</span>
                        <span id="previewBaseFee">0 đ</span>
                    </div>
                    <div class="summary-fee-row">
                        <span>Phụ phí (Hỏa tốc/Giờ cao điểm)</span>
                        <span id="previewSurge">0 đ</span>
                    </div>
                    
                    <div class="summary-fee-row total">
                        <span>Tổng cộng</span>
                        <strong id="previewFee" class="fee-amount">0 đ</strong>
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="submit" id="submitBtn" class="btn-submit-order-large">
                            Tạo đơn hàng ngay <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span>
                        </button>
                        <button type="button" onclick="saveDraft(); document.getElementById('draft-notice').style.display = 'flex';" class="btn-save-draft">
                            Lưu nháp
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
    // ===== BẢN ĐỒ & GEOCODER (Giữ nguyên) =====
    let map = L.map('mapContainer').setView([16.4637, 107.5909], 13);
    L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    }).addTo(map);

    let markers = { sender: null, receiver: null };
    let currentMode = 'sender';

    const icons = {
        sender: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41]
        }),
        receiver: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41]
        })
    };

    function setMapMode(mode) {
        currentMode = mode;
        document.getElementById('btnSenderMap').classList.remove('active');
        document.getElementById('btnReceiverMap').classList.remove('active');
        document.getElementById(mode === 'sender' ? 'btnSenderMap' : 'btnReceiverMap').classList.add('active');
    }

    const geocoder = L.Control.geocoder({
        defaultMarkGeocode: false,
        placeholder: "Tìm địa chỉ...",
        geocoder: L.Control.Geocoder.nominatim({ geocodingQueryParams: { countrycodes: 'vn', limit: 5 } })
    }).on('markgeocode', function(e) {
        const lat = e.geocode.center.lat;
        const lng = e.geocode.center.lng;
        const address = e.geocode.name.replace(/, Việt Nam|, Vietnam/gi, '');
        updateMapLocation(lat, lng, address, currentMode);
        if(currentMode === 'sender' && !document.getElementById('receiver_lat').value) setMapMode('receiver');
        calculateFee();
    }).addTo(map);

    map.on('click', async function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        document.getElementById(currentMode + '_lat').value = lat.toFixed(6);
        document.getElementById(currentMode + '_lng').value = lng.toFixed(6);
        
        if (markers[currentMode]) map.removeLayer(markers[currentMode]);
        markers[currentMode] = L.marker([lat, lng], {icon: icons[currentMode]}).addTo(map);

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi&addressdetails=1`, { headers: { 'User-Agent': 'NUN-Express/1.0' } });
            const data = await response.json();
            if(data) {
                let fullAddress = '';
                if (data.address) {
                    let addr = data.address;
                    let addressParts = [];

                    if (addr.house_number && addr.road) addressParts.push(addr.house_number + ' ' + addr.road);
                    else if (addr.road) addressParts.push(addr.road);
                    
                    let localArea = addr.hamlet || addr.village || addr.suburb || addr.quarter || addr.neighbourhood || addr.residential;
                    if (localArea) addressParts.push(localArea);
                    
                    let ward = addr.city_district || addr.town || addr.county || addr.state_district;
                    if (ward && ward !== localArea) addressParts.push(ward);
                    
                    let city = addr.city || addr.state || addr.province;
                    if (city && city !== ward) addressParts.push(city);

                    fullAddress = addressParts.join(', ');
                }
                
                if (!fullAddress || fullAddress.split(',').length < 2) {
                    fullAddress = data.display_name ? data.display_name.replace(/, Việt Nam|, Vietnam/gi, '') : '';
                }

                document.getElementById(currentMode === 'sender' ? 'pickup_address' : 'delivery_address').value = fullAddress;
            }
        } catch (error) { console.error(error); }
        
        if(currentMode === 'sender' && !document.getElementById('receiver_lat').value) setMapMode('receiver');
        calculateFee();
    });

    function updateMapLocation(lat, lng, address, mode) {
        map.setView([lat, lng], 15);
        if (markers[mode]) map.removeLayer(markers[mode]);
        markers[mode] = L.marker([lat, lng], {icon: icons[mode]}).addTo(map);
        document.getElementById(mode + '_lat').value = lat.toFixed(6);
        document.getElementById(mode + '_lng').value = lng.toFixed(6);
        document.getElementById(mode === 'sender' ? 'pickup_address' : 'delivery_address').value = address;
    }

    async function geocodeAddress(address, mode) {
        if (!address || address.trim() === '') return;
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&countrycodes=vn&limit=1`, { headers: { 'User-Agent': 'NUN-Express/1.0' } });
            const data = await response.json();
            if (data && data.length > 0) {
                updateMapLocation(parseFloat(data[0].lat), parseFloat(data[0].lon), address, mode);
                calculateFee();
            }
        } catch (error) { console.error(error); }
    }

    document.getElementById('pickup_address').addEventListener('change', function(e) {
        setMapMode('sender'); geocodeAddress(e.target.value, 'sender');
    });
    document.getElementById('delivery_address').addEventListener('change', function(e) {
        setMapMode('receiver'); geocodeAddress(e.target.value, 'receiver');
    });

    // ===== LƯU NHÁP (AUTOSAVE) =====
    const draftKey = 'nun_order_draft';
    function saveDraft() {
        const formData = {
            sender_lat: document.getElementById('sender_lat').value,
            sender_lng: document.getElementById('sender_lng').value,
            pickup_address: document.getElementById('pickup_address').value,
            receiver_lat: document.getElementById('receiver_lat').value,
            receiver_lng: document.getElementById('receiver_lng').value,
            receiver_name: document.querySelector('input[name="receiver_name"]').value,
            receiver_phone: document.querySelector('input[name="receiver_phone"]').value,
            delivery_address: document.getElementById('delivery_address').value,
            weight: document.querySelector('input[name="weight"]').value,
            shipping_method: document.querySelector('select[name="shipping_method"]').value,
            scheduled_at: document.getElementById('scheduled_at').value,
            note: document.querySelector('textarea[name="note"]').value,
        };
        if (formData.pickup_address || formData.delivery_address) {
            localStorage.setItem(draftKey, JSON.stringify(formData));
        }
    }

    function clearDraft() { localStorage.removeItem(draftKey); window.location.reload(); }

    function loadDraft() {
        const hasError = document.querySelector('.alert-banner.danger') !== null;
        if (hasError) return;
        const saved = localStorage.getItem(draftKey);
        if (saved) {
            try {
                const data = JSON.parse(saved);
                if (data.pickup_address || data.delivery_address) {
                    if (data.sender_lat) document.getElementById('sender_lat').value = data.sender_lat;
                    if (data.sender_lng) document.getElementById('sender_lng').value = data.sender_lng;
                    if (data.pickup_address) document.getElementById('pickup_address').value = data.pickup_address;
                    if (data.receiver_lat) document.getElementById('receiver_lat').value = data.receiver_lat;
                    if (data.receiver_lng) document.getElementById('receiver_lng').value = data.receiver_lng;
                    if (data.receiver_name) document.querySelector('input[name="receiver_name"]').value = data.receiver_name;
                    if (data.receiver_phone) document.querySelector('input[name="receiver_phone"]').value = data.receiver_phone;
                    if (data.delivery_address) document.getElementById('delivery_address').value = data.delivery_address;
                    if (data.weight) document.querySelector('input[name="weight"]').value = data.weight;
                    if (data.shipping_method) document.querySelector('select[name="shipping_method"]').value = data.shipping_method;
                    if (data.scheduled_at) document.getElementById('scheduled_at').value = data.scheduled_at;
                    if (data.note) document.querySelector('textarea[name="note"]').value = data.note;
                    
                    if (data.sender_lat && data.pickup_address) updateMapLocation(parseFloat(data.sender_lat), parseFloat(data.sender_lng), data.pickup_address, 'sender');
                    if (data.receiver_lat && data.delivery_address) { updateMapLocation(parseFloat(data.receiver_lat), parseFloat(data.receiver_lng), data.delivery_address, 'receiver'); setMapMode('receiver'); }
                    document.getElementById('draft-notice').style.display = 'flex';
                }
            } catch (e) { console.error(e); }
        }
    }

    document.querySelector('.order-form').addEventListener('input', saveDraft);
    document.querySelector('.order-form').addEventListener('change', saveDraft);

    // ===== TÍNH CƯỚC AI =====
    async function calculateFee() {
        const senderLat = document.getElementById('sender_lat').value;
        const senderLng = document.getElementById('sender_lng').value;
        const receiverLat = document.getElementById('receiver_lat').value;
        const receiverLng = document.getElementById('receiver_lng').value;
        const weight = document.querySelector('input[name="weight"]').value;
        const shippingMethod = document.querySelector('select[name="shipping_method"]').value;

        if (senderLat && senderLng && receiverLat && receiverLng && weight > 0) {
            document.getElementById('previewFee').textContent = 'Đang tính...';
            try {
                const response = await fetch('/api/orders/calculate-fee', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sender_lat: senderLat, sender_lng: senderLng, receiver_lat: receiverLat, receiver_lng: receiverLng, weight: weight, shipping_method: shippingMethod })
                });
                
                const data = await response.json();
                if (data && data.success) {
                    const fmt = new Intl.NumberFormat('vi-VN');
                    document.getElementById('previewDistance').textContent = data.distance + ' km';
                    document.getElementById('previewTime').textContent = Math.round(data.distance * 3) + ' - ' + Math.round(data.distance * 5); // Tạm giả lập thời gian từ khoảng cách
                    
                    document.getElementById('previewBaseFee').textContent = fmt.format(data.fee) + ' đ';
                    let surgeFee = (data.surge_multiplier > 1.0) ? (data.fee * (data.surge_multiplier - 1)) : 0;
                    document.getElementById('previewSurge').textContent = fmt.format(surgeFee) + ' đ';
                    document.getElementById('previewFee').textContent = fmt.format(data.fee + surgeFee) + ' đ';
                }
            } catch (e) { console.error("Tính cước thất bại", e); }
        }
    }

    let feeTimeout = null;
    function debouncedCalculateFee() {
        clearTimeout(feeTimeout);
        feeTimeout = setTimeout(calculateFee, 500);
    }

    document.querySelector('input[name="weight"]').addEventListener('input', debouncedCalculateFee);
    document.querySelector('select[name="shipping_method"]').addEventListener('change', calculateFee);
    
    document.querySelector('.order-form').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true; btn.innerHTML = 'Đang xử lý...';
        localStorage.removeItem(draftKey);
    });
    
    document.addEventListener('DOMContentLoaded', () => {
        loadDraft(); calculateFee();
        
        // Cài đặt ngày tối thiểu và tối đa cho lịch hẹn lấy hàng
        const scheduledAtInput = document.getElementById('scheduled_at');
        if (scheduledAtInput) {
            const now = new Date();
            const formatDateTime = (date) => {
                const pad = (n) => n.toString().padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            };
            
            scheduledAtInput.min = formatDateTime(now);
            scheduledAtInput.max = formatDateTime(new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000));
        }
    });
</script>

<?php require_once __DIR__ . '/../../layouts/user_footer.php'; ?>