<?php
namespace App\Core;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use mysqli_stmt;
use RuntimeException;
use Throwable;

/**
 * Bọc kết nối mysqli: tạo kết nối từ config, chạy câu SQL bằng prepared statement,
 * chạy nhiều câu trong 1 transaction.
 *
 *   $db = Database::fromConfigFile(__DIR__ . '/config.php');
 *   $row = $db->query("SELECT * FROM products WHERE id = ?", 'i', [3])->fetch_assoc();
 */
class Database
{
    // Mã lỗi MySQL khi trùng khóa UNIQUE / PRIMARY KEY
    public const ERR_DUPLICATE = 1062;

    /** @var mysqli */
    private $conn;

    /**
     * @param array $config ['host', 'user', 'pass', 'name', 'port' (tùy chọn)]
     * @throws RuntimeException khi không kết nối được (chi tiết lỗi chỉ ghi vào log)
     */
    public function __construct(array $config)
    {
        // Từ PHP 8.1 đây là mặc định; đặt rõ để PHP 7.4 / 8.0 cũng báo lỗi bằng exception
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli(
                $config['host'] ?? 'localhost',
                $config['user'] ?? '',
                $config['pass'] ?? '',
                $config['name'] ?? '',
                (int)($config['port'] ?? 3306)
            );
            $this->conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            error_log('Lỗi kết nối MySQL: ' . $e->getMessage());
            throw new RuntimeException('Lỗi kết nối MySQL — kiểm tra MySQL đã chạy chưa và thông tin trong config.php.');
        }
    }

    /**
     * Tạo kết nối từ file config.php (file trả về mảng cấu hình).
     * @throws RuntimeException khi thiếu file hoặc không kết nối được
     */
    public static function fromConfigFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException('Thiếu file config.php — hãy copy config.example.php thành config.php và điền thông tin CSDL.');
        }
        return new self(require $path);
    }

    /**
     * Chạy câu SQL bằng prepared statement.
     * Trả về kết quả với SELECT, null với INSERT/UPDATE/DELETE.
     *
     * @param string $types kiểu của từng tham số: i (int), d (float), s (string), b (blob)
     */
    public function query(string $sql, string $types = '', array $params = []): ?mysqli_result
    {
        $stmt = $this->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result() ?: null;
    }

    // Chuẩn bị câu SQL để chạy nhiều lần với tham số khác nhau (vd. trong vòng lặp)
    public function prepare(string $sql): mysqli_stmt
    {
        return $this->conn->prepare($sql);
    }

    /**
     * Chạy $work trong 1 transaction: xong hết thì commit, có lỗi thì rollback rồi ném lại lỗi.
     *
     *   $deleted = $db->transaction(function (Database $db) { ...; return $n; });
     *
     * @return mixed giá trị $work trả về
     */
    public function transaction(callable $work)
    {
        $this->conn->begin_transaction();
        try {
            $result = $work($this);
            $this->conn->commit();
            return $result;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Escape chuỗi để ghép thẳng vào câu SQL (vd. khi XUẤT ra file .sql).
     * Khi chạy truy vấn thì luôn dùng query() với tham số, không ghép chuỗi.
     */
    public function escape(string $value): string
    {
        return $this->conn->real_escape_string($value);
    }
}
