<?php
// API sản phẩm
//
//   GET    api/products.php?q=&category=&page=1&per_page=10   danh sách (đăng nhập)
//   GET    api/products.php?id=3                              1 sản phẩm (đăng nhập)
//   POST   api/products.php            {name, price, description, category_id}   (admin)
//   PUT    api/products.php?id=3       {name, price, description, category_id}   (admin)
//   DELETE api/products.php            {"ids": [1, 2]} → {"deleted": 2}           (admin)
require_once dirname(__DIR__) . '/lib/bootstrap.php';

// Sản phẩm theo id, không có thì trả về 404
function find_product_or_404(mysqli $conn, int $id): array
{
    $product = product_find($conn, $id);
    if (!$product) {
        json_error('Không tìm thấy sản phẩm.', 404);
    }
    return $product;
}

// Dữ liệu sản phẩm từ body JSON, không hợp lệ thì trả về 422
function product_input_or_422(mysqli $conn): array
{
    [$data, $error] = product_validate($conn, read_json_body());
    if ($error) {
        json_error($error, 422);
    }
    return $data;
}

$id = (int)query_param('id', '0');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        require_login();
        if ($id > 0) {
            json_response(find_product_or_404($conn, $id));
        }
        json_response(product_list(
            $conn,
            ['q' => query_param('q'), 'category' => query_param('category')],
            (int)query_param('page', '1'),
            (int)query_param('per_page', '10')
        ));

    case 'POST':
        require_role('admin');
        product_create($conn, product_input_or_422($conn));
        json_response(['ok' => true], 201);

    case 'PUT':
        require_role('admin');
        find_product_or_404($conn, $id);
        product_update($conn, $id, product_input_or_422($conn));
        json_response(['ok' => true]);

    case 'DELETE':
        require_role('admin');
        $ids = (array)(read_json_body()['ids'] ?? []);
        if (!$ids) {
            json_error('Bạn chưa chọn sản phẩm nào để xóa.', 422);
        }
        json_response(['deleted' => product_delete_many($conn, $ids)]);

    default:
        json_error('Phương thức không được hỗ trợ.', 405);
}
