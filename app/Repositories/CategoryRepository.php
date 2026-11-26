<?php
namespace App\Repositories;

use App\Auth\DataScope;
use App\Core\Database;

/**
 * Thao tác CSDL với bảng categories (danh mục sản phẩm).
 * Mỗi quản trị có bộ danh mục riêng (cột owner_id). Tham số $owners: xem App\Auth\DataScope.
 */
class CategoryRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Danh mục trong phạm vi $categoryOwners, kèm số sản phẩm (chỉ đếm sản phẩm trong phạm vi
     * $productOwners) và tên quản trị sở hữu (owner_name), sắp theo tên.
     * @param int[]|null $categoryOwners
     * @param int[]|null $productOwners
     */
    public function allWithProductCount(?array $categoryOwners = null, ?array $productOwners = null): array
    {
        [$productScope, $pTypes, $pParams] = DataScope::sqlFilter('p.created_by', $productOwners);
        [$categoryScope, $cTypes, $cParams] = DataScope::sqlFilter('c.owner_id', $categoryOwners);

        return $this->db->query(
            "SELECT c.id, c.name, c.owner_id, o.username AS owner_name, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN users o ON o.id = c.owner_id
             LEFT JOIN products p ON p.category_id = c.id" . ($productScope !== '' ? " AND $productScope" : '') . "
             " . ($categoryScope !== '' ? "WHERE $categoryScope" : '') . "
             GROUP BY c.id, c.name, c.owner_id, o.username
             ORDER BY c.name ASC, o.username ASC",
            $pTypes . $cTypes,
            array_merge($pParams, $cParams)
        )->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Danh mục $id có tồn tại (và nằm trong phạm vi $owners) không.
     * @param int[]|null $owners
     */
    public function exists(int $id, ?array $owners = null): bool
    {
        [$scope, $types, $params] = DataScope::sqlFilter('owner_id', $owners);
        return (bool)$this->db->query(
            "SELECT 1 FROM categories WHERE id = ?" . ($scope !== '' ? " AND $scope" : ''),
            'i' . $types,
            array_merge([$id], $params)
        )->fetch_row();
    }
}
