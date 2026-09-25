<?php
/**
 * Pie de página común (inicio, comentarios y test de síntomas): logos + equipo.
 * Uso: require __DIR__ . '/inc/footer.php';
 */
?>
<footer class="pie">
  <div class="container">
    <div class="pie-logos">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-ipf.png" alt="Instituto Politécnico Formosa &quot;Dr. Alberto Marcelo Zorrilla&quot;" class="pie-logo pie-logo-ancho" loading="lazy">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-rfa.png" alt="RFA El Colorado" class="pie-logo" loading="lazy">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-ia.png" alt="Inteligencia Artificial" class="pie-logo" loading="lazy">
    </div>
    <p class="pie-evento">🦟 <strong>CaszaMosqui</strong> · FormosaHack 2026</p>
    <p class="pie-equipo">
      <strong>Ocampo Guillermo</strong> · <strong>Ramirez Lucas</strong> · <strong>Saade David</strong>
    </p>
  </div>
</footer>
