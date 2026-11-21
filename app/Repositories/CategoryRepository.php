<?php
namespace App\Repositories;

use App\Core\Database;

// Thao tác CSDL với bảng categories (danh mục sản phẩm)
class CategoryRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Mọi danh mục kèm số sản phẩm trong từng danh mục, sắp theo tên
    public function allWithProductCount(): array
    {
        return $this->db->query(
            "SELECT c.id, c.name, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id, c.name
             ORDER BY c.name ASC"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function exists(int $id): bool
    {
        return (bool)$this->db->query("SELECT 1 FROM categories WHERE id = ?", 'i', [$id])->fetch_row();
    }
}
