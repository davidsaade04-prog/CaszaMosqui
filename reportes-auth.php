<?php
/**
 * Procesa el inicio y cierre de sesión de la sección Reportes.
 * No contiene la interfaz: recibe formularios POST y redirige al dashboard.
 */

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$destinoReportes = BASE_URL . '/#reportes';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

$accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
$token  = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

if (!reportes_csrf_valido($token)) {
    guardar_error_reportes('La sesión expiró. Volvé a intentar.');
    redirect($destinoReportes);
}

if ($accion === 'login') {
    $usuario    = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
    $contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';

    if (autenticar_reportes($usuario, $contrasena)) {
        redirect($destinoReportes);
    }

    guardar_error_reportes('Usuario o contraseña incorrectos');
    redirect($destinoReportes);
}

if ($accion === 'logout') {
    cerrar_sesion_reportes();
    redirect($destinoReportes);
}

http_response_code(400);
exit('Solicitud inválida.');
