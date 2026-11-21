<?php
// API danh mục — xử lý trong App\Controllers\CategoryController
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\CategoryController;

(new CategoryController($app->request(), $app->auth(), $app->categories()))->handle();
