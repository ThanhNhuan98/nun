document.addEventListener('DOMContentLoaded', function() {
    if (!window.OrderMapConfig) return;

    const {
        status, trackingCode,
        senderLat, senderLng,
        receiverLat, receiverLng,
        driverLat, driverLng
    } = window.OrderMapConfig;

    // Sử dụng chung lớp MapHelper để đồng bộ logic hiển thị map toàn hệ thống
    const myMapHelper = new MapHelper('order-map');
    myMapHelper.initTracking({
        orderStatus: status,
        trackingCode: trackingCode,
        driverLat: parseFloat(driverLat) || 0,
        driverLng: parseFloat(driverLng) || 0,
        senderLat: parseFloat(senderLat) || 0,
        senderLng: parseFloat(senderLng) || 0,
        receiverLat: parseFloat(receiverLat) || 0,
        receiverLng: parseFloat(receiverLng) || 0
    });
    
    // Cập nhật lại kích thước map cho admin sau khi load xong UI
    setTimeout(() => { if(myMapHelper.map) myMapHelper.map.invalidateSize(); }, 400);
});