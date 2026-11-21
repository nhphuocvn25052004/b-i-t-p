<?php
// Model: bảng categories (danh mục sản phẩm)

// Mọi danh mục kèm số sản phẩm trong từng danh mục
function category_all(mysqli $conn): array
{
    return db_query(
        $conn,
        "SELECT c.id, c.name, COUNT(p.id) AS product_count
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.id, c.name
         ORDER BY c.name ASC"
    )->fetch_all(MYSQLI_ASSOC);
}

function category_exists(mysqli $conn, int $id): bool
{
    return (bool)db_query($conn, "SELECT 1 FROM categories WHERE id = ?", 'i', [$id])->fetch_row();
}
