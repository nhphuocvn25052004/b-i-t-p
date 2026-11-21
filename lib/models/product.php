<?php
// Model: mọi thao tác CSDL với bảng products

const PRODUCT_MAX_PRICE    = 9999999999.99; // giới hạn của DECIMAL(12,2)
const PRODUCT_PER_PAGE_MAX = 100;

// Phần SELECT dùng chung cho danh sách và xem 1 sản phẩm (kèm tên danh mục)
const PRODUCT_SELECT = "SELECT p.id, p.name, p.price, p.description, p.category_id, c.name AS category_name
                        FROM products p
                        LEFT JOIN categories c ON c.id = p.category_id";

// Danh sách sản phẩm có tìm kiếm, lọc theo danh mục và phân trang.
//   $filters['q']        từ khóa tìm trong tên / mô tả (không phân biệt hoa thường, dấu)
//   $filters['category'] ''     = tất cả
//                        'none' = chưa phân loại (category_id IS NULL)
//                        số     = id danh mục
// Trang vượt quá số trang thì trả về trang cuối.
function product_list(mysqli $conn, array $filters, int $page, int $perPage): array
{
    $where  = [];
    $types  = '';
    $params = [];

    $q = trim((string)($filters['q'] ?? ''));
    if ($q !== '') {
        // Escape ký tự đặc biệt của LIKE để tìm đúng nguyên văn
        $like     = '%' . addcslashes($q, '\\%_') . '%';
        $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
        $types   .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    $category = (string)($filters['category'] ?? '');
    if ($category === 'none') {
        $where[] = 'p.category_id IS NULL';
    } elseif ((int)$category > 0) {
        $where[]  = 'p.category_id = ?';
        $types   .= 'i';
        $params[] = (int)$category;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $perPage    = max(1, min(PRODUCT_PER_PAGE_MAX, $perPage));
    $total      = (int)db_query($conn, "SELECT COUNT(*) AS n FROM products p $whereSql", $types, $params)
                        ->fetch_assoc()['n'];
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = max(1, min($page, $totalPages));

    $items = db_query(
        $conn,
        PRODUCT_SELECT . " $whereSql ORDER BY p.id ASC LIMIT ? OFFSET ?",
        $types . 'ii',
        array_merge($params, [$perPage, ($page - 1) * $perPage])
    )->fetch_all(MYSQLI_ASSOC);

    return [
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
    ];
}

function product_find(mysqli $conn, int $id): ?array
{
    return db_query($conn, PRODUCT_SELECT . " WHERE p.id = ?", 'i', [$id])->fetch_assoc() ?: null;
}

// Thêm mới: id = id lớn nhất hiện có + 1 (không bị nhảy số)
function product_create(mysqli $conn, array $data): void
{
    db_query(
        $conn,
        "INSERT INTO products (id, name, price, description, category_id)
         SELECT COALESCE(MAX(id), 0) + 1, ?, ?, ?, ? FROM products",
        'sdsi',
        [$data['name'], $data['price'], $data['description'], $data['category_id']]
    );
}

function product_update(mysqli $conn, int $id, array $data): void
{
    db_query(
        $conn,
        "UPDATE products SET name = ?, price = ?, description = ?, category_id = ? WHERE id = ?",
        'sdsii',
        [$data['name'], $data['price'], $data['description'], $data['category_id'], $id]
    );
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

// Lấy dữ liệu từ body JSON và kiểm tra hợp lệ. Trả về [dữ liệu, lỗi]
function product_validate(mysqli $conn, array $input): array
{
    $categoryId = $input['category_id'] ?? null;
    $data = [
        'name'        => string_field($input, 'name'),
        'price'       => is_scalar($input['price'] ?? null) ? $input['price'] : '',
        'description' => string_field($input, 'description'),
        // '' / null / 0 = chưa phân loại
        'category_id' => is_numeric($categoryId) && (int)$categoryId > 0 ? (int)$categoryId : null,
    ];

    $error = '';
    if ($data['name'] === '' || !is_numeric($data['price']) || $data['price'] < 0) {
        $error = 'Vui lòng nhập tên và giá hợp lệ.';
    } elseif (preg_match('/^.{256,}/su', $data['name'])) { // quá 255 ký tự (tính cả tiếng Việt)
        $error = 'Tên sản phẩm tối đa 255 ký tự.';
    } elseif ($data['price'] > PRODUCT_MAX_PRICE) {
        $error = 'Giá tối đa là ' . number_format(PRODUCT_MAX_PRICE, 2) . ' VND.';
    } elseif ($data['category_id'] !== null && !category_exists($conn, $data['category_id'])) {
        $error = 'Danh mục không tồn tại.';
    }

    return [$data, $error];
}
