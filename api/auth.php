<?php
// API đăng nhập / đăng xuất — xử lý trong App\Controllers\AuthController
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;

(new AuthController($app->request(), $app->auth()))->handle();
