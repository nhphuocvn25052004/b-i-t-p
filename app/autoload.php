<?php
// Tự động nạp class theo quy tắc PSR-4: App\Core\Database → app/Core/Database.php
// (không cần require_once từng file class)
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return; // không phải class của dự án
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
