<?php
// API quản lý người dùng (admin tối cao / quản trị / editor được cấp quyền) — xử lý trong App\Controllers\UserController
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\UserController;

(new UserController($app->request(), $app->auth(), $app->users(), $app->userValidator(), $app->superAdmins()))->handle();
