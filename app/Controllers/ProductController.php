<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Auth\DataScope;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\ProductRepository;
use App\Validators\ProductValidator;

/**
 * API sản phẩm
 *
 *   GET    api/products.php?q=&category=&page=1&per_page=10   danh sách (đăng nhập)
 *   GET    api/products.php?id=3                              1 sản phẩm (đăng nhập)
 *   POST   api/products.php            {name, price, description, category_id}   (quyền products.create)
 *   PUT    api/products.php?id=3       {name, price, description, category_id}   (quyền products.update)
 *   DELETE api/products.php            {"ids": [1, 2]} → {"deleted": 2}           (quyền products.delete)
 *
 * Mỗi người chỉ thấy / sửa / xóa sản phẩm trong phạm vi của mình (App\Auth\DataScope);
 * sản phẩm ngoài phạm vi trả về 404 như thể không tồn tại.
 */
class ProductController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    /** @var ProductRepository */
    private $products;

    /** @var ProductValidator */
    private $validator;

    /** @var DataScope */
    private $scope;

    public function __construct(
        Request $request,
        Auth $auth,
        ProductRepository $products,
        ProductValidator $validator,
        DataScope $scope
    ) {
        $this->request   = $request;
        $this->auth      = $auth;
        $this->products  = $products;
        $this->validator = $validator;
        $this->scope     = $scope;
    }

    // Chọn hành động theo phương thức HTTP
    public function handle(): void
    {
        $id = $this->request->queryInt('id');

        switch ($this->request->method()) {
            case 'GET':
                $id > 0 ? $this->show($id) : $this->index();
                break;
            case 'POST':
                $this->store();
                break;
            case 'PUT':
                $this->update($id);
                break;
            case 'DELETE':
                $this->destroy();
                break;
            default:
                JsonResponse::error('Phương thức không được hỗ trợ.', 405);
        }
    }

    public function index(): void
    {
        $user = $this->auth->requireLogin();
        JsonResponse::send($this->products->paginate(
            ['q' => $this->request->query('q'), 'category' => $this->request->query('category')],
            $this->request->queryInt('page', 1),
            $this->request->queryInt('per_page', 10),
            $this->scope->productOwners($user)
        ));
    }

    public function show(int $id): void
    {
        $user = $this->auth->requireLogin();
        JsonResponse::send($this->findOr404($user, $id));
    }

    public function store(): void
    {
        $user = $this->auth->requirePermission(Auth::PERM_PRODUCTS_CREATE);
        $this->products->create($this->validInputOr422($user), DataScope::productOwnerFor($user));
        JsonResponse::send(['ok' => true], 201);
    }

    public function update(int $id): void
    {
        $user = $this->auth->requirePermission(Auth::PERM_PRODUCTS_UPDATE);
        $this->findOr404($user, $id);
        $this->products->update($id, $this->validInputOr422($user));
        JsonResponse::send(['ok' => true]);
    }

    public function destroy(): void
    {
        $user = $this->auth->requirePermission(Auth::PERM_PRODUCTS_DELETE);
        $ids  = (array)($this->request->json()['ids'] ?? []);
        if (!$ids) {
            JsonResponse::error('Bạn chưa chọn sản phẩm nào để xóa.', 422);
        }
        JsonResponse::send(['deleted' => $this->products->deleteMany($ids, $this->scope->productOwners($user))]);
    }

    // Sản phẩm theo id trong phạm vi của $user, không có thì trả về 404
    private function findOr404(array $user, int $id): array
    {
        $product = $this->products->find($id, $this->scope->productOwners($user));
        if (!$product) {
            JsonResponse::error('Không tìm thấy sản phẩm.', 404);
        }
        return $product;
    }

    // Dữ liệu sản phẩm từ body JSON (danh mục phải thuộc phạm vi của $user), không hợp lệ thì trả về 422
    private function validInputOr422(array $user): array
    {
        [$data, $error] = $this->validator->validate($this->request->json(), $this->scope->categoryOwners($user));
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }
        return $data;
    }
}
