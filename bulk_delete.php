<?php
// DELETE nhiều — Xóa các sản phẩm đã chọn (chỉ nhận POST, ids[] = danh sách id)
require_once __DIR__ . '/lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$ids = (array)($_POST['ids'] ?? []);
if (!$ids) {
    redirect('index.php?msg=none_chosen');
}

$deleted = product_delete_many($conn, $ids);

redirect('index.php?msg=bulk_deleted&n=' . $deleted);
