<?php
/**
 * Cabecera HTML común de CaszaMosqui (inicio y páginas internas).
 * La página que la incluye debe definir antes:
 *   $page_title        Título de la pestaña y de la página.
 *   $active_page       Clave del ítem de menú activo (mapa, reportar, prevencion, salud, cuestionario, sintomas, comentarios).
 *   $reportes_logueado, $reportes_error, $reportes_csrf   Datos de sesión del administrador.
 * Uso: require __DIR__ . '/inc/cabecera.php';
 */
$page_title  = isset($page_title) ? $page_title : APP_NAME;
$active_page = isset($active_page) ? $active_page : '';

if (!function_exists('nav_clase')) {
    /** Devuelve las clases del ítem de menú según la página activa. */
    function nav_clase(string $clave, string $activa): string
    {
        return $clave === $activa ? ' class="active" aria-current="page"' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> · <?= e(APP_NAME) ?></title>
  <link rel="manifest" href="<?= e(BASE_URL) ?>/manifest.webmanifest">
  <meta name="theme-color" content="#0b1220">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="CaszaMosqui">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/app/favicon-48.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/app/apple-touch-icon.png">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css?v=20261113-portada">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/chatbot.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/clima.css?v=20260925-vivo">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/leaflet.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/mapa-plano.css">
</head>
<body>

<header class="topbar">
  <?php require __DIR__ . '/acceso-admin.php'; ?>
  <div class="container">
    <a class="brand" href="<?= e(BASE_URL) ?>/" aria-label="<?= e(APP_NAME) ?> · inicio">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-emblema.png" alt="" class="logo-img" width="52" height="59">
      <span class="brand-text">
        <strong><?= e(APP_NAME) ?></strong>
        <span><?= e(APP_TAGLINE) ?></span>
      </span>
    </a>
    <nav aria-label="Secciones">
      <a href="<?= e(BASE_URL) ?>/"<?= nav_clase('mapa', $active_page) ?>>Mapa de riesgo</a>
      <a href="<?= e(BASE_URL) ?>/reportar.php"<?= nav_clase('reportar', $active_page) ?>>+ Reportar</a>
      <a href="<?= e(BASE_URL) ?>/prevencion.php"<?= nav_clase('prevencion', $active_page) ?>>Prevención</a>
      <a href="<?= e(BASE_URL) ?>/centros-salud.php"<?= nav_clase('salud', $active_page) ?>>Centros de salud</a>
      <a href="<?= e(BASE_URL) ?>/cuestionario.php"<?= nav_clase('cuestionario', $active_page) ?>>Cuestionario</a>
      <a href="<?= e(BASE_URL) ?>/test-sintomas.php"<?= nav_clase('sintomas', $active_page) ?>>Test de Síntomas</a>
      <a href="<?= e(BASE_URL) ?>/comentarios.php"<?= nav_clase('comentarios', $active_page) ?>>Comentarios</a>
    </nav>
  </div>
</header>

<main class="container">
