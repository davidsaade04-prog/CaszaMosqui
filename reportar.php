<?php
/**
 * CaszaMosqui — Reportar un criadero (página independiente)
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Reportar un criadero';
$active_page = 'reportar';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

require __DIR__ . '/inc/cabecera.php';
?>

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
          <p class="ayuda ayuda-cuidado">📸 Fotografiá <strong>el recipiente con agua</strong>: que no aparezcan personas, caras, patentes ni el frente de una casa. No hace falta señalar a ningún vecino.</p>
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
      <label class="consentimiento">
        <input type="checkbox" name="acepta" required>
        <span>Leí y acepto el <a href="<?= e(BASE_URL) ?>/privacidad.php" target="_blank" rel="noopener">aviso de privacidad</a>.
          Usamos estos datos solo para el mapa de riesgo y los operativos contra el mosquito. La foto y la ubicación exacta
          solo las ve el equipo y se borran a los 90 días de controlado el criadero. No escribas teléfonos ni nombres de otras personas.</span>
      </label>
      <p class="acepta-pista" data-js-acepta-pista>☝️ Marcá la casilla del aviso de privacidad para habilitar el botón.</p>
      <button type="submit" disabled>Reportar criadero</button>
      <p class="form-msg" data-js-form-msg aria-live="polite"></p>
    </form>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
