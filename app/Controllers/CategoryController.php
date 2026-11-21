<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\CategoryRepository;

/**
 * API danh mục
 *
 *   GET api/categories.php   danh sách danh mục + số sản phẩm (đăng nhập)
 */
class CategoryController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    /** @var CategoryRepository */
    private $categories;

    public function __construct(Request $request, Auth $auth, CategoryRepository $categories)
    {
        $this->request    = $request;
        $this->auth       = $auth;
        $this->categories = $categories;
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
        $this->auth->requireLogin();
        JsonResponse::send($this->categories->allWithProductCount());
    }
}
