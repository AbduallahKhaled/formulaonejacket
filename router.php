<?php
// Local dev only (php -S localhost:8096 router.php): same private paths as .htaccess blocks on the host.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('#^/(data|admin-sections|docs|src-ref|src-video|\.git)(/|$)|^/(lib|router)\.php$#', $path)) {
    http_response_code(404);
    exit('Not found');
}
return false;
