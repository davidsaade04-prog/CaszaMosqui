<?php
/**
 * CaszaMosqui — Acerca de nosotros: el equipo, el problema y la solución.
 * Los datos del equipo se editan en inc/equipo.php.
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Acerca de nosotros';
$active_page = 'acerca';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

$datos  = require __DIR__ . '/inc/equipo.php';
$equipo = $datos['equipo'];

require __DIR__ . '/inc/cabecera.php';
?>

  <section class="panel acerca" id="acerca">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>👥 Acerca de nosotros</h2>
    <p class="sub">Somos un equipo de El Colorado, Formosa, profesionales en el uso de la inteligencia artificial. Creamos CaszaMosqui en el <strong>FormosaHack 2026</strong> para atacar un problema que vive nuestra comunidad todos los veranos.</p>

    <div class="acerca-problema">
      <article>
        <h3>El problema</h3>
        <p>El dengue, el zika y la chikungunya los transmite el mosquito <em>Aedes aegypti</em>, que se cría en <strong>agua limpia y quieta</strong> dentro y alrededor de las casas: tachos, cubiertas, floreros, canaletas. Los operativos de descacharrado llegan tarde porque <strong>nadie sabe dónde están los criaderos</strong> hasta que ya hay enfermos.</p>
      </article>
      <article>
        <h3>Nuestra solución</h3>
        <p>CaszaMosqui pone al vecino como primer vigilante: <strong>reporta un criadero en un minuto</strong>, con ubicación y foto, incluso sin señal. Los reportes arman un <strong>mapa de riesgo por barrio</strong> en tiempo real, que suma el clima, y el municipio planifica los operativos donde hacen falta. Además, acerca la información que importa: cómo prevenir, qué hacer con síntomas y <strong>dónde atenderse</strong>.</p>
      </article>
    </div>

    <ul class="acerca-logros" aria-label="Qué logra CaszaMosqui">
      <li><strong>Reportes en un minuto</strong><span>desde el celular, con GPS, foto y modo sin internet</span></li>
      <li><strong>Mapa de riesgo vivo</strong><span>semáforo por barrio que cambia con cada reporte y con el clima</span></li>
      <li><strong>Operativos con datos</strong><span>panel para el municipio con mapa de calor y exportación a Excel</span></li>
      <li><strong>Salud al alcance</strong><span>test de síntomas, centros de salud con horarios y cómo llegar</span></li>
    </ul>

    <h3 class="acerca-titulo-equipo">El equipo</h3>
    <div class="equipo-grid">
      <?php foreach ($equipo as $m): ?>
      <article class="equipo-card">
        <?php if (!empty($m['foto'])): ?>
          <img src="<?= e(BASE_URL) ?>/<?= e($m['foto']) ?>" alt="<?= e($m['nombre']) ?>" class="equipo-foto" loading="lazy">
        <?php else: ?>
          <span class="equipo-inicial" aria-hidden="true"><?= e(mb_substr($m['nombre'], 0, 1)) ?></span>
        <?php endif; ?>
        <h4><?= e($m['nombre']) ?></h4>
        <?php if (!empty($m['rol'])): ?><p class="equipo-rol"><?= e($m['rol']) ?></p><?php endif; ?>
        <?php if (!empty($m['titulos'])): ?>
          <ul class="equipo-titulos">
            <?php foreach ($m['titulos'] as $t): ?><li>🎓 <?= e($t) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if (!empty($m['linkedin'])): ?>
          <a class="equipo-link" href="<?= e($m['linkedin']) ?>" target="_blank" rel="noopener">LinkedIn ↗</a>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>

    <p class="acerca-instituciones">Desarrollado en el marco del <strong>FormosaHack 2026</strong>, con el acompañamiento del Instituto Politécnico Formosa "Dr. Alberto Marcelo Zorrilla" y el Instituto República Federal de Alemania de El Colorado.</p>

    <div class="acerca-cta">
      <a href="<?= e(BASE_URL) ?>/contacto.php" class="btn-hero">Hablemos de tu proyecto →</a>
      <a href="<?= e(BASE_URL) ?>/ayuda.php" class="btn-hero-sec btn-hero-sec-claro">Ver cómo funciona</a>
    </div>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
