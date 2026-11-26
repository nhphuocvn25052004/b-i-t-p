<?php
namespace App\Repositories;

use App\Auth\DataScope;
use App\Core\Database;

/**
 * Mọi thao tác CSDL với bảng products.
 *
 * Quy ước id: id luôn liên tục 1, 2, 3... — thêm mới lấy id lớn nhất + 1,
 * xóa thì các id phía sau lùi xuống. Vì vậy KHÔNG dùng products.id làm khóa ngoại ở bảng khác.
 *
 * Tham số $owners (xem App\Auth\DataScope): null = mọi sản phẩm, mảng id = chỉ sản phẩm
 * do các tài khoản đó thêm (cột created_by).
 */
class ProductRepository
{
    public const MAX_PRICE    = 9999999999.99; // giới hạn của DECIMAL(12,2)
    public const PER_PAGE_MAX = 100;

    /**
     * Bảng tạm "p" = các sản phẩm trong phạm vi (điều kiện %s), kèm tên danh mục và
     * seq = số thứ tự 1, 2, 3... TRONG PHẠM VI ĐÓ (theo id). Nhờ vậy mỗi quản trị thấy sản phẩm
     * của mình đánh số liên tục từ 1 đến n, dù id thật trong bảng nằm rải rác.
     * seq tính TRƯỚC khi tìm kiếm / lọc danh mục → lọc không làm đổi số.
     * id thật vẫn dùng cho sửa / xóa; seq chỉ để hiển thị.
     */
    private const SCOPED = "(SELECT p.id, p.name, p.price, p.description, p.category_id, c.name AS category_name,
                                    ROW_NUMBER() OVER (ORDER BY p.id) AS seq
                             FROM products p
                             LEFT JOIN categories c ON c.id = p.category_id
                             %s) p";

    private const COLUMNS = "p.id, p.seq, p.name, p.price, p.description, p.category_id, p.category_name";

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
     * @param int[]|null $owners
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage, ?array $owners = null): array
    {
        [$from, $scopeTypes, $scopeParams] = self::scopedFrom($owners);
        [$whereSql, $filterTypes, $filterParams] = $this->buildWhere($filters);
        $types  = $scopeTypes . $filterTypes;
        $params = array_merge($scopeParams, $filterParams);

        $perPage    = max(1, min(self::PER_PAGE_MAX, $perPage));
        $total      = (int)$this->db->query("SELECT COUNT(*) AS n FROM $from $whereSql", $types, $params)
                                    ->fetch_assoc()['n'];
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = max(1, min($page, $totalPages));

        $items = $this->db->query(
            "SELECT " . self::COLUMNS . " FROM $from $whereSql ORDER BY p.seq ASC LIMIT ? OFFSET ?",
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

    /**
     * 1 sản phẩm theo id thật (kèm seq trong phạm vi), không có hoặc ngoài phạm vi $owners thì null.
     * @param int[]|null $owners
     */
    public function find(int $id, ?array $owners = null): ?array
    {
        [$from, $types, $params] = self::scopedFrom($owners);
        return $this->db->query(
            "SELECT " . self::COLUMNS . " FROM $from WHERE p.id = ?",
            $types . 'i',
            array_merge($params, [$id])
        )->fetch_assoc() ?: null;
    }

    /**
     * Thêm mới: id = id lớn nhất hiện có + 1 (không bị nhảy số).
     * @param array $data đã qua ProductValidator: name, price, description, category_id
     * @param int|null $createdBy người thêm (null = admin tối cao)
     */
    public function create(array $data, ?int $createdBy = null): void
    {
        $this->db->query(
            "INSERT INTO products (id, name, price, description, category_id, created_by)
             SELECT COALESCE(MAX(id), 0) + 1, ?, ?, ?, ?, ? FROM products",
            'sdsii',
            [$data['name'], $data['price'], $data['description'], $data['category_id'], $createdBy]
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
     * Sản phẩm ngoài phạm vi $owners bị bỏ qua (không xóa, không đếm).
     *
     * @param int[]|null $owners
     */
    public function deleteMany(array $ids, ?array $owners = null): int
    {
        // Chỉ giữ id hợp lệ, bỏ trùng
        $ids = array_unique(array_filter(array_map('intval', $ids), function (int $id) {
            return $id > 0;
        }));
        // Chỉ giữ sản phẩm trong phạm vi
        if ($owners !== null) {
            [$scope, $types, $params] = DataScope::sqlFilter('created_by', $owners);
            $ids = array_filter($ids, function (int $id) use ($scope, $types, $params) {
                return (bool)$this->db->query(
                    "SELECT 1 FROM products WHERE id = ? AND $scope",
                    'i' . $types,
                    array_merge([$id], $params)
                )->fetch_row();
            });
        }
        // Xóa từ id lớn đến nhỏ để việc dời id không làm thay đổi các id còn chờ xóa
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

    /**
     * Phần FROM: bảng tạm "p" gồm sản phẩm trong phạm vi $owners, có cột seq (xem SCOPED).
     * @param int[]|null $owners
     * @return array{0: string, 1: string, 2: array} [phần FROM, kiểu tham số, tham số]
     */
    private static function scopedFrom(?array $owners): array
    {
        [$scope, $types, $params] = DataScope::sqlFilter('p.created_by', $owners);
        return [sprintf(self::SCOPED, $scope !== '' ? "WHERE $scope" : ''), $types, $params];
    }
}
