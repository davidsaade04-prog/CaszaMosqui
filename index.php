<?php
/**
 * CaszaMosqui — Mapa de riesgo (página principal)
 * FormosaHack 2026 · Desafío: enfermedades transmitidas por mosquitos
 * Los datos se cargan desde la API /api/?route=... con fetch().
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Mapa de riesgo';
$active_page = 'mapa';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

// Evitar que el contenido protegido pueda quedar en la caché del navegador.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require __DIR__ . '/inc/cabecera.php';
?>

  <!-- PORTADA -->
  <section class="hero" aria-labelledby="hero-titulo">
    <div class="hero-texto">
      <h2 id="hero-titulo">¿Dónde se crían los mosquitos?</h2>
      <p>Detectá y reportá los criaderos de tu barrio. Entre todos evitamos el
         <strong>dengue, zika y chikungunya</strong>. Un reporte = un criadero menos.</p>
      <div class="hero-acciones">
        <a href="<?= e(BASE_URL) ?>/reportar.php" class="btn-hero">Reportar un criadero
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        <a href="#mapa" class="btn-hero-sec">Ver el mapa de riesgo</a>
      </div>
      <p class="hero-kpis" data-js-hero-kpis aria-live="polite"></p>
    </div>
    <div class="hero-logo" aria-hidden="true">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-casza-hero.webp" alt="" width="1172" height="896" fetchpriority="high">
    </div>
  </section>

  <!-- ALERTA CLIMÁTICA -->
  <section class="panel" id="clima">
    <p class="loading">Cargando datos climáticos…</p>
  </section>

  <?php require __DIR__ . '/inc/mapa.php'; ?>

  <?php require __DIR__ . '/inc/kpis.php'; ?>

<?php require __DIR__ . '/inc/scripts.php'; ?>
