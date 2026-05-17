<?php

use App\Controllers\User\OrderController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\Driver\OrderController as DriverOrderController; 
use App\Controllers\Admin\AdminController; 
use App\Controllers\Admin\UserController; 
use App\Controllers\Admin\SettingController;

/** @var \App\Core\Router $router */


$router->get('/', [\App\Controllers\HomeController::class, 'index']);
$router->get('/tracking', [\App\Controllers\TrackingController::class, 'index']);


// --- AUTH ROUTES ---
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/register', [AuthController::class, 'showRegisterForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/auth/verify', [AuthController::class, 'showVerifyForm']);
$router->post('/auth/verify', [AuthController::class, 'verify']);

// --- FORGOT PASSWORD ROUTES ---
$router->get('/forgot-password', [AuthController::class, 'showForgotPasswordForm']);
$router->post('/request-otp', [AuthController::class, 'requestOTP']);
$router->get('/verify-otp', [AuthController::class, 'showVerifyOTPForm']);
$router->post('/verify-otp', [AuthController::class, 'verifyOTP']);
$router->get('/reset-password', [AuthController::class, 'showResetPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// --- PROFILE ROUTE ---
$router->get('/profile/{id}', [ProfileController::class, 'show']);
$router->post('/profile/update-avatar', [ProfileController::class, 'updateAvatar']);
$router->post('/profile/update-info', [ProfileController::class, 'updateInfo']);
$router->post('/profile/change-password', [ProfileController::class, 'changePassword']);
$router->get('/notifications/read', [ProfileController::class, 'readAllNotifications']);

// --- USER ROUTES ---
$router->get('/user/dashboard', [OrderController::class, 'index']);
$router->get('/user/orders', [OrderController::class, 'index']);
// Quản lý đơn hàng
$router->get('/user/orders/create', [OrderController::class, 'create']);
$router->post('/user/orders/create', [OrderController::class, 'store']);
$router->get('/user/orders/track/{code}', [OrderController::class, 'track']);
$router->post('/user/orders/cancel/{code}', [OrderController::class, 'cancel']);
$router->get('/user/orders/payment/{code}', [OrderController::class, 'payment']);
$router->post('/user/orders/payment/{code}', [OrderController::class, 'processPayment']);
$router->get('/api/orders/driver-location/{code}', [OrderController::class, 'apiDriverLocation']);
$router->post('/api/orders/calculate-fee', [OrderController::class, 'apiCalculateFee']);
$router->get('/user/orders/review/{id}', [OrderController::class, 'review']);
$router->post('/user/orders/review/{id}', [OrderController::class, 'storeReview']);
$router->post('/user/orders/dispute/{code}', [OrderController::class, 'dispute']);
$router->post('/user/orders/withdraw-dispute/{code}', [OrderController::class, 'withdrawDispute']);

// --- DRIVER ROUTES ---
$router->post('/profile/register-driver', [ProfileController::class, 'registerDriver']);
$router->get('/driver/receive-orders', [DriverOrderController::class, 'receiveOrders']);
$router->post('/driver/receive-orders', [DriverOrderController::class, 'acceptOrder']);
$router->get('/driver/wallet/topup', [\App\Controllers\Driver\WalletController::class, 'topup']);
$router->post('/driver/wallet/topup', [\App\Controllers\Driver\WalletController::class, 'topup']);
$router->get('/driver/wallet/withdraw', [\App\Controllers\Driver\WithdrawalController::class, 'index']);
$router->post('/driver/wallet/withdraw', [\App\Controllers\Driver\WithdrawalController::class, 'store']);
$router->get('/driver/history', [DriverOrderController::class, 'history']);
$router->get('/driver/active-orders', [DriverOrderController::class, 'activeOrders']);
$router->get('/driver/orders/view/{id}', [DriverOrderController::class, 'viewOrder']);
$router->post('/driver/orders/update-status/{id}', [DriverOrderController::class, 'updateStatus']);
$router->post('/api/driver/update-location', [DriverOrderController::class, 'updateLocation']);
$router->get('/api/driver/check-new-orders', [DriverOrderController::class, 'apiCheckNewOrders']);

// --- CHAT ROUTES ---
$router->get('/api/chat/{order_id}', [\App\Controllers\ChatController::class, 'getMessages']);
$router->post('/api/chat/{order_id}', [\App\Controllers\ChatController::class, 'sendMessage']);
$router->post('/driver/orders/report-noshow/{id}', [DriverOrderController::class, 'reportNoShow']);

// --- ADMIN ROUTES ---
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
// NEW ADMIN ROUTES START
$router->get('/admin/users', [UserController::class, 'index']);
// Quản lý đơn hàng
$router->get('/admin/orders', [AdminController::class, 'orders']);
$router->get('/admin/orders/view/{id}', [AdminController::class, 'viewOrder']);
$router->post('/admin/orders/view/{id}', [AdminController::class, 'viewOrder']);
$router->post('/admin/orders/penalize-driver/{id}', [AdminController::class, 'penalizeDriver']);
$router->get('/admin/settings', [SettingController::class, 'index']);
$router->post('/admin/settings', [SettingController::class, 'index']);
// Đường dẫn thêm mới
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users/create', [UserController::class, 'create']);

// Các đường dẫn động (có truyền ID)
$router->get('/admin/users/edit/{id}', [UserController::class, 'edit']);
$router->post('/admin/users/edit/{id}', [UserController::class, 'edit']);
$router->get('/admin/users/block/{id}', [UserController::class, 'toggleBlock']);
$router->get('/admin/users/unblock/{id}', [UserController::class, 'toggleBlock']);

// --- ROUTE CHO CÁC TÍNH NĂNG MỚI CỦA ADMIN ---

// 1. Route cho trang "Các công việc"
$router->get('/admin/tasks', [AdminController::class, 'tasks']);

// 2. Route cho "Quản lý Khiếu nại"
$router->get('/admin/disputes', [\App\Controllers\Admin\DisputeController::class, 'index']);
$router->get('/admin/disputes/view/{id}', [\App\Controllers\Admin\DisputeController::class, 'view']);
$router->post('/admin/disputes/view/{id}', [\App\Controllers\Admin\DisputeController::class, 'view']);

// 3. Route cho "Chỉnh sửa đơn hàng"
$router->get('/admin/orders/edit/{id}', [AdminController::class, 'editOrder']);
$router->post('/admin/orders/edit/{id}', [AdminController::class, 'editOrder']);

// --- CRON JOB ROUTE ---
$router->get('/cron/auto-reassign', [\App\Controllers\CronController::class, 'autoReassign']);
$router->get('/notifications', [\App\Controllers\NotificationController::class, 'index']);
