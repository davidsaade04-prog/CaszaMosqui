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
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" type="image/jpeg">
  <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css?v=20260925-admin">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/chatbot.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/clima.css?v=20260925-vivo">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/mapa-plano.css">
</head>
<body>

<header class="topbar">
  <?php require __DIR__ . '/inc/acceso-admin.php'; ?>
  <div class="container">
    <div class="brand">
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?></h1>
        <p><?= e(APP_TAGLINE) ?></p>
      </div>
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" alt="<?= e(APP_NAME) ?>" class="logo-img">
    </div>
    <nav>
      <a href="#" data-nav="mapa" class="active">Mapa de riesgo</a>
      <a href="#" data-nav="nuevo">+ Reportar</a>
      <a href="#" data-nav="prevencion">Prevención</a>
      <a href="#" data-nav="quiz">Cuestionario</a>
      <a href="<?= e(BASE_URL) ?>/test-sintomas.php">Test de Síntomas</a>
      <a href="<?= e(BASE_URL) ?>/comentarios.php">Comentarios</a>
    </nav>
  </div>
</header>

<main class="container">

  <!-- HERO -->
  <section class="hero">
    <div>
      <h2>¿Dónde se crían los mosquitos?</h2>
      <p>Detectá y reportá los criaderos de tu barrio. Entre todos evitamos el
         <strong>dengue, zika y chikungunya</strong>. 📍 Un reporte = un criadero menos.</p>
      <a href="#" data-nav="nuevo" class="btn-hero">Reportar un criadero →</a>
    </div>
    <div class="hero-kpis" data-js-hero-kpis></div>
  </section>

  <!-- ALERTA CLIMÁTICA -->
  <section class="panel" id="clima">
    <p class="loading">Cargando datos climáticos…</p>
  </section>

  <?php require __DIR__ . '/inc/mapa.php'; ?>

  <?php require __DIR__ . '/inc/kpis.php'; ?>

  <!-- TIPOS MÁS COMUNES -->
  <section class="panel">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>🐞 Criaderos más reportados</h2>
    <div class="sectores" data-js-tipos>
      <p class="loading">Cargando…</p>
    </div>
  </section>

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
        <p><strong>No te automediques</strong> (la aspirina puede complicar el dengue): consultá al centro de salud más cercano.</p>
      </div>
      <a class="prev-alerta-btn" href="<?= e(BASE_URL) ?>/test-sintomas.php">Hacer el test de síntomas →</a>
    </div>
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
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/clima.js?v=20260925-vivo"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/calles.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=20260925-admin"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/chatbot.js"></script>
</body>
</html>