<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Auth\DataScope;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\CategoryRepository;

/**
 * API danh mục
 *
 *   GET api/categories.php   danh mục trong phạm vi + số sản phẩm mình thấy (đăng nhập)
 *                            [{id, name, owner_id, owner_name, product_count}]
 * Mỗi quản trị có bộ danh mục riêng — xem App\Auth\DataScope.
 */
class CategoryController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    /** @var CategoryRepository */
    private $categories;

    /** @var DataScope */
    private $scope;

    public function __construct(Request $request, Auth $auth, CategoryRepository $categories, DataScope $scope)
    {
        $this->request    = $request;
        $this->auth       = $auth;
        $this->categories = $categories;
        $this->scope      = $scope;
    }

    public function handle(): void
    {
        if ($this->request->method() !== 'GET') {
            JsonResponse::error('Phương thức không được hỗ trợ.', 405);
        }
        $this->index();
    }

    public function index(): void
    {
        $user = $this->auth->requireLogin();
        JsonResponse::send($this->categories->allWithProductCount(
            $this->scope->categoryOwners($user),
            $this->scope->productOwners($user)
        ));
    }
}
