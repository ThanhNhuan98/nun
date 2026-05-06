<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class HomeController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        return $response->render('home', [
            'pageTitle' => 'Trang chủ - Hệ thống giao hàng'
        ]);
    }
}