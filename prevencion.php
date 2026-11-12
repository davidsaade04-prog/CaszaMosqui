<?php
/**
 * CaszaMosqui — Guía de prevención (página independiente)
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Prevención';
$active_page = 'prevencion';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

require __DIR__ . '/inc/cabecera.php';
?>

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
        <p><strong>No te automediques</strong> (la aspirina puede complicar el dengue): consultá al
          <a href="<?= e(BASE_URL) ?>/centros-salud.php">centro de salud más cercano</a>.</p>
      </div>
      <a class="prev-alerta-btn" href="<?= e(BASE_URL) ?>/test-sintomas.php">Hacer el test de síntomas →</a>
    </div>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
