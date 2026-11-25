<?php
// Điểm vào duy nhất của giao diện: trả về trang React đã build (cd frontend && npm run build).
// Vite build với base './' nên đường dẫn tài nguyên là "./assets/..."; ở đây đổi thành
// "/frontend/dist/assets/..." vì trang được phục vụ tại "/".

const FRONTEND_DIST = '/frontend/dist/';

$page = __DIR__ . FRONTEND_DIST . 'index.html';

if (!is_file($page)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Chưa build giao diện. Chạy: cd frontend && npm run build";
    exit;
}

$html = file_get_contents($page);
$html = preg_replace('#(src|href)="\./#', '$1="' . FRONTEND_DIST, $html);

header('Content-Type: text/html; charset=utf-8');
// Không cache trang HTML để trình duyệt luôn nhận đúng tên file assets (có hash) sau mỗi lần build
header('Cache-Control: no-cache, no-store, must-revalidate');
echo $html;
