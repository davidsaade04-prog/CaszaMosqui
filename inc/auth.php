<?php
/**
 * Autenticación simple de la sección Reportes.
 *
 * La sesión es de PHP: dura mientras el navegador mantiene abierta la
 * aplicación y se elimina completamente al cerrar sesión. Las credenciales
 * se configuran en inc/config.php y nunca se incluyen en el HTML.
 */

require_once __DIR__ . '/config.php';

function iniciar_sesion_reportes(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $path   = defined('BASE_URL') ? BASE_URL . '/' : '/';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('CASZAMOSQUI_SESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $path,
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function reportes_logueado(): bool
{
    iniciar_sesion_reportes();

    return !empty($_SESSION['casza_reportes_auth'])
        && !empty($_SESSION['casza_reportes_user']);
}

function usuario_reportes(): string
{
    iniciar_sesion_reportes();

    return isset($_SESSION['casza_reportes_user'])
        ? (string) $_SESSION['casza_reportes_user']
        : '';
}

function reportes_csrf_token(): string
{
    iniciar_sesion_reportes();

    if (empty($_SESSION['casza_reportes_csrf'])) {
        $_SESSION['casza_reportes_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['casza_reportes_csrf'];
}

function reportes_csrf_valido(?string $token): bool
{
    iniciar_sesion_reportes();

    return $token !== null
        && !empty($_SESSION['casza_reportes_csrf'])
        && hash_equals((string) $_SESSION['casza_reportes_csrf'], $token);
}

function autenticar_reportes(string $usuario, string $contrasena): bool
{
    iniciar_sesion_reportes();

    $usuarioEsperado = defined('AUTH_USERNAME') ? (string) AUTH_USERNAME : '';
    $hashEsperado    = defined('AUTH_PASSWORD_HASH') ? (string) AUTH_PASSWORD_HASH : '';
    $usuarioValido    = $usuarioEsperado !== '' && hash_equals($usuarioEsperado, trim($usuario));
    $contrasenaValida = $hashEsperado !== '' && password_verify($contrasena, $hashEsperado);

    if (!$usuarioValido || !$contrasenaValida) {
        return false;
    }

    // Renovar el ID evita reutilizar una sesión anterior al iniciar sesión.
    session_regenerate_id(true);
    $_SESSION['casza_reportes_auth'] = true;
    $_SESSION['casza_reportes_user'] = $usuarioEsperado;
    unset($_SESSION['casza_reportes_error']);

    return true;
}

function guardar_error_reportes(string $mensaje): void
{
    iniciar_sesion_reportes();
    $_SESSION['casza_reportes_error'] = $mensaje;
}

function consumir_error_reportes(): ?string
{
    iniciar_sesion_reportes();

    if (empty($_SESSION['casza_reportes_error'])) {
        return null;
    }

    $mensaje = (string) $_SESSION['casza_reportes_error'];
    unset($_SESSION['casza_reportes_error']);

    return $mensaje;
}

function cerrar_sesion_reportes(): void
{
    iniciar_sesion_reportes();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'] ?? '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => $params['secure'] ?? false,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
