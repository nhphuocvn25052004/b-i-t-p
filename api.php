<?php
// API JSON cho giao diện React — mọi thao tác với bảng products
//
//   GET    api.php            danh sách sản phẩm
//   GET    api.php?q=từ_khóa  tìm theo tên / mô tả
//   GET    api.php?id=3       lấy 1 sản phẩm
//   POST   api.php            thêm   (body JSON: name, price, description)
//   PUT    api.php?id=3       sửa    (body JSON: name, price, description)
//   DELETE api.php            xóa    (body JSON: {"ids": [1, 2, 3]}) — trả về {"deleted": n}
require_once __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

try {
    switch ($method) {
        case 'GET':
            if ($id > 0) {
                $product = product_find($conn, $id);
                if (!$product) {
                    json_error('Không tìm thấy sản phẩm.', 404);
                }
                json_response($product);
            }
            $q = trim((string)($_GET['q'] ?? ''));
            json_response($q === '' ? product_all($conn) : product_search($conn, $q));

        case 'POST':
            [$data, $error] = product_from_request(read_json_body());
            if ($error) {
                json_error($error, 422);
            }
            product_create($conn, $data);
            json_response(['ok' => true], 201);

        case 'PUT':
            if (!product_find($conn, $id)) {
                json_error('Không tìm thấy sản phẩm.', 404);
            }
            [$data, $error] = product_from_request(read_json_body());
            if ($error) {
                json_error($error, 422);
            }
            product_update($conn, $id, $data);
            json_response(['ok' => true]);

        case 'DELETE':
            $ids = (array)(read_json_body()['ids'] ?? []);
            if (!$ids) {
                json_error('Bạn chưa chọn sản phẩm nào để xóa.', 422);
            }
            json_response(['deleted' => product_delete_many($conn, $ids)]);

        default:
            json_error('Phương thức không được hỗ trợ.', 405);
    }
} catch (Throwable $e) {
    error_log('API lỗi: ' . $e->getMessage());
    json_error('Lỗi máy chủ.', 500);
}
