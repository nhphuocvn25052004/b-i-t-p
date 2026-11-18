<?php
// Form dùng chung cho Thêm và Sửa
// Cần có: $product (mảng name/price/description), $formAction (URL gửi form)
?>
<div class="box">
    <form method="post" action="<?= e($formAction) ?>">
        <label for="name">Tên sản phẩm</label>
        <input type="text" id="name" name="name" required maxlength="255"
               value="<?= e($product['name']) ?>">

        <label for="price">Giá (VND)</label>
        <input type="number" id="price" name="price" required min="0" max="<?= PRODUCT_MAX_PRICE ?>" step="0.01"
               value="<?= e($product['price']) ?>">

        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3"><?= e($product['description']) ?></textarea>

        <div class="buttons">
            <button type="submit" class="btn btn-add">Lưu</button>
            <a href="index.php" class="btn btn-cancel">Hủy</a>
        </div>
    </form>
</div>
