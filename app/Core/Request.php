<?php

namespace App\Core;

class Request
{
    protected array $routeParams = [];

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

    public function getMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

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

    public function getJsonBody(): array
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            return [];
        }

        return $this->normalizeInput($payload);
    }

    private function normalizeInput($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'normalizeInput'], $value);
        }

        return $value;
    }

    public function setRouteParams(array $params)
    {
        $this->routeParams = $params;
        return $this;
    }

    public function getRouteParam(string $param, $default = null)
    {
        return $this->routeParams[$param] ?? $default;
    }
}
