<?php
// READ — Danh sách sản phẩm
require_once __DIR__ . '/lib/bootstrap.php';

$title    = 'Danh sách sản phẩm';
$msg      = flash_message();
$q        = '';
$products = product_all($conn);

require __DIR__ . '/views/header.php';
require __DIR__ . '/views/product_table.php';
require __DIR__ . '/views/footer.php';
