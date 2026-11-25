<?php
namespace App\Core;

/**
 * Dữ liệu của request HTTP hiện tại: method, query string (?key=...), body JSON.
 *
 *   $request = Request::fromGlobals();
 *   $id   = $request->queryInt('id');
 *   $body = $request->json();
 *
 * Nhận dữ liệu qua constructor (thay vì đọc thẳng $_GET, $_SERVER) để có thể
 * tạo Request giả khi viết test.
 */
class Request
{
    /** @var array */
    private $query;

    /** @var array */
    private $server;

    /** @var string */
    private $rawBody;

    /** @var array|null body JSON đã giải mã (đọc 1 lần rồi giữ lại) */
    private $json = null;

    public function __construct(array $query, array $server, string $rawBody = '')
    {
        $this->query   = $query;
        $this->server  = $server;
        $this->rawBody = $rawBody;
    }

    // Request thật từ $_GET, $_SERVER và php://input
    public static function fromGlobals(): self
    {
        return new self($_GET, $_SERVER, (string)file_get_contents('php://input'));
    }

    // GET, POST, PUT, DELETE...
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    // Địa chỉ IP của trình duyệt (dùng để giới hạn số lần nhập sai mật khẩu)
    public function ip(): string
    {
        return (string)($this->server['REMOTE_ADDR'] ?? '');
    }

    // Tham số chuỗi từ query string (?key=...), đã bỏ khoảng trắng 2 đầu
    public function query(string $key, string $default = ''): string
    {
        return self::stringField($this->query, $key, $default);
    }

    // Tham số số nguyên từ query string; không có thì trả về $default
    public function queryInt(string $key, int $default = 0): int
    {
        $value = $this->query($key);
        return $value === '' ? $default : (int)$value;
    }

    /**
     * Body JSON của request dạng mảng (rỗng nếu body trống).
     * Bắt buộc Content-Type: application/json — form HTML của trang web khác không gửi được
     * kiểu này, nên đây cũng là một lớp chống CSRF.
     * Sai Content-Type → trả lỗi 415, JSON hỏng → trả lỗi 400.
     */
    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        if (stripos($this->server['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
            JsonResponse::error('Content-Type phải là application/json.', 415);
        }
        if (trim($this->rawBody) === '') {
            return $this->json = [];
        }
        $data = json_decode($this->rawBody, true);
        if (!is_array($data)) {
            JsonResponse::error('Dữ liệu JSON không hợp lệ.', 400);
        }
        return $this->json = $data;
    }

    /**
     * Lấy 1 trường chuỗi trong mảng (body JSON, query string...).
     * Không có hoặc không phải chuỗi thì trả về $default.
     */
    public static function stringField(array $data, string $key, string $default = '', bool $trim = true): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value)) {
            return $default;
        }
        return $trim ? trim($value) : $value;
    }
}
