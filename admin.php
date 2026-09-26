<?php
/**
 * CaszaMosqui — Panel de administración de reportes
 * Solo con sesión iniciada (el servidor lo verifica acá y en cada pedido a la API).
 * Los cambios de estado actualizan el semáforo del mapa y los indicadores del sitio público.
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

if (!reportes_logueado()) {
    redirect(BASE_URL . '/?acceso=1');
}
$reportes_csrf    = reportes_csrf_token();
$reportes_usuario = usuario_reportes();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>Panel de reportes · <?= e(APP_NAME) ?></title>
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" type="image/jpeg">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css?v=20260926-mapa">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/leaflet.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/mapa-plano.css">
</head>
<body class="pagina-admin">

<header class="topbar topbar-admin">
  <div class="container">
    <div class="brand">
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?> · Panel de reportes</h1>
        <p>Gestión de criaderos reportados por la comunidad</p>
      </div>
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" alt="<?= e(APP_NAME) ?>" class="logo-img">
    </div>
    <div class="admin-sesion">
      <span>👤 Sesión iniciada como <strong><?= e($reportes_usuario) ?></strong></span>
      <a class="btn-admin-sec" href="<?= e(BASE_URL) ?>/">← Ver sitio público</a>
      <form method="post" action="<?= e(BASE_URL) ?>/reportes-auth.php" class="reportes-logout-form">
        <input type="hidden" name="accion" value="logout">
        <input type="hidden" name="csrf" value="<?= e($reportes_csrf) ?>">
        <button type="submit" class="btn-logout">Cerrar sesión</button>
      </form>
    </div>
  </div>
</header>

<main class="container">

  <p class="admin-aviso">ℹ️ Lo que cambies acá (verificar, controlar o eliminar un reporte) se refleja al instante en los
     indicadores y en el <strong>semáforo del mapa</strong> que ve la comunidad. Tocá un barrio del mapa para ver solo sus criaderos.</p>

  <?php require __DIR__ . '/inc/kpis.php'; ?>

  <?php require __DIR__ . '/inc/mapa.php'; ?>

  <!-- REPORTES -->
  <section class="panel" id="reportes">
    <div class="panel-head">
      <h2>📋 Criaderos reportados por la comunidad</h2>
      <form class="filtros" data-js-filtros>
        <select name="tipo" aria-label="Filtrar por tipo">
          <option value="">Todos los tipos</option>
        </select>
        <select name="barrio" aria-label="Filtrar por barrio">
          <option value="">Todos los barrios</option>
        </select>
        <select name="estado" aria-label="Filtrar por estado">
          <option value="">Todos los estados</option>
          <option value="pendiente">Sin controlar</option>
          <option value="verificado">Verificado</option>
          <option value="controlado">Controlado</option>
        </select>
        <input type="search" name="q" placeholder="Buscar…" aria-label="Buscar">
        <button type="button" data-js-reset class="btn-ghost">Limpiar</button>
      </form>
    </div>
    <div data-js-reportes>
      <p class="loading">Cargando reportes…</p>
    </div>
    <nav class="reportes-paginacion" data-js-paginacion aria-label="Paginación de reportes" hidden>
      <button type="button" class="reportes-pagina" data-pagina-anterior disabled>← Anterior</button>
      <div class="paginacion-numeros" data-js-paginacion-numeros></div>
      <button type="button" class="reportes-pagina" data-pagina-siguiente disabled>Siguiente →</button>
      <span class="paginacion-info" data-js-paginacion-info aria-live="polite"></span>
    </nav>
  </section>

</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.REPORTES_ACCESS = true;
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/clima.js?v=20260925-vivo"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=20260926-mapa"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/leaflet.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/mapa-real.js?v=20260926-mapa"></script>
</body>
</html>
