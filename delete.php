<?php
// DELETE — Xóa sản phẩm (chỉ nhận POST)
require_once __DIR__ . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    product_delete($conn, $id);
}

redirect('index.php?msg=deleted');
