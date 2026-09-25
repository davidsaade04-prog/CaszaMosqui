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
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css?v=20260924-pagination">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/clima.css">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/mapa-plano.css">
</head>
<body>

<header class="topbar">
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
      <a href="#" data-nav="reportes">Reportes</a>
      <a href="#" data-nav="nuevo">+ Reportar</a>
      <a href="#" data-nav="prevencion">Prevención</a>
      <a href="#" data-nav="quiz">Quiz</a>
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

  <!-- MAPA DE RIESGO -->
  <section class="panel" id="mapa">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <div class="panel-head">
      <h2>🗺️ Mapa de riesgo por barrio</h2>
      <div class="mapa-filtros" role="group" aria-label="Filtrar barrios por nivel de riesgo">
        <button type="button" data-nivel="todos" class="activo">Todos</button>
        <button type="button" data-nivel="alto"><i class="dot alto"></i> Alto</button>
        <button type="button" data-nivel="medio"><i class="dot medio"></i> Medio</button>
        <button type="button" data-nivel="bajo"><i class="dot bajo"></i> Bajo</button>
      </div>
      <div class="mapa-controles" role="group" aria-label="Controles de zoom del mapa">
        <button type="button" id="zoom-in" aria-label="Acercar">🔍+</button>
        <button type="button" id="zoom-out" aria-label="Alejar">🔍−</button>
        <button type="button" id="zoom-reset" aria-label="Restablecer vista">⌂</button>
      </div>
    </div>
    <div class="mapa-contenedor">
      <!-- Sidebar con lista de barrios -->
      <aside class="mapa-sidebar" aria-label="Lista de barrios">
        <h3>📍 Barrios</h3>
        <ul data-js-barra-barrios>
          <li><button type="button" data-barrio="todos" class="activo">Todos los barrios</button></li>
        </ul>
      </aside>
      <!-- Mapa con zoom/pan -->
      <div class="mapa-wrapper">
        <div class="mapa-imagen" data-js-mapa-imagen aria-label="Plano de barrios de El Colorado"></div>
        <div class="mapa-burbujas" data-js-mapa-burbujas aria-label="Burbujas de riesgo por barrio"></div>
      </div>
      <p class="mapa-nota">Plano oficial de barrios de El Colorado. Usá los botones <strong>🔍+ / 🔍−</strong> para zoom, <strong>⌂</strong> para reset. <strong>Clic en un barrio</strong> (lista o burbuja) para centrar y hacer zoom. Semáforo: <strong>rojo 5+</strong> criaderos sin controlar, <strong>amarillo 3-4</strong>, <strong>verde 0-2</strong>.</p>
    </div>
  </section>

  <!-- KPIs -->
  <section class="kpis" aria-label="Indicadores">
    <article class="kpi" data-kpi="total"><strong>—</strong><span>🦟 Criaderos reportados</span></article>
    <article class="kpi" data-kpi="pendiente"><strong>—</strong><span>⏳ Sin controlar</span></article>
    <article class="kpi" data-kpi="verificado"><strong>—</strong><span>🔎 Verificados</span></article>
    <article class="kpi" data-kpi="controlado"><strong>—</strong><span>✅ Controlados</span></article>
  </section>

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

  <!-- REPORTES -->
  <section class="panel" id="reportes">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>

    <?php if ($reportes_logueado): ?>
      <div class="reportes-sesion">
        <span>Sesión iniciada como <strong><?= e($reportes_usuario) ?></strong></span>
        <form method="post" action="<?= e(BASE_URL) ?>/reportes-auth.php" class="reportes-logout-form">
          <input type="hidden" name="accion" value="logout">
          <input type="hidden" name="csrf" value="<?= e($reportes_csrf) ?>">
          <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
      </div>
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
    <?php else: ?>
      <div class="reportes-login">
        <div class="reportes-login-icon" aria-hidden="true">🔒</div>
        <h2>Acceso a Reportes</h2>
        <p class="sub">Ingresá con tu usuario para consultar y gestionar los criaderos reportados por la comunidad.</p>
        <form method="post" action="<?= e(BASE_URL) ?>/reportes-auth.php" class="reportes-login-form">
          <input type="hidden" name="accion" value="login">
          <input type="hidden" name="csrf" value="<?= e($reportes_csrf) ?>">
          <label for="reportes-usuario">
            Usuario
            <input id="reportes-usuario" name="usuario" type="text" autocomplete="username" required autofocus>
          </label>
          <label for="reportes-contrasena">
            Contraseña
            <input id="reportes-contrasena" name="contrasena" type="password" autocomplete="current-password" required>
          </label>
          <?php if ($reportes_error): ?>
            <p class="reportes-login-error" role="alert"><?= e($reportes_error) ?></p>
          <?php endif; ?>
          <button type="submit" class="btn-login">Ingresar</button>
        </form>
        <p class="reportes-login-note">El acceso usa una sesión de PHP y se mantiene mientras la aplicación esté abierta.</p>
      </div>
    <?php endif; ?>
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
    <h2>📖 Guía rápida de prevención</h2>
    <p class="sub">El mosquito <strong>Aedes aegypti</strong> se cría en <strong>agua limpia y estancada</strong> cerca de casas. Eliminá sus criaderos:</p>
    <div class="tips">
      <article class="tip">🪣 <h3>Descacharrá</h3><p>Tirá latas, botellas, baldes y cacharros que junten agua.</p></article>
      <article class="tip">🛢️ <h3>Tapá los tanques</h3><p>Tanques y recipientes grandes siempre con tapa bien ajustada.</p></article>
      <article class="tip">🛞 <h3>Neumáticos</h3><p>Guardalos bajo techo o perforalos para que no junten agua.</p></article>
      <article class="tip">💧 <h3>Vaciá y limpiá</h3><p>Platitos de macetas, bebederos y piletas: semanal, sin agua estancada.</p></article>
      <article class="tip">🧹 <h3>Limpiá canaletas</h3><p>Hojas y tierra en desagües dejan charcos ideales para larvas.</p></article>
      <article class="tip">🛡️ <h3>Protegé tu casa</h3><p>Mosquiteros, espirales, repelente y ropa clara en horas de actividad.</p></article>
    </div>
    <p class="mapa-nota">🚨 <strong>Síntomas de alarma (dengue):</strong> fiebre alta, dolor detrás de los ojos, dolor muscular y articular, sarpullido. Ante estos síntomas, <strong>no te automediques</strong>: consultá al centro de salud más cercano.</p>
  </section>

  <!-- QUIZ -->
  <section class="panel" id="quiz">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>🎯 ¿Cuánto sabés sobre prevención?</h2>
    <p class="sub">Respondé el quiz y recibí tu veredicto. Compartilo con tu barrio para frenar al mosquito. 🦟</p>
    <div data-js-quiz>
      <p class="loading">Cargando quiz…</p>
    </div>
  </section>

</main>

<footer>
  <div class="container">
    <p>🦟 <strong>CaszaMosqui</strong> · Equipo FormosaHack 2026 · HTML + PHP + CSS + JS + MySQL ·
       Desafío: prevenir enfermedades transmitidas por mosquitos</p>
  </div>
</footer>

<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.REPORTES_ACCESS = <?= $reportes_logueado ? 'true' : 'false' ?>;
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/clima.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/calles.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=20260924-pagination"></script>
</body>
</html>