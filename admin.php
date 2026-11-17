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
  <link rel="manifest" href="<?= e(BASE_URL) ?>/manifest.webmanifest">
  <meta name="theme-color" content="#0b1220">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="CaszaMosqui">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/app/favicon-48.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/app/apple-touch-icon.png">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css?v=20261116-botones">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/leaflet.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/mapa-plano.css">
</head>
<body class="pagina-admin">

<header class="topbar topbar-admin">
  <div class="container">
    <div class="brand">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-emblema.png" alt="" class="logo-img" width="52" height="59">
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?> · Panel de reportes</h1>
        <p>Gestión de criaderos reportados por la comunidad</p>
      </div>
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
     indicadores y en el <strong>semáforo del mapa</strong> que ve la comunidad. Tocá un barrio del mapa para ver solo sus criaderos.
     En <strong>🌎 Mapa real</strong> ves el punto exacto de los reportes que llegaron con GPS (solo vos los ves).</p>

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
        <button type="button" data-js-exportar class="btn-excel" title="Descarga los reportes con los filtros elegidos">📊 Excel</button>
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

  <!-- PEDIDOS Y RECLAMOS (Ley 25.326, arts. 14 y 16) -->
  <section class="panel cuentas" id="reclamos">
    <div class="panel-head">
      <h2>📨 Pedidos y reclamos de vecinos</h2>
      <select data-js-reclamos-filtro aria-label="Filtrar pedidos por estado">
        <option value="">Todos</option>
        <option value="nuevo" selected>Nuevos</option>
        <option value="en_curso">En curso</option>
        <option value="resuelto">Resueltos</option>
      </select>
    </div>
    <p class="sub">Pedidos de acceso, corrección o borrado de datos, reclamos por comentarios y avisos de errores
      (formulario de <a href="<?= e(BASE_URL) ?>/privacidad.php#reclamo" target="_blank" rel="noopener">Privacidad</a>).
      La ley pide responder rápido: el acceso en 10 días corridos y las correcciones o borrados en 5 días hábiles.</p>
    <div data-js-reclamos><p class="loading">Cargando pedidos…</p></div>
  </section>

  <!-- REGISTRO DE CAMBIOS (rendición de cuentas) -->
  <section class="panel cuentas" id="historial">
    <div class="panel-head">
      <h2>📜 Registro de cambios</h2>
      <select data-js-historial-filtro aria-label="Filtrar el registro">
        <option value="">Todo</option>
        <option value="reporte">Reportes</option>
        <option value="comentario">Comentarios</option>
        <option value="reclamo">Pedidos</option>
        <option value="sistema">Sistema</option>
      </select>
    </div>
    <p class="sub">Quién hizo qué y cuándo: cada verificación, control, borrado, respuesta a un pedido o descarga de respaldo
      queda registrada y no se puede editar. Sirve para rendir cuentas si algo sale mal.</p>
    <div class="historial-wrap" data-js-historial><p class="loading">Cargando registro…</p></div>
  </section>

  <!-- RESPALDO / MUDANZA DE LA BASE -->
  <section class="panel respaldo" data-js-respaldo>
    <h2>🗄️ Respaldo de datos</h2>
    <p class="sub">Descargá una copia de todos los reportes, comentarios y fotos. Sirve para mudar la base de datos
       (la base gratuita de Render vence cada 30 días) o como resguardo. Guía paso a paso: <code>docs/MUDANZA.md</code>.</p>
    <p class="admin-aviso">🔒 El respaldo tiene <strong>datos personales</strong> (fotos, ubicaciones y pedidos). Guardalo en un lugar seguro,
      no lo compartas y borralo cuando ya no lo necesites. Cada descarga queda en el registro de cambios.</p>
    <div class="extra-botones">
      <button type="button" data-js-respaldo-descargar>⬇️ Descargar respaldo</button>
      <button type="button" class="btn-ghost" data-js-respaldo-restaurar>⬆️ Restaurar un respaldo…</button>
    </div>
    <input type="file" accept="application/json,.json" data-js-respaldo-archivo hidden>
    <p class="extra-estado" data-js-respaldo-estado aria-live="polite"></p>
  </section>

</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.REPORTES_ACCESS = true;
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/clima.js?v=20261116-sindatos"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=20261116-acepta"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/leaflet.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/geo.js?v=20261019-gps"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/mapa-calor.js?v=20261111-app"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/mapa-real.js?v=20261112-salud"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/admin-extra.js?v=20261111-app"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/admin-cuentas.js?v=20261116-cuentas"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/offline.js?v=20261116-instalar"></script>
</body>
</html>
