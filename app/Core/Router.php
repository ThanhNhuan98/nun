<?php

namespace App\Core;

class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];
    protected array $middlewareGroups = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function group(array $middlewares, \Closure $callback)
    {
        $previousGroup = $this->middlewareGroups;
        $this->middlewareGroups = array_merge($this->middlewareGroups, $middlewares);
        
        $callback($this);
        
        $this->middlewareGroups = $previousGroup; // Khôi phục lại trạng thái cũ sau khi gom nhóm xong
    }

    public function get($path, $callback, array $middlewares = [])
    {
        $middlewares = array_merge($this->middlewareGroups, $middlewares);
        $this->routes['get'][$path] = ['callback' => $callback, 'middlewares' => $middlewares];
    }

    public function post($path, $callback, array $middlewares = [])
    {
        $middlewares = array_merge($this->middlewareGroups, $middlewares);
        $this->routes['post'][$path] = ['callback' => $callback, 'middlewares' => $middlewares];
    }

    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        $routeInfo = $this->routes[$method][$path] ?? false;

        // Nếu không khớp trực tiếp, kiểm tra các route động
        if ($routeInfo === false) {
            foreach ($this->routes[$method] ?? [] as $route => $info) {
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
                    $routeInfo = $info;
                    break;
                }
            }
        }

        if ($routeInfo === false) {
            $this->response->setStatusCode(404);
            echo $this->response->render('layouts/404'); // Sửa lỗi: Thêm echo để hiển thị trang 404
            return; // Dừng thực thi sau khi hiển thị 404
        }

        $callback = $routeInfo['callback'];
        $middlewares = $routeInfo['middlewares'] ?? [];

        // Nếu callback là một mảng [Controller::class, 'methodName']
        if (is_array($callback)) {
            // Khởi tạo Object của Controller
            $callback[0] = new $callback[0](); 
        }

        // Tạo hàm thực thi Controller cốt lõi
        $coreAction = function($req, $res) use ($callback) {
            $result = call_user_func($callback, $req, $res);
            if ($result !== null) {
                echo $result;
            }
        };

        // Bọc các Middleware xung quanh hàm Controller (Từ dưới lên trên để bọc ra ngoài)
        $pipeline = $coreAction;
        for ($i = count($middlewares) - 1; $i >= 0; $i--) {
            $middlewareEntry = $middlewares[$i];
            
            // Hỗ trợ khởi tạo Middleware từ chuỗi có tham số (VD: RoleMiddleware::class . ':admin')
            if (is_string($middlewareEntry)) {
                $parts = explode(':', $middlewareEntry, 2);
                $middlewareClass = $parts[0];
                $middlewareParam = $parts[1] ?? null;
                
                $middlewareInstance = $middlewareParam !== null 
                    ? new $middlewareClass($middlewareParam) 
                    : new $middlewareClass();
            } elseif (is_object($middlewareEntry)) {
                // Hỗ trợ truyền trực tiếp Object (VD: new RoleMiddleware('admin'))
                $middlewareInstance = $middlewareEntry;
            }
            $next = $pipeline; // Lưu lại trạng thái của hàm thực thi tiếp theo
            
            $pipeline = function($req, $res) use ($middlewareInstance, $next) {
                return $middlewareInstance->handle($req, $res, $next);
            };
        }

        // Bắt đầu luồng chạy (Request chạy qua Middleware -> Controller -> Trả về Response)
        $pipeline($this->request, $this->response);
    }
}
