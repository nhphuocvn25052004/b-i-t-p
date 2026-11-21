<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * Mọi thao tác CSDL với bảng products.
 *
 * Quy ước id: id luôn liên tục 1, 2, 3... — thêm mới lấy id lớn nhất + 1,
 * xóa thì các id phía sau lùi xuống. Vì vậy KHÔNG dùng products.id làm khóa ngoại ở bảng khác.
 */
class ProductRepository
{
    public const MAX_PRICE    = 9999999999.99; // giới hạn của DECIMAL(12,2)
    public const PER_PAGE_MAX = 100;

    // Phần SELECT dùng chung cho danh sách và xem 1 sản phẩm (kèm tên danh mục)
    private const SELECT = "SELECT p.id, p.name, p.price, p.description, p.category_id, c.name AS category_name
                            FROM products p
                            LEFT JOIN categories c ON c.id = p.category_id";

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Danh sách sản phẩm có tìm kiếm, lọc theo danh mục và phân trang.
     *   $filters['q']        từ khóa tìm trong tên / mô tả (không phân biệt hoa thường, dấu)
     *   $filters['category'] ''     = tất cả
     *                        'none' = chưa phân loại (category_id IS NULL)
     *                        số     = id danh mục
     * Trang vượt quá số trang thì trả về trang cuối.
     *
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $types, $params] = $this->buildWhere($filters);

        $perPage    = max(1, min(self::PER_PAGE_MAX, $perPage));
        $total      = (int)$this->db->query("SELECT COUNT(*) AS n FROM products p $whereSql", $types, $params)
                                    ->fetch_assoc()['n'];
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = max(1, min($page, $totalPages));

        $items = $this->db->query(
            self::SELECT . " $whereSql ORDER BY p.id ASC LIMIT ? OFFSET ?",
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

    // 1 sản phẩm theo id, không có thì null
    public function find(int $id): ?array
    {
        return $this->db->query(self::SELECT . " WHERE p.id = ?", 'i', [$id])->fetch_assoc() ?: null;
    }

    /**
     * Thêm mới: id = id lớn nhất hiện có + 1 (không bị nhảy số).
     * @param array $data đã qua ProductValidator: name, price, description, category_id
     */
    public function create(array $data): void
    {
        $this->db->query(
            "INSERT INTO products (id, name, price, description, category_id)
             SELECT COALESCE(MAX(id), 0) + 1, ?, ?, ?, ? FROM products",
            'sdsi',
            [$data['name'], $data['price'], $data['description'], $data['category_id']]
        );
    }

    /** @param array $data đã qua ProductValidator */
    public function update(int $id, array $data): void
    {
        $this->db->query(
            "UPDATE products SET name = ?, price = ?, description = ?, category_id = ? WHERE id = ?",
            'sdsii',
            [$data['name'], $data['price'], $data['description'], $data['category_id'], $id]
        );
    }

    /**
     * Xóa nhiều sản phẩm trong 1 transaction. Sau khi xóa, các id phía sau lùi xuống
     * để id luôn liên tục. Trả về số sản phẩm đã xóa.
     */
    public function deleteMany(array $ids): int
    {
        // Chỉ giữ id hợp lệ, bỏ trùng; xóa từ id lớn đến nhỏ để việc dời id
        // không làm thay đổi các id còn chờ xóa
        $ids = array_unique(array_filter(array_map('intval', $ids), function (int $id) {
            return $id > 0;
        }));
        rsort($ids);

        return $this->db->transaction(function (Database $db) use ($ids) {
            $del   = $db->prepare("DELETE FROM products WHERE id = ?");
            // ORDER BY id ASC để không bị trùng khóa chính khi dời id
            $shift = $db->prepare("UPDATE products SET id = id - 1 WHERE id > ? ORDER BY id ASC");

            $deleted = 0;
            foreach ($ids as $id) {
                $del->bind_param('i', $id);
                $del->execute();
                if ($del->affected_rows > 0) {
                    $deleted++;
                    $shift->bind_param('i', $id);
                    $shift->execute();
                }
            }
            return $deleted;
        });
    }

    /**
     * Ghép điều kiện WHERE từ bộ lọc.
     * @return array{0: string, 1: string, 2: array} [câu WHERE, kiểu tham số, tham số]
     */
    private function buildWhere(array $filters): array
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
        return [$whereSql, $types, $params];
    }
}
