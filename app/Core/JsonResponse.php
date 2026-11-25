<?php
namespace App\Core;

/**
 * Trả kết quả API dạng JSON rồi dừng script.
 *
 *   JsonResponse::send(['ok' => true], 201);
 *   JsonResponse::error('Không tìm thấy sản phẩm.', 404);   // → {"error": "..."}
 */
class JsonResponse
{
    /** @var mixed */
    private $data;

    /** @var int */
    private $status;

    /** @param mixed $data dữ liệu sẽ được json_encode */
    public function __construct($data, int $status = 200)
    {
        $this->data   = $data;
        $this->status = $status;
    }

    // Gửi header + body JSON và dừng script
    public function emit(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @param mixed $data */
    public static function send($data, int $status = 200): void
    {
        (new self($data, $status))->emit();
    }

    /**
     * Lỗi dạng {"error": "...", ...$extra}
     * @param array $extra trường bổ sung, vd ['retry_after' => 30]
     */
    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        self::send(['error' => $message] + $extra, $status);
    }
}
