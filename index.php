<?php
/**
 * CaszaMosqui — Dashboard principal
 * FormosaHack 2026 · Desafío: enfermedades transmitidas por mosquitos
 * Los datos se cargan desde la API /api/?route=... con fetch().
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

// Evitar que el contenido protegido pueda quedar en la caché del navegador.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(APP_NAME) ?> · <?= e(APP_TAGLINE) ?></title>
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
  <?php require __DIR__ . '/inc/acceso-admin.php'; ?>
  <div class="container">
    <a class="brand" href="<?= e(BASE_URL) ?>/" aria-label="<?= e(APP_NAME) ?> · inicio">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-emblema.png" alt="" class="logo-img" width="52" height="59">
      <span class="brand-text">
        <strong><?= e(APP_NAME) ?></strong>
        <span><?= e(APP_TAGLINE) ?></span>
      </span>
    </a>
    <nav>
      <a href="#" data-nav="mapa" class="active">Mapa de riesgo</a>
      <a href="#" data-nav="nuevo">+ Reportar</a>
      <a href="#" data-nav="prevencion">Prevención</a>
      <a href="#" data-nav="salud">Centros de salud</a>
      <a href="#" data-nav="quiz">Cuestionario</a>
      <a href="<?= e(BASE_URL) ?>/test-sintomas.php">Test de Síntomas</a>
      <a href="<?= e(BASE_URL) ?>/comentarios.php">Comentarios</a>
    </nav>
  </div>
</header>

<main class="container">

  <!-- PORTADA -->
  <section class="hero" aria-labelledby="hero-titulo">
    <div class="hero-texto">
      <h2 id="hero-titulo">¿Dónde se crían los mosquitos?</h2>
      <p>Detectá y reportá los criaderos de tu barrio. Entre todos evitamos el
         <strong>dengue, zika y chikungunya</strong>. Un reporte = un criadero menos.</p>
      <div class="hero-acciones">
        <a href="#" data-nav="nuevo" class="btn-hero">Reportar un criadero
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        <a href="#" data-nav="mapa" class="btn-hero-sec">Ver el mapa de riesgo</a>
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

  <!-- FORMULARIO -->
  <section class="panel" id="nuevo">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>📢 Reportar un criadero</h2>
    <p class="sub">Elegí el tipo de situación y el barrio. Tu reporte se suma al mapa de riesgo al instante.</p>
    <form data-js-form>
      <div class="grid-3">
        <label>
          Tipo de criadero <span class="req">*</span>
          <select name="tipo_id" required></select>
        </label>
        <label>
          Barrio <span class="req">*</span>
          <select name="barrio_id" required></select>
        </label>
        <label>
          Referencia
          <input type="text" name="referencia" placeholder="Ej.: Pueyrredón y Cayo Novoa Gil">
        </label>
      </div>
      <label>
        Título <span class="req">*</span>
        <input type="text" name="titulo" maxlength="120" required placeholder="Ej.: Baldes con agua en obra abandonada">
      </label>
      <label>
        Descripción <span class="req">*</span>
        <textarea name="descripcion" rows="4" required placeholder="Contanos qué viste y dónde… puede haber larvas o mosquitos."></textarea>
      </label>

      <!-- Opcionales: ubicación GPS y foto (solo las ve el administrador) -->
      <div class="reporte-extras">
        <fieldset class="reporte-extra">
          <legend>📍 Ubicación exacta <span class="opcional">(opcional)</span></legend>
          <p class="ayuda">Ayuda a encontrar el criadero. Solo la ve el administrador.</p>
          <div class="extra-botones">
            <button type="button" class="btn-ghost" data-js-gps>📍 Usar mi ubicación</button>
            <button type="button" class="btn-ghost" data-js-marcar>🗺️ Marcar en el mapa</button>
            <button type="button" class="btn-quitar" data-js-quitar-ubic hidden>✕ Quitar ubicación</button>
          </div>
          <p class="extra-estado" data-js-gps-estado aria-live="polite"></p>
          <div class="mini-mapa" data-js-mini-mapa hidden></div>
        </fieldset>
        <fieldset class="reporte-extra">
          <legend>📷 Foto <span class="opcional">(opcional)</span></legend>
          <p class="ayuda">Sacá una foto del criadero o elegí una de tu galería. Solo la ve el administrador.</p>
          <div class="extra-botones">
            <button type="button" class="btn-ghost" data-js-foto-camara>📷 Sacar foto</button>
            <button type="button" class="btn-ghost" data-js-foto-galeria>🖼️ Elegir de la galería</button>
          </div>
          <input type="file" accept="image/*" capture="environment" data-js-foto-input-camara hidden>
          <input type="file" accept="image/*" data-js-foto-input-galeria hidden>
          <p class="extra-estado" data-js-foto-estado aria-live="polite"></p>
          <div class="foto-preview" data-js-foto-preview hidden>
            <img alt="Vista previa de la foto del criadero" data-js-foto-img>
            <button type="button" class="btn-quitar" data-js-quitar-foto>✕ Quitar foto</button>
          </div>
        </fieldset>
      </div>
      <button type="submit">Reportar criadero</button>
      <p class="form-msg" data-js-form-msg aria-live="polite"></p>
    </form>
  </section>

  <!-- PREVENCIÓN -->
  <section class="panel" id="prevencion">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <div class="prev-hero">
      <div class="prev-hero-texto">
        <span class="prev-etiqueta">Guía de prevención</span>
        <h2>Sin agua estancada, no hay mosquito 🦟</h2>
        <p>El <strong>Aedes aegypti</strong>, que transmite dengue, zika y chikungunya, pone sus huevos en
           <strong>agua limpia y quieta</strong> cerca de las casas. Cortar su ciclo depende de lo que hacemos en cada patio.</p>
      </div>
      <div class="prev-hero-dato">
        <strong>7 a 10</strong>
        <span>días tarda en pasar de huevo a mosquito con calor</span>
      </div>
    </div>

    <h3 class="prev-titulo">Las 3 acciones que más sirven</h3>
    <div class="prev-claves">
      <article class="prev-clave"><span class="prev-num">1</span><span class="prev-ico">🪣</span>
        <h4>Tapá</h4><p>Tanques, aljibes y todo recipiente que guarde agua, con tapa bien ajustada.</p></article>
      <article class="prev-clave"><span class="prev-num">2</span><span class="prev-ico">🔄</span>
        <h4>Vaciá y dá vuelta</h4><p>Baldes, macetas, bebederos y juguetes: boca abajo o vacíos, una vez por semana.</p></article>
      <article class="prev-clave"><span class="prev-num">3</span><span class="prev-ico">🗑️</span>
        <h4>Tirá</h4><p>Latas, botellas, cubiertas y cacharros que no uses. Menos objetos, menos criaderos.</p></article>
    </div>

    <h3 class="prev-titulo">Así crece el mosquito (por eso la revisión es semanal)</h3>
    <ol class="prev-ciclo">
      <li><span>🥚</span><strong>Huevo</strong><small>pegado a la pared del recipiente; resiste meses en seco</small></li>
      <li><span>🐛</span><strong>Larva</strong><small>nace cuando el recipiente se moja con la lluvia</small></li>
      <li><span>⏳</span><strong>Pupa</strong><small>última etapa dentro del agua</small></li>
      <li><span>🦟</span><strong>Mosquito</strong><small>pica de día, sobre todo al amanecer y al atardecer</small></li>
    </ol>

    <h3 class="prev-titulo">Revisá tu casa</h3>
    <div class="tips prev-tips">
      <article class="tip" style="--c:#0ea5e9"><span class="tip-ico">🪣</span><h3>Descacharrá</h3><p>Tirá latas, botellas, baldes y cacharros que junten agua.</p></article>
      <article class="tip" style="--c:#6366f1"><span class="tip-ico">🛢️</span><h3>Tapá los tanques</h3><p>Tanques y recipientes grandes siempre con tapa bien ajustada.</p></article>
      <article class="tip" style="--c:#f59e0b"><span class="tip-ico">🛞</span><h3>Neumáticos</h3><p>Guardalos bajo techo o perforalos para que no junten agua.</p></article>
      <article class="tip" style="--c:#14b8a6"><span class="tip-ico">💧</span><h3>Vaciá y limpiá</h3><p>Platitos de macetas, bebederos y piletas: semanal, sin agua estancada.</p></article>
      <article class="tip" style="--c:#84cc16"><span class="tip-ico">🧹</span><h3>Limpiá canaletas</h3><p>Hojas y tierra en desagües dejan charcos ideales para larvas.</p></article>
      <article class="tip" style="--c:#ec4899"><span class="tip-ico">🛡️</span><h3>Protegé tu casa</h3><p>Mosquiteros, espirales, repelente y ropa clara en horas de actividad.</p></article>
    </div>

    <div class="prev-alerta">
      <div class="prev-alerta-ico">🚨</div>
      <div class="prev-alerta-texto">
        <h3>Síntomas de alarma del dengue</h3>
        <ul>
          <li>Fiebre alta</li><li>Dolor detrás de los ojos</li><li>Dolor muscular y articular</li><li>Sarpullido</li>
        </ul>
        <p><strong>No te automediques</strong> (la aspirina puede complicar el dengue): consultá al <a href="#salud" data-nav="salud">centro de salud más cercano</a>.</p>
      </div>
      <a class="prev-alerta-btn" href="<?= e(BASE_URL) ?>/test-sintomas.php">Hacer el test de síntomas →</a>
    </div>
  </section>

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

</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.REPORTES_ACCESS = <?= $reportes_logueado ? 'true' : 'false' ?>;
  window.CENTROS_SALUD = <?= json_encode(require __DIR__ . '/inc/centros-salud.php', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/clima.js?v=20260925-vivo"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/calles.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=20261113-portada"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/leaflet.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/geo.js?v=20261019-gps"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/mapa-real.js?v=20261112-salud"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/centros-salud.js?v=20261112-salud"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/reporte-extra.js?v=20261019-gps"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/offline.js?v=20261111-app"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/chatbot.js"></script>
</body>
</html>