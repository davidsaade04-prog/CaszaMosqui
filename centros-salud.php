<?php
/**
 * CaszaMosqui — Centros de salud de El Colorado (página independiente)
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Centros de salud';
$active_page = 'salud';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

require __DIR__ . '/inc/cabecera.php';
?>

  <!-- CENTROS DE SALUD -->
  <section class="panel" id="salud">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>🏥 Centros de salud de El Colorado</h2>
    <p class="sub">Si tenés fiebre o síntomas, <strong>no te automediques</strong>: consultá. Tocá <strong>📞</strong> para llamar o <strong>🧭 Cómo llegar</strong> para ver el camino desde donde estás.</p>
    <div class="salud-barra">
      <a class="salud-107" href="tel:107"><span>🚑</span><span><strong>Emergencias: 107</strong><small>Signos de alarma: llamá o andá a la guardia</small></span></a>
      <button type="button" class="salud-btn salud-cerca" data-js-mas-cerca>📍 ¿Cuál me queda más cerca?</button>
      <p class="salud-cerca-aviso" data-js-cerca-aviso aria-live="polite"></p>
    </div>
    <div class="salud-grid">
      <div class="salud-lista" data-js-centros></div>
      <div class="salud-lateral">
        <div class="salud-mapa" data-js-salud-mapa aria-label="Mapa de los centros de salud"></div>
        <div class="salud-ruta" data-js-ruta hidden aria-live="polite"></div>
      </div>
    </div>
    <p class="salud-pie">Datos verificados por el equipo de CaszaMosqui (noviembre 2026). Las salas de primeros auxilios de los barrios se suman cuando confirmemos sus datos.
      Antes de ir a un consultorio o clínica privada, llamá para confirmar el horario.</p>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
