<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../api/rbac.php';

function smoke_fail($message)
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function smoke_assert($condition, $message)
{
    if (!$condition) {
        smoke_fail($message);
    }
}

ob_start();
tf_security_headers();
ob_end_clean();

$token1 = tf_csrf_token();
$token2 = tf_csrf_token();

smoke_assert(is_string($token1) && strlen($token1) === 64, 'CSRF token should be a 64-char hex string');
smoke_assert($token1 === $token2, 'CSRF token should remain stable within the same session');

$_SERVER['HTTP_X_CSRF_TOKEN'] = $token1;
tf_csrf_validate([]);

smoke_assert(isset($_SESSION['_tf_csrf']) && $_SESSION['_tf_csrf'] === $token1, 'CSRF token should be stored in session');

echo "SMOKE_OK" . PHP_EOL;
