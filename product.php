<?php
// Model: mọi thao tác CSDL với bảng products

const PRODUCT_MAX_PRICE = 9999999999.99; // giới hạn của DECIMAL(12,2)

function product_all(mysqli $conn): array
{
    return $conn->query("SELECT * FROM products ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
}

function product_find(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

// Thêm mới: id = id lớn nhất hiện có + 1 (không bị nhảy số)
function product_create(mysqli $conn, array $data): void
{
    $stmt = $conn->prepare(
        "INSERT INTO products (id, name, price, description)
         SELECT COALESCE(MAX(id), 0) + 1, ?, ?, ? FROM products"
    );
    $stmt->bind_param("sds", $data['name'], $data['price'], $data['description']);
    $stmt->execute();
}

function product_update(mysqli $conn, int $id, array $data): void
{
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $data['name'], $data['price'], $data['description'], $id);
    $stmt->execute();
}

// Tìm theo tên hoặc mô tả (không phân biệt hoa thường)
function product_search(mysqli $conn, string $keyword): array
{
    // Escape ký tự đặc biệt của LIKE để tìm đúng nguyên văn
    $like = '%' . addcslashes($keyword, '\\%_') . '%';
    $stmt = $conn->prepare(
        "SELECT * FROM products
         WHERE name LIKE ? OR description LIKE ?
         ORDER BY id ASC"
    );
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Xóa 1 sản phẩm
function product_delete(mysqli $conn, int $id): void
{
    product_delete_many($conn, [$id]);
}

// Xóa nhiều sản phẩm. Sau khi xóa, các id phía sau lùi xuống để id luôn liên tục 1, 2, 3...
// Trả về số sản phẩm đã xóa.
function product_delete_many(mysqli $conn, array $ids): int
{
    // Chỉ giữ id hợp lệ, bỏ trùng; xóa từ id lớn đến nhỏ để việc dời id
    // không làm thay đổi các id còn chờ xóa
    $ids = array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
    rsort($ids);

    $deleted = 0;
    $conn->begin_transaction();
    try {
        $del   = $conn->prepare("DELETE FROM products WHERE id = ?");
        // ORDER BY id ASC để không bị trùng khóa chính khi dời id
        $shift = $conn->prepare("UPDATE products SET id = id - 1 WHERE id > ? ORDER BY id ASC");

        foreach ($ids as $id) {
            $del->bind_param("i", $id);
            $del->execute();
            if ($del->affected_rows > 0) {
                $deleted++;
                $shift->bind_param("i", $id);
                $shift->execute();
            }
        }

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    return $deleted;
}

// Lấy dữ liệu từ form và kiểm tra hợp lệ. Trả về [dữ liệu, lỗi]
function product_from_request(array $input): array
{
    $data = [
        'name'        => trim($input['name'] ?? ''),
        'price'       => $input['price'] ?? '',
        'description' => trim($input['description'] ?? ''),
    ];

    $error = '';
    if ($data['name'] === '' || !is_numeric($data['price']) || $data['price'] < 0) {
        $error = 'Vui lòng nhập tên và giá hợp lệ.';
    } elseif (preg_match('/^.{256,}/su', $data['name'])) { // quá 255 ký tự (tính cả tiếng Việt)
        $error = 'Tên sản phẩm tối đa 255 ký tự.';
    } elseif ($data['price'] > PRODUCT_MAX_PRICE) {
        // Cột price là DECIMAL(12,2)
        $error = 'Giá tối đa là ' . number_format(PRODUCT_MAX_PRICE, 2) . ' VND.';
    }

    return [$data, $error];
}
