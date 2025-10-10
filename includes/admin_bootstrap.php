<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security_headers.php';
if (($GLOBALS['__admin_security_headers_applied'] ?? false) === false) {
    SecurityHeaders::setHeaders();
    SecurityHeaders::setSecureSession();
    $GLOBALS['__admin_security_headers_applied'] = true;
}

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (file_exists(__DIR__ . '/../config/production.php')) {
    require_once __DIR__ . '/../config/production.php';
}

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    require_once __DIR__ . '/db.php';
    if (!isset($GLOBALS['conn'])) {
        $GLOBALS['conn'] = $conn ?? null;
    }
    $conn = $GLOBALS['conn'];
}
require_once __DIR__ . '/auth_middleware.php';

/**
 * Ensure the current session has an authenticated user and optionally enforce roles.
 *
 * @param array|null $allowedRoles List of allowed roles (case-insensitive). Null to allow any authenticated role.
 * @return array The authenticated user data from the session.
 */
function admin_require_auth(?array $allowedRoles = null): array
{
    $user = AuthMiddleware::requireLogin();

    if ($allowedRoles !== null) {
        $allowed = array_map('strtolower', $allowedRoles);
        $role = strtolower($user['role'] ?? '');

        if (!in_array($role, $allowed, true)) {
            AuthMiddleware::logSecurityEvent('access_denied', [
                'allowed_roles' => $allowedRoles,
                'user_role' => $role,
                'path' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            header('Location: ' . admin_login_url('access_denied'));
            exit;
        }
    }

    $GLOBALS['currentUser'] = $user;
    $GLOBALS['currentUserRole'] = strtolower($user['role'] ?? '');

    return $user;
}

/**
 * Build a URL relative to the application root.
 */
function admin_app_url(string $path = ''): string
{
    $normalizedPath = '/' . ltrim($path, '/');

    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/') . $normalizedPath;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = str_replace('\\', '/', dirname(dirname($scriptName)));
    $basePath = rtrim($basePath, '/');

    if ($basePath === '' || $basePath === '.') {
        return $normalizedPath;
    }

    return $basePath . $normalizedPath;
}

/**
 * Build the login URL, optionally appending a query parameter reason.
 */
function admin_login_url(string $errorCode = ''): string
{
    $loginPath = '/auth/login.php';
    $url = admin_app_url($loginPath);

    if ($errorCode !== '') {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'error=' . rawurlencode($errorCode);
    }

    return $url;
}
