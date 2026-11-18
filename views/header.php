<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Sản phẩm') ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<h2><?= e($title ?? 'Sản phẩm') ?></h2>

<?php if (!empty($msg)): ?>
    <div class="alert alert-ok"><?= e($msg) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-err"><?= e($error) ?></div>
<?php endif; ?>
