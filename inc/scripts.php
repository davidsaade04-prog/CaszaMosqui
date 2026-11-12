<?php
/**
 * Pie de página y scripts comunes de CaszaMosqui.
 * Debe incluirse al final de cada página, después del contenido.
 * Uso: require __DIR__ . '/inc/scripts.php';
 */
?>
</main>

<?php require __DIR__ . '/footer.php'; ?>

<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.REPORTES_ACCESS = <?= !empty($reportes_logueado) ? 'true' : 'false' ?>;
  window.CENTROS_SALUD = <?= json_encode(require __DIR__ . '/centros-salud.php', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/clima.js?v=20260925-vivo"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/calles.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=20261113-portada"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/leaflet.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/geo.js?v=20261019-gps"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/mapa-real.js?v=20261113-portada"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/centros-salud.js?v=20261113-portada"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/reporte-extra.js?v=20261019-gps"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/offline.js?v=20261111-app"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/chatbot.js"></script>
</body>
</html>
