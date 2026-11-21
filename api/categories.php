<?php
// API danh mục
//
//   GET api/categories.php   danh sách danh mục + số sản phẩm (đăng nhập)
require_once dirname(__DIR__) . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Phương thức không được hỗ trợ.', 405);
}
require_login();
json_response(category_all($conn));
