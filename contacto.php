<?php
/**
 * CaszaMosqui — Contacto: para llevar el sistema a otro municipio, redes y
 * propuestas de desarrollo web. Los datos se editan en inc/equipo.php.
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Contacto';
$active_page = 'contacto';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

$datos    = require __DIR__ . '/inc/equipo.php';
$contacto = $datos['contacto'];
$wa       = preg_replace('/\D/', '', $contacto['whatsapp'] ?? '');
$asunto   = rawurlencode('Consulta por CaszaMosqui');
$iconos   = ['Instagram' => '📸', 'Facebook' => '📘', 'TikTok' => '🎵', 'YouTube' => '▶️', 'LinkedIn' => '💼', 'X' => '✖️', 'GitHub' => '💻'];

require __DIR__ . '/inc/cabecera.php';
?>

  <section class="panel contacto" id="contacto">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>✉️ Contacto</h2>
    <p class="sub">¿Querés <strong>CaszaMosqui en tu municipio</strong>, una app a medida para tu organización o un sistema web para tu negocio? Escribinos: respondemos en el día.</p>

    <div class="contacto-grid">
      <div class="contacto-servicios">
        <h3>Qué hacemos</h3>
        <ul>
          <li><strong>CaszaMosqui para tu ciudad.</strong> Lo adaptamos a tus barrios, tus centros de salud y tu equipo de operativos, con capacitación incluida.</li>
          <li><strong>Sistemas web y apps a medida.</strong> Plataformas que funcionan en el celular, instalables y con modo sin internet.</li>
          <li><strong>Soluciones con inteligencia artificial.</strong> Asistentes, automatizaciones y análisis de datos para instituciones y negocios.</li>
          <li><strong>Mapas y datos comunitarios.</strong> Tableros, mapas de riesgo y reportes para tomar decisiones con información real.</li>
        </ul>
      </div>

      <div class="contacto-canales">
        <h3>Escribinos</h3>
        <?php if ($wa): ?>
          <a class="contacto-btn contacto-wa" href="https://wa.me/<?= e($wa) ?>?text=<?= $asunto ?>" target="_blank" rel="noopener">
            💬 WhatsApp<small><?= e($contacto['whatsapp_txt'] ?: $contacto['whatsapp']) ?></small></a>
        <?php endif; ?>
        <?php if (!empty($contacto['email'])): ?>
          <a class="contacto-btn contacto-mail" href="mailto:<?= e($contacto['email']) ?>?subject=<?= $asunto ?>">
            ✉️ Correo<small><?= e($contacto['email']) ?></small></a>
        <?php endif; ?>
        <?php if (!$wa && empty($contacto['email'])): ?>
          <p class="contacto-vacio">Los datos de contacto se están cargando. Mientras tanto, dejanos tu mensaje en <a href="<?= e(BASE_URL) ?>/comentarios.php">Comentarios</a>.</p>
        <?php endif; ?>
        <?php if (!empty($contacto['ciudad'])): ?><p class="contacto-ciudad">📍 <?= e($contacto['ciudad']) ?></p><?php endif; ?>

        <?php if (!empty($contacto['redes'])): ?>
          <h3>Seguinos</h3>
          <div class="contacto-redes">
            <?php foreach ($contacto['redes'] as $red => $url): ?>
              <a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($iconos[$red] ?? '🔗') ?> <?= e($red) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <p class="contacto-pie">Si sos vecino de El Colorado y querés avisar algo sobre la página, usá <a href="<?= e(BASE_URL) ?>/comentarios.php">Comentarios</a>; para reportar un criadero, <a href="<?= e(BASE_URL) ?>/reportar.php">Reportar</a>.</p>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
