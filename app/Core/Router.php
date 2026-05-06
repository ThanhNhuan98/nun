<?php

namespace App\Core;

class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        // Nếu không khớp trực tiếp, kiểm tra các route động
        if ($callback === false) {
            foreach ($this->routes[$method] ?? [] as $route => $cb) {
                // TỐI ƯU: Bỏ qua các route tĩnh (không có tham số {}) vì đã kiểm tra không khớp ở trên
                if (strpos($route, '{') === false) {
                    continue;
                }

                $routeRegex = preg_replace('/\/\{([a-zA-Z0-9_]+)\}/', '/(?P<$1>[a-zA-Z0-9_-]+)', $route);
                $routeRegex = '#^' . $routeRegex . '$#';

                if (preg_match($routeRegex, $path, $matches)) {
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    // Gán các tham số đã tìm được vào đối tượng Request
                    $this->request->setRouteParams($params);
                    $callback = $cb;
                    break;
                }
            }
        }

        if ($callback === false) {
            $this->response->setStatusCode(404);
            echo $this->response->render('layouts/404'); // Sửa lỗi: Thêm echo để hiển thị trang 404
            return; // Dừng thực thi sau khi hiển thị 404
        }

        // Nếu callback là một mảng [Controller::class, 'methodName']
        if (is_array($callback)) {
            // Khởi tạo Object của Controller
            $callback[0] = new $callback[0](); 
        }

        // Gọi hàm xử lý trong Controller và truyền vào Request, Response
        $result = call_user_func($callback, $this->request, $this->response);
        if ($result !== null) {
            echo $result;
        }
    }
}
