<?php
// API sản phẩm — xử lý trong App\Controllers\ProductController
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\ProductController;

(new ProductController($app->request(), $app->auth(), $app->products(), $app->productValidator()))->handle();
