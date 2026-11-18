<?php
// UPDATE — Sửa sản phẩm
require_once __DIR__ . '/lib/bootstrap.php';

$id      = (int)($_GET['id'] ?? 0);
$product = product_find($conn, $id);
$error   = '';

if (!$product) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$product, $error] = product_from_request($_POST);
    if (!$error) {
        product_update($conn, $id, $product);
        redirect('index.php?msg=updated');
    }
}

$title      = 'Sửa sản phẩm #' . $id;
$formAction = 'edit.php?id=' . $id;

require __DIR__ . '/views/header.php';
require __DIR__ . '/views/product_form.php';
require __DIR__ . '/views/footer.php';
