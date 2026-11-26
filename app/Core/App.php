<?php
namespace App\Core;

use App\Auth\Auth;
use App\Auth\DataScope;
use App\Auth\LoginThrottle;
use App\Repositories\CategoryRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SuperAdminRepository;
use App\Repositories\UserRepository;
use App\Validators\ProductValidator;
use App\Validators\UserValidator;

/**
 * Nơi DUY NHẤT tạo các đối tượng dùng chung và nối chúng với nhau (ai cần ai).
 * Mỗi đối tượng chỉ được tạo khi cần lần đầu, các lần sau dùng lại.
 *
 *   $app = new App($database);
 *   $app->products()->find(3);
 *
 * Nhờ vậy controller / repository không tự "new" thứ mình cần mà được truyền vào
 * qua constructor (dependency injection) — dễ thay thế và dễ test.
 */
class App
{
    /** @var Database */
    private $database;

    /** @var array<string, object> đối tượng đã tạo, theo tên */
    private $instances = [];

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function database(): Database
    {
        return $this->database;
    }

    public function request(): Request
    {
        return $this->once('request', function () {
            return Request::fromGlobals();
        });
    }

    public function products(): ProductRepository
    {
        return $this->once('products', function () {
            return new ProductRepository($this->database);
        });
    }

    public function categories(): CategoryRepository
    {
        return $this->once('categories', function () {
            return new CategoryRepository($this->database);
        });
    }

    public function users(): UserRepository
    {
        return $this->once('users', function () {
            return new UserRepository($this->database);
        });
    }

    public function superAdmins(): SuperAdminRepository
    {
        return $this->once('superAdmins', function () {
            return new SuperAdminRepository($this->database);
        });
    }

    public function productValidator(): ProductValidator
    {
        return $this->once('productValidator', function () {
            return new ProductValidator($this->categories());
        });
    }

    public function userValidator(): UserValidator
    {
        return $this->once('userValidator', function () {
            return new UserValidator();
        });
    }

    public function auth(): Auth
    {
        return $this->once('auth', function () {
            return new Auth($this->users(), $this->superAdmins());
        });
    }

    public function dataScope(): DataScope
    {
        return $this->once('dataScope', function () {
            return new DataScope($this->users());
        });
    }

    public function loginThrottle(): LoginThrottle
    {
        return $this->once('loginThrottle', function () {
            return new LoginThrottle(new LoginAttemptRepository($this->database));
        });
    }

    // Tạo đối tượng bằng $factory ở lần gọi đầu, các lần sau trả về đúng đối tượng đó
    private function once(string $name, callable $factory)
    {
        if (!isset($this->instances[$name])) {
            $this->instances[$name] = $factory();
        }
        return $this->instances[$name];
    }
}
