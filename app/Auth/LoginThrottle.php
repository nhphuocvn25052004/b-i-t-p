<?php
namespace App\Auth;

use App\Core\JsonResponse;
use App\Repositories\LoginAttemptRepository;

/**
 * Chống dò mật khẩu: cho sai tối đa 3 lần, sau đó bắt chờ; sai càng nhiều chờ càng lâu.
 *
 *   Lần sai thứ:  1, 2   3      4      5       6       7       8        9        10+
 *   Phải chờ:     —      30 giây 1 phút 2 phút  4 phút  8 phút  16 phút  32 phút  60 phút
 *
 * - Đếm theo cặp (tên đăng nhập, IP): người dò từ máy khác không làm khóa tài khoản của bạn.
 * - Đăng nhập đúng → xóa bộ đếm. Sau 24 giờ không sai lần nào → đếm lại từ đầu.
 * - Tên đăng nhập không tồn tại cũng bị đếm như thường, để không lộ tên nào có thật.
 *
 *   $throttle->ensureAllowed($username, $ip);          // đang bị khóa → 429
 *   if (!$ok) $throttle->fail($username, $ip, 'Sai...', 401);  // ghi lần sai, trả lỗi
 *   $throttle->clear($username, $ip);
 */
class LoginThrottle
{
    public const FREE_ATTEMPTS = 3;      // số lần được sai trước khi bị khóa
    public const BASE_DELAY    = 30;     // giây chờ ở lần khóa đầu tiên, sau đó nhân đôi
    public const MAX_DELAY     = 3600;   // chờ tối đa 1 giờ
    public const RESET_AFTER   = 86400;  // 24 giờ không sai → đếm lại

    private const KEY_MAX_LENGTH = 50;   // = VARCHAR(50) của login_attempts.username

    /** @var LoginAttemptRepository */
    private $attempts;

    public function __construct(LoginAttemptRepository $attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * Số giây phải chờ sau lần sai thứ $failures (0 = chưa bị khóa).
     * 3 → 30, 4 → 60, 5 → 120, ... tối đa MAX_DELAY.
     */
    public static function delayFor(int $failures): int
    {
        if ($failures < self::FREE_ATTEMPTS) {
            return 0;
        }
        $doublings = min($failures - self::FREE_ATTEMPTS, 20); // giới hạn để 2^n không tràn số
        return min(self::MAX_DELAY, self::BASE_DELAY * (2 ** $doublings));
    }

    // Đang bị khóa thì trả 429 và dừng script
    public function ensureAllowed(string $username, string $ip): void
    {
        $seconds = $this->attempts->secondsLocked($this->key($username), $ip);
        if ($seconds > 0) {
            $this->tooManyAttempts($seconds);
        }
    }

    /**
     * Ghi 1 lần sai rồi trả lỗi và dừng script:
     *   - vừa đủ số lần sai để bị khóa → 429 kèm số giây phải chờ
     *   - chưa bị khóa → $status với "$message Bạn còn N lần thử."
     */
    public function fail(string $username, string $ip, string $message, int $status): void
    {
        $key      = $this->key($username);
        $failures = $this->attempts->recordFailure($key, $ip, self::RESET_AFTER);
        $delay    = self::delayFor($failures);

        if ($delay > 0) {
            $this->attempts->lock($key, $ip, $delay);
            $this->tooManyAttempts($delay);
        }

        $left = self::FREE_ATTEMPTS - $failures;
        JsonResponse::error("$message Bạn còn $left lần thử.", $status, ['attempts_left' => $left]);
    }

    public function clear(string $username, string $ip): void
    {
        $this->attempts->clear($this->key($username), $ip);
    }

    // 429 Too Many Requests + header Retry-After (chuẩn HTTP) + retry_after trong JSON cho React đếm ngược
    private function tooManyAttempts(int $seconds): void
    {
        header('Retry-After: ' . $seconds);
        JsonResponse::error(
            'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . self::formatWait($seconds) . '.',
            429,
            ['retry_after' => $seconds]
        );
    }

    // 45 → "45 giây", 120 → "2 phút", 150 → "2 phút 30 giây"
    private static function formatWait(int $seconds): string
    {
        if ($seconds < 60) {
            return "$seconds giây";
        }
        $minutes = intdiv($seconds, 60);
        $rest    = $seconds % 60;
        return $rest ? "$minutes phút $rest giây" : "$minutes phút";
    }

    // Cắt tên đăng nhập cho vừa cột (đếm theo ký tự UTF-8, không cắt giữa chữ có dấu).
    // Hoa/thường, có dấu/không dấu được MySQL coi là một (collation utf8mb4_0900_ai_ci),
    // nên "Admin" và "admin" dùng chung bộ đếm — giống cách bảng users so tên đăng nhập.
    private function key(string $username): string
    {
        return preg_replace('/^(.{0,' . self::KEY_MAX_LENGTH . '}).*$/su', '$1', $username) ?? '';
    }
}
