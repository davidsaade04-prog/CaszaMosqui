<?php
/**
 * Test de Síntomas — CaszaMosqui
 * FormosaHack 2026 · Detección temprana de dengue/zika/chikungunya
 */
require_once __DIR__ . '/inc/helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test de Síntomas · <?= e(APP_NAME) ?></title>
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" type="image/jpeg">
  <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css">
  <style>
    .test-container { max-width: 720px; margin: 0 auto; }
    .test-card { background: var(--panel); border: 1px solid var(--borde); border-radius: var(--radio); box-shadow: var(--sombra); padding: 24px; margin-bottom: 16px; }
    .test-card h3 { margin: 0 0 16px; font-size: 1.15rem; color: #0f172a; }
    .sintoma { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--borde); }
    .sintoma:last-child { border-bottom: none; }
    .sintoma input[type="checkbox"] { width: 22px; height: 22px; accent-color: var(--acento); cursor: pointer; flex-shrink: 0; }
    .sintoma label { margin: 0; font-weight: 500; font-size: .98rem; cursor: pointer; flex: 1; }
    .sintoma .gravedad { font-size: .75rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; text-transform: uppercase; }
    .gravedad-alta { background: #fee2e2; color: #b91c1c; }
    .gravedad-media { background: #fef3c7; color: #b45309; }
    .gravedad-baja { background: #dcfce7; color: #166534; }
    .btn-alerta { width: 100%; padding: 14px; font-size: 1.05rem; font-weight: 700; background: var(--rojo); color: #fff; border: none; border-radius: 10px; cursor: pointer; transition: background .15s; }
    .btn-alerta:hover { background: #b91c1c; }
    .btn-alerta:disabled { background: #fca5a5; cursor: not-allowed; }
    .resultado { display: none; margin-top: 24px; padding: 20px; border-radius: 12px; text-align: center; animation: fadeIn .3s; }
    .resultado.alerta { display: block; background: #fef2f2; border: 2px solid var(--rojo); color: #b91c1c; }
    .resultado.ok { display: block; background: #f0fdf4; border: 2px solid var(--verde); color: #166534; }
    .resultado h2 { margin: 0 0 12px; font-size: 1.4rem; }
    .resultado p { margin: 0 0 16px; font-size: 1rem; line-height: 1.6; }
    
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
  </style>
</head>
<body>

<header class="topbar">
  <div class="container">
    <div class="brand">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" alt="<?= e(APP_NAME) ?>" class="logo-img">
      <div>
        <h1><?= e(APP_NAME) ?></h1>
        <p>Test de Síntomas — Detección temprana</p>
      </div>
    </div>
  </div>
</header>

<main class="container test-container">

  <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>

  <section class="test-card">
    <h3>🩺 Verificá tus síntomas</h3>
    <p style="color: var(--suave); margin-bottom: 16px;">
      Marcá los síntomas que tenés ahora. <strong>Esto no es un diagnóstico médico</strong>,
      pero te ayuda a saber si debés ir urgente a un centro de salud.
    </p>

    <form id="form-sintomas">
      <!-- Síntomas de ALERTA (dengue grave) -->
      <div class="sintoma">
        <input type="checkbox" id="s1" name="sintomas[]" value="fiebre_alta" data-gravedad="alta">
        <label for="s1">Fiebre alta (> 38.5°C) <span class="gravedad gravedad-alta">ALERTA</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s2" name="sintomas[]" value="dolor_abdominal" data-gravedad="alta">
        <label for="s2">Dolor abdominal intenso y sostenido <span class="gravedad gravedad-alta">ALERTA</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s3" name="sintomas[]" value="vomitos" data-gravedad="alta">
        <label for="s3">Vómitos persistentes (3 o más en 24h) <span class="gravedad gravedad-alta">ALERTA</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s4" name="sintomas[]" value="sangrado" data-gravedad="alta">
        <label for="s4">Sangrado (encías, nariz, heces, orina) <span class="gravedad gravedad-alta">ALERTA</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s5" name="sintomas[]" value="somnolencia" data-gravedad="alta">
        <label for="s5">Somnolencia, irritabilidad o confusión <span class="gravedad gravedad-alta">ALERTA</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s6" name="sintomas[]" value="dificultad_respirar" data-gravedad="alta">
        <label for="s6">Dificultad para respirar <span class="gravedad gravedad-alta">ALERTA</span></label>
      </div>

      <!-- Síntomas clásicos de dengue/zika/chikungunya -->
      <div class="sintoma">
        <input type="checkbox" id="s7" name="sintomas[]" value="fiebre" data-gravedad="media">
        <label for="s7">Fiebre (38–38.5°C) <span class="gravedad gravedad-media">Común</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s8" name="sintomas[]" value="dolor_cabeza" data-gravedad="media">
        <label for="s8">Dolor de cabeza intenso (detrás de los ojos) <span class="gravedad gravedad-media">Común</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s9" name="sintomas[]" value="dolor_articular" data-gravedad="media">
        <label for="s9">Dolor muscular y articular fuerte <span class="gravedad gravedad-media">Común</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s10" name="sintomas[]" value="sarpullido" data-gravedad="media">
        <label for="s10">Sarpullido / erupción en piel <span class="gravedad gravedad-media">Común</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s11" name="sintomas[]" value="nauseas" data-gravedad="media">
        <label for="s11">Náuseas o vómitos ocasionales <span class="gravedad gravedad-media">Común</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s12" name="sintomas[]" value="dolor_ojos" data-gravedad="media">
        <label for="s12">Dolor detrás de los ojos (retroocular) <span class="gravedad gravedad-media">Común</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s13" name="sintomas[]" value="cansancio" data-gravedad="baja">
        <label for="s13">Cansancio extremo / debilidad <span class="gravedad gravedad-baja">Leve</span></label>
      </div>
      <div class="sintoma">
        <input type="checkbox" id="s14" name="sintomas[]" value="conjuntivitis" data-gravedad="baja">
        <label for="s14">Ojos rojos / conjuntivitis (típico Zika) <span class="gravedad gravedad-baja">Leve</span></label>
      </div>
    </form>

    <button type="button" class="btn-alerta" id="btn-evaluar" disabled>
      Evaluar síntomas
    </button>
  </section>

  <!-- Resultado -->
  <div id="resultado" class="resultado" role="alert" aria-live="assertive"></div>

  <section class="test-card" style="background: #f0fdfa; border-color: #99f6e4;">
    <h3>📍 Dónde atenderse en El Colorado</h3>
    <ul style="margin: 0; padding-left: 20px; color: var(--texto);">
      <li><strong>Hospital Distrital El Colorado</strong> — Guardia 24h</li>
      <li><strong>Centro de Salud "Dr. Ramón Carrillo"</strong> — Av. San Martín</li>
      <li><strong>CAPS Barrio San Martín</strong> — Calle 25 de Mayo</li>
      <li><strong>CAPS Barrio 2 de Abril</strong> — Calle 25 de Mayo</li>
    </ul>
    <p style="margin-top: 12px; font-size: .9rem; color: var(--suave);">
      🚨 <strong>Si tenés síntomas de ALERTA (rojos), no esperes: andá ya a la guardia.</strong>
    </p>
  </section>

</main>

<footer>
  <div class="container">
    <p>🦟 <strong>CaszaMosqui</strong> · Equipo FormosaHack 2026 · Test de Síntomas</p>
  </div>
</footer>

<script>
  const form = document.getElementById('form-sintomas');
  const btn = document.getElementById('btn-evaluar');
  const res = document.getElementById('resultado');

  const ALERTA = ['fiebre_alta','dolor_abdominal','vomitos','sangrado','somnolencia','dificultad_respirar'];

  form?.addEventListener('change', () => {
    const checked = form.querySelectorAll('input:checked').length;
    btn.disabled = checked === 0;
  });

  btn?.addEventListener('click', () => {
    const seleccionados = Array.from(form.querySelectorAll('input:checked')).map(i => i.value);
    const tieneAlerta = seleccionados.some(s => ALERTA.includes(s));
    const total = seleccionados.length;

    if (tieneAlerta) {
      res.className = 'resultado alerta';
      res.innerHTML = `
        <h2>⚠️ ¡ACUDÍ URGENTE A UN SANATORIO!</h2>
        <p>Tenés <strong>${total} síntoma(s)</strong> y al menos uno es de <strong>ALERTA ROJA</strong>
           (dengue grave / shock).<br>
           No te automediques. No esperes a que pase.<br>
           <strong>Andá YA a la guardia más cercana.</strong></p>
        <a href="${window.BASE_URL || '/formosahack-2026'}/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>`;
    } else if (total >= 3) {
      res.className = 'resultado alerta';
      res.innerHTML = `
        <h2>🩺 Consultá hoy mismo</h2>
        <p>Tenés <strong>${total} síntoma(s)</strong> compatibles con dengue/zika/chikungunya.
           Aunque no tengas signos de alarma, <strong>requerís evaluación médica</strong>
           para confirmar y recibir tratamiento.</p>
        <a href="${window.BASE_URL || '/formosahack-2026'}/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>`;
    } else if (total > 0) {
      res.className = 'resultado ok';
      res.innerHTML = `
        <h2>✅ Pocos síntomas, pero atent@</h2>
        <p>Tenés <strong>${total} síntoma(s)</strong> leves. Podría ser inicio de dengue u otra enfermedad.
           <strong>Controlá tu temperatura</strong> y si aparece fiebre alta o alguno de los
           síntomas de ALERTA (rojos), <strong>andá al centro de salud</strong>.</p>
        <a href="${window.BASE_URL || '/formosahack-2026'}/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>`;
    } else {
      res.className = 'resultado ok';
      res.innerHTML = `
        <h2>👍 Sin síntomas marcados</h2>
        <p>No seleccionaste ningún síntoma. Seguí usando repelente, descacharrando
           y participando en CaszaMosqui reportando criaderos.</p>
        <a href="${window.BASE_URL || '/formosahack-2026'}/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>`;
    }
  });
</script>
</body>
</html>