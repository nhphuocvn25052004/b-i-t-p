<?php
// API quản lý người dùng (chỉ admin) — xử lý trong App\Controllers\UserController
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\UserController;

(new UserController($app->request(), $app->auth(), $app->users(), $app->userValidator()))->handle();
