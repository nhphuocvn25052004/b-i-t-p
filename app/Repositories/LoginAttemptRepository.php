<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * Bảng login_attempts: số lần nhập sai mật khẩu liên tiếp theo (tên đăng nhập, IP).
 * Chính sách khóa (sai mấy lần, chờ bao lâu) nằm ở App\Auth\LoginThrottle.
 *
 * Mọi phép tính thời gian dùng NOW() của MySQL (không dùng giờ PHP) để không lệch múi giờ.
 */
class LoginAttemptRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Số giây còn bị khóa, 0 = không bị khóa
    public function secondsLocked(string $username, string $ip): int
    {
        $row = $this->db->query(
            "SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS s
             FROM login_attempts WHERE username = ? AND ip = ?",
            'ss',
            [$username, $ip]
        )->fetch_assoc();
        return $row ? (int)$row['s'] : 0;
    }

    /**
     * Ghi thêm 1 lần sai, trả về tổng số lần sai liên tiếp.
     * Lần sai trước đã quá $resetAfter giây thì đếm lại từ 1.
     * Tăng bằng 1 câu SQL (không đọc rồi ghi) nên 2 request cùng lúc vẫn đếm đúng.
     */
    public function recordFailure(string $username, string $ip, int $resetAfter): int
    {
        // Trong ON DUPLICATE KEY UPDATE, failures được tính trước khi last_failed_at bị ghi đè
        $this->db->query(
            "INSERT INTO login_attempts (username, ip, failures, last_failed_at)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                 failures       = IF(last_failed_at < NOW() - INTERVAL ? SECOND, 1, failures + 1),
                 last_failed_at = NOW()",
            'ssi',
            [$username, $ip, $resetAfter]
        );
        $row = $this->db->query(
            "SELECT failures FROM login_attempts WHERE username = ? AND ip = ?",
            'ss',
            [$username, $ip]
        )->fetch_assoc();
        return $row ? (int)$row['failures'] : 1;
    }

    // Khóa trong $seconds giây kể từ bây giờ
    public function lock(string $username, string $ip, int $seconds): void
    {
        $this->db->query(
            "UPDATE login_attempts SET locked_until = NOW() + INTERVAL ? SECOND WHERE username = ? AND ip = ?",
            'iss',
            [$seconds, $username, $ip]
        );
    }

    // Đăng nhập đúng → xóa bộ đếm
    public function clear(string $username, string $ip): void
    {
        $this->db->query("DELETE FROM login_attempts WHERE username = ? AND ip = ?", 'ss', [$username, $ip]);
    }
}
