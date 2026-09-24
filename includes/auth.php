<?php
declare(strict_types=1);

function tracker_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function tracker_is_logged_in(): bool
{
    tracker_start_session();
    return !empty($_SESSION['tracker_auth']);
}

function tracker_require_login(): void
{
    if (!tracker_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function tracker_login(string $password, array $config): bool
{
    if (password_verify($password, $config['password_hash'])) {
        tracker_start_session();
        session_regenerate_id(true);
        $_SESSION['tracker_auth'] = true;
        return true;
    }
    return false;
}

function tracker_logout(): void
{
    tracker_start_session();
    $_SESSION = [];
    session_destroy();
}

function tracker_csrf_token(): string
{
    tracker_start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function tracker_csrf_check(string $token): bool
{
    tracker_start_session();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}