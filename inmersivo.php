<?php
/**
 * CaszaMosqui — Modo inmersivo
 * El Colorado en 3D: cada barrio es una torre de luz que crece con sus criaderos activos
 * y toma el color de su nivel de riesgo. Alrededor flotan las opciones de la página
 * (reportar, centros de salud, comentarios…), que se abren en un panel sin salir del mapa.
 * Motor 3D: Three.js r170 (assets/vendor/three, licencia MIT), servido localmente.
 */
require_once __DIR__ . '/inc/helpers.php';

$centros = require __DIR__ . '/inc/centros-salud.php';
$v = '20261117-leyenda';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Modo inmersivo · <?= e(APP_NAME) ?></title>
  <meta name="description" content="El Colorado en 3D: el riesgo de cada barrio y todas las herramientas de CaszaMosqui a mano.">
  <link rel="manifest" href="<?= e(BASE_URL) ?>/manifest.webmanifest">
  <meta name="theme-color" content="#060b16">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/app/favicon-48.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/app/apple-touch-icon.png">
  <link rel="preload" href="<?= e(BASE_URL) ?>/assets/vendor/three/three.module.min.js" as="script" crossorigin>
  <link rel="preload" href="<?= e(BASE_URL) ?>/assets/img/plano-holo.webp" as="image" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/chatbot.css?v=20261116-arriba">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/inmersivo.css?v=<?= $v ?>">
  <script type="importmap">
    { "imports": { "three": "<?= e(BASE_URL) ?>/assets/vendor/three/three.module.min.js" } }
  </script>
</head>
<body class="inm">

  <!-- Escena 3D -->
  <canvas class="inm-escena" data-js-escena aria-label="Mapa 3D de El Colorado con el nivel de riesgo de cada barrio"></canvas>
  <div class="inm-etiquetas" data-js-etiquetas aria-hidden="true"></div>

  <!-- Pantalla de carga -->
  <div class="inm-carga" data-js-carga>
    <img src="<?= e(BASE_URL) ?>/assets/img/logo-emblema.png" alt="" width="104" height="118">
    <p data-js-carga-texto>Encendiendo el mapa de El Colorado…</p>
    <span class="inm-carga-barra"><span></span></span>
  </div>

  <!-- Barra superior -->
  <header class="inm-hud">
    <a class="inm-marca" href="<?= e(BASE_URL) ?>/?modo=normal" aria-label="Ir al modo normal de CaszaMosqui">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-emblema.png" alt="" width="40" height="45">
      <span><strong>CaszaMosqui</strong><small>Modo inmersivo</small></span>
    </a>
    <div class="inm-kpis" data-js-kpis aria-live="polite">
      <span class="inm-kpi"><b data-kpi="activos">–</b> criaderos activos</span>
      <span class="inm-kpi"><b data-kpi="alto">–</b> barrios en riesgo alto</span>
      <span class="inm-kpi inm-kpi-clima" data-js-clima hidden></span>
    </div>
    <a class="inm-salir" href="<?= e(BASE_URL) ?>/?modo=normal">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg>
      Modo normal
    </a>
  </header>

  <!-- Opciones flotantes -->
  <nav class="inm-orbes" aria-label="Opciones">
    <button type="button" class="inm-orbe inm-orbe-principal" data-abrir="reportar" style="--i:0">
      <span class="inm-orbe-ico" aria-hidden="true">📢</span><span class="inm-orbe-txt">Reportar criadero</span>
    </button>
    <button type="button" class="inm-orbe inm-orbe-salud" data-salud3d aria-pressed="false" style="--i:1">
      <span class="inm-orbe-ico" aria-hidden="true">🏥</span><span class="inm-orbe-txt">Centros de salud</span>
    </button>
    <button type="button" class="inm-orbe" data-abrir="comentarios" style="--i:2">
      <span class="inm-orbe-ico" aria-hidden="true">💬</span><span class="inm-orbe-txt">Comentarios</span>
    </button>
    <button type="button" class="inm-orbe" data-abrir="sintomas" style="--i:3">
      <span class="inm-orbe-ico" aria-hidden="true">🩺</span><span class="inm-orbe-txt">Test de síntomas</span>
    </button>
    <button type="button" class="inm-orbe" data-abrir="prevencion" style="--i:4">
      <span class="inm-orbe-ico" aria-hidden="true">🛡️</span><span class="inm-orbe-txt">Prevención</span>
    </button>
    <button type="button" class="inm-orbe" data-abrir="cuestionario" style="--i:5">
      <span class="inm-orbe-ico" aria-hidden="true">🧠</span><span class="inm-orbe-txt">Cuestionario</span>
    </button>
    <button type="button" class="inm-orbe" data-abrir="ayuda" style="--i:6">
      <span class="inm-orbe-ico" aria-hidden="true">❓</span><span class="inm-orbe-txt">Ayuda</span>
    </button>
  </nav>

  <!-- Leyenda y ayuda de navegación -->
  <aside class="inm-leyenda" aria-label="Referencias">
    <p class="inm-leyenda-titulo">Riesgo por barrio</p>
    <span><i class="alto"></i>Alto</span><span><i class="medio"></i>Medio</span><span><i class="bajo"></i>Bajo</span>
    <span><i class="salud"></i>Salud</span>
    <p class="inm-gesto" data-js-gesto>Arrastrá para girar · acercá con la rueda o pellizcando · tocá una torre</p>
    <p class="inm-limite">Refleja reportes de vecinos (incluye sin verificar). Las casas son ilustrativas.
      <a href="<?= e(BASE_URL) ?>/privacidad.php#limites" target="_blank" rel="noopener">Límites y privacidad</a></p>
  </aside>

  <!-- Centros de salud: lista flotante del modo "enfocar salud" -->
  <section class="inm-salud" data-js-salud hidden aria-label="Centros de salud de El Colorado">
    <header>
      <h2>🏥 Centros de salud</h2>
      <button type="button" class="inm-ficha-x" data-salud3d aria-label="Cerrar centros de salud">✕</button>
    </header>
    <a class="inm-salud-107" href="tel:107"><span aria-hidden="true">🚑</span><span><strong>Emergencias 107</strong><small>Signos de alarma: llamá o andá a la guardia</small></span></a>
    <ul class="inm-salud-items" data-js-salud-items></ul>
    <button type="button" class="inm-salud-guia" data-abrir="salud">Ver la guía completa y el más cercano →</button>
  </section>

  <!-- Ficha del barrio o centro de salud tocado -->
  <section class="inm-ficha" data-js-ficha hidden aria-live="polite"></section>

  <!-- Panel con las secciones de la página -->
  <div class="inm-panel" data-js-panel hidden>
    <div class="inm-panel-fondo" data-js-panel-cerrar></div>
    <section class="inm-panel-hoja" role="dialog" aria-modal="true" aria-labelledby="inm-panel-titulo">
      <header>
        <h2 id="inm-panel-titulo" data-js-panel-titulo></h2>
        <a class="inm-panel-abrir" data-js-panel-link href="#" target="_blank" rel="noopener" title="Abrir en una pestaña nueva">↗</a>
        <button type="button" class="inm-panel-x" data-js-panel-cerrar aria-label="Cerrar y volver al mapa">✕</button>
      </header>
      <div class="inm-panel-cargando" aria-hidden="true"><span class="inm-carga-barra"><span></span></span>Abriendo…</div>
      <iframe data-js-panel-frame title="Sección de CaszaMosqui" allowtransparency="true" allow="geolocation; camera; clipboard-write; web-share"></iframe>
    </section>
  </div>

  <!-- Mosqui (chat) -->
  <button type="button" class="inm-mosqui" data-abrir-chat aria-label="Preguntale a Mosqui">🦟<span>¿Dudas?</span></button>

  <noscript><p class="inm-sin-3d">El modo inmersivo necesita JavaScript. <a href="<?= e(BASE_URL) ?>/?modo=normal">Ir al modo normal</a>.</p></noscript>

  <script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    window.CENTROS_SALUD = <?= json_encode($centros, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  </script>
  <script type="module" src="<?= e(BASE_URL) ?>/assets/js/inmersivo.js?v=<?= $v ?>"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/offline.js?v=20261116-instalar"></script>
  <script src="<?= e(BASE_URL) ?>/assets/js/chatbot.js?v=20261116-privacidad"></script>
</body>
</html>
