<?php
/**
 * CaszaMosqui — Cuestionario de concientización (página independiente)
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Cuestionario';
$active_page = 'cuestionario';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

require __DIR__ . '/inc/cabecera.php';
?>

  <!-- CUESTIONARIO -->
  <section class="panel" id="quiz">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <div class="cuestionario-encabezado">
      <h2>🎯 Cuestionario: ¿cuánto sabés sobre prevención?</h2>
      <p class="sub">Respondé el cuestionario y recibí tu resultado. Compartilo con tu barrio para frenar al mosquito. 🦟</p>
    </div>
    <div data-js-quiz>
      <p class="loading">Cargando cuestionario…</p>
    </div>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
