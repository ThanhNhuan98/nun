<?php

namespace App\Core;

class Request
{
    protected array $routeParams = [];

    // Lấy đường dẫn URL hiện tại từ Request, tự động loại bỏ thư mục gốc XAMPP và query string.
    public function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        // Tự động loại bỏ thư mục gốc nếu chạy qua XAMPP (VD: /nun_ai/public)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && $scriptName !== '\\' && strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }

        return empty($path) ? '/' : $path;
    }

    // Lấy phương thức HTTP (GET, POST, PUT, DELETE) của Request hiện tại (dạng chữ thường).
    public function getMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    // Kiểm tra xem Request hiện tại có phải là POST không.
    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    // Kiểm tra xem Request hiện tại có phải là GET không.
    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

    // Lấy toàn bộ dữ liệu từ GET hoặc POST và tiến hành chuẩn hóa.
    public function getBody()
    {
        if ($this->getMethod() === 'get') {
            return $this->normalizeInput($_GET);
        }

        if ($this->getMethod() === 'post') {
            return $this->normalizeInput($_POST);
        }

        return [];
    }

    // Phân tích và lấy dữ liệu JSON thô từ body của Request (Thường dùng cho Web API).
    public function getJsonBody(): array
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            return [];
        }

        return $this->normalizeInput($payload);
    }

    // Chuẩn hóa dữ liệu đầu vào (loại bỏ khoảng trắng) và hỗ trợ xử lý đệ quy cho mảng.
    private function normalizeInput($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'normalizeInput'], $value);
        }

        return $value;
    }

    // Lưu trữ mảng các tham số động trên URL (Ví dụ: id trong /user/{id}).
    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
        return $this;
    }

    // Lấy giá trị của một tham số URL động cụ thể (Trả về mặc định nếu không có).
    public function getRouteParam(string $param, $default = null)
    {
        return $this->routeParams[$param] ?? $default;
    }
}
