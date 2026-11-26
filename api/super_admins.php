<?php
// API quản lý admin tối cao (chỉ admin tối cao) — xử lý trong App\Controllers\SuperAdminController
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\SuperAdminController;

(new SuperAdminController($app->request(), $app->auth(), $app->superAdmins(), $app->userValidator()))->handle();
