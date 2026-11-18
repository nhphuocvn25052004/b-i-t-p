<?php
// CREATE — Thêm sản phẩm
require_once __DIR__ . '/lib/bootstrap.php';

$product = ['name' => '', 'price' => '', 'description' => ''];
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$product, $error] = product_from_request($_POST);
    if (!$error) {
        product_create($conn, $product);
        redirect('index.php?msg=added');
    }
}

$title      = 'Thêm sản phẩm';
$formAction = 'create.php';

require __DIR__ . '/views/header.php';
require __DIR__ . '/views/product_form.php'; 
require __DIR__ . '/views/footer.php';
