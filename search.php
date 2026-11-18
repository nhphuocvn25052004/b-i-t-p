<?php
// SEARCH — Tìm kiếm sản phẩm theo tên hoặc mô tả (?q=từ khóa)
require_once __DIR__ . '/lib/bootstrap.php';

$q = trim($_GET['q'] ?? '');

// Không nhập từ khóa thì quay về danh sách đầy đủ
if ($q === '') {
    redirect('index.php');
}

$products = product_search($conn, $q);
$title    = 'Tìm kiếm: ' . $q;
$msg      = 'Tìm thấy ' . count($products) . ' sản phẩm.';

require __DIR__ . '/views/header.php';
require __DIR__ . '/views/product_table.php';
require __DIR__ . '/views/footer.php';
