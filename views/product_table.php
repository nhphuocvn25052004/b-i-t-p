<?php
// Bảng sản phẩm dùng chung cho index.php và search.php
// Cần có: $products (danh sách sản phẩm), $q (từ khóa tìm kiếm, có thể rỗng)
?>
<div class="toolbar">
    <a href="create.php" class="btn btn-add">+ Thêm</a>

    <form method="get" action="search.php" class="search">
        <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Tìm theo tên hoặc mô tả...">
        <button type="submit" class="btn btn-search">Tìm</button>
        <?php if (($q ?? '') !== ''): ?>
            <a href="index.php" class="btn btn-cancel">Bỏ lọc</a>
        <?php endif; ?>
    </form>

    <!-- Form xóa nhiều: các checkbox trong bảng gắn vào form này bằng thuộc tính form="bulk-form" -->
    <form method="post" action="bulk_delete.php" id="bulk-form" class="bulk"
          onsubmit="return confirmBulkDelete();">
        <button type="submit" class="btn btn-delete">Xóa đã chọn</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th class="check"><input type="checkbox" id="check-all" title="Chọn tất cả"></th>
            <th>ID</th>
            <th>Tên sản phẩm</th>
            <th>Giá (VND)</th>
            <th>Mô tả</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $row): ?>
        <tr>
            <td class="check">
                <input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" form="bulk-form" class="row-check">
            </td>
            <td><?= (int)$row['id'] ?></td>
            <td><?= e($row['name']) ?></td>
            <td class="price"><?= number_format($row['price']) ?></td>
            <td><?= e($row['description']) ?></td>
            <td class="actions">
                <a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-edit">Sửa</a>
                <form method="post" action="delete.php"
                      onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" class="btn btn-delete">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?>
        <tr><td colspan="6"><?= ($q ?? '') !== '' ? 'Không tìm thấy sản phẩm phù hợp' : 'Không có dữ liệu' ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>

<script>
// Chọn / bỏ chọn tất cả
document.getElementById('check-all').addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});

function confirmBulkDelete() {
    const n = document.querySelectorAll('.row-check:checked').length;
    if (n === 0) {
        alert('Bạn chưa chọn sản phẩm nào.');
        return false;
    }
    return confirm('Bạn có chắc muốn xóa ' + n + ' sản phẩm đã chọn?');
}
</script>
