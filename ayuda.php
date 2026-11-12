<?php
/**
 * CaszaMosqui — Ayuda: cómo usar la página, paso a paso, con capturas y videos.
 * Las capturas están en assets/img/ayuda/ y los videos en assets/video/ayuda/
 * (se generan con tests/capturas-ayuda.js contra una base de prueba).
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Ayuda';
$active_page = 'ayuda';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

require __DIR__ . '/inc/cabecera.php';

/** Captura de pantalla (webp) con su pie. */
function captura(string $nombre, string $pie): string
{
    $src = e(BASE_URL) . '/assets/img/ayuda/' . $nombre . '.webp';
    return '<figure class="ayuda-captura"><img src="' . $src . '" alt="' . e($pie) . '" loading="lazy" width="420" height="760">'
         . '<figcaption>' . e($pie) . '</figcaption></figure>';
}
/** Video instructivo (mp4, sin audio) con su pie. */
function video(string $nombre, string $pie): string
{
    $base = e(BASE_URL) . '/assets/video/ayuda/' . $nombre;
    return '<figure class="ayuda-video"><video controls muted playsinline preload="none" poster="' . $base . '.jpg" width="420" height="760">'
         . '<source src="' . $base . '.mp4" type="video/mp4">Tu navegador no reproduce el video.</video>'
         . '<figcaption>▶ ' . e($pie) . '</figcaption></figure>';
}
?>

  <section class="panel ayuda" id="ayuda">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>❓ Ayuda: cómo usar CaszaMosqui</h2>
    <p class="sub">Guía paso a paso de cada parte de la página, con capturas y videos. Si algo no te queda claro, el asistente <strong>Mosqui</strong> (abajo a la derecha) también responde preguntas sobre cómo usarla.</p>

    <nav class="ayuda-indice" aria-label="Temas de ayuda">
      <a href="#ay-instalar">📲 Instalar la app</a>
      <a href="#ay-reportar">📢 Reportar un criadero</a>
      <a href="#ay-sin-senal">📵 Sin señal</a>
      <a href="#ay-mapa">🗺️ Mapa de riesgo</a>
      <a href="#ay-salud">🏥 Centros de salud</a>
      <a href="#ay-sintomas">🩺 Test de síntomas</a>
      <a href="#ay-prevencion">🛡️ Prevención y cuestionario</a>
      <a href="#ay-mosqui">💬 Mosqui y comentarios</a>
      <a href="#ay-admin">🔐 Administrador</a>
    </nav>

    <article class="ayuda-tema" id="ay-instalar">
      <h3>📲 Instalar la app en el celular</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>Abrí <strong>caszamosqui.onrender.com</strong> en el navegador del celular. La primera vez puede tardar un minuto en despertar.</li>
          <li><strong>Android (Chrome):</strong> tocá el botón naranja <strong>📲 Instalar app</strong>, o abrí el menú <strong>⋮</strong> y elegí <strong>Instalar app</strong> / <strong>Agregar a la pantalla de inicio</strong>.</li>
          <li><strong>iPhone (Safari):</strong> tocá el botón <strong>Compartir</strong> (el cuadrado con la flecha) y después <strong>Agregar a inicio</strong>.</li>
          <li>Queda el ícono de CaszaMosqui como una app más. Abre a pantalla completa y <strong>funciona aunque no haya señal</strong>.</li>
        </ol>
        <p class="ayuda-tip">💡 Manteniendo apretado el ícono aparecen accesos directos: <em>Reportar</em>, <em>Mapa</em>, <em>Salud</em> y <em>Test de síntomas</em>.</p>
      </div>
      <?= captura('inicio', 'La pantalla de inicio, con el botón rojo para reportar y el acceso al mapa.') ?>
    </article>

    <article class="ayuda-tema" id="ay-reportar">
      <h3>📢 Reportar un criadero</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>Entrá a <a href="<?= e(BASE_URL) ?>/reportar.php"><strong>+ Reportar</strong></a> (en el menú) o tocá <strong>Reportar un criadero</strong> en el inicio.</li>
          <li>Elegí el <strong>tipo de criadero</strong> (tacho, cubierta, florero, pileta…) y el <strong>barrio</strong>.</li>
          <li>Escribí una <strong>referencia</strong> (una esquina, "frente a la escuela"), un <strong>título</strong> y una <strong>descripción</strong> de lo que viste.</li>
          <li><strong>Ubicación exacta (opcional):</strong> tocá <strong>📍 Usar mi ubicación</strong> para que el GPS marque el punto, o <strong>🗺️ Marcar en el mapa</strong> y tocá el lugar exacto.</li>
          <li><strong>Foto (opcional):</strong> <strong>📷 Sacar foto</strong> abre la cámara; <strong>🖼️ Elegir de la galería</strong> usa una que ya tengas.</li>
          <li>Tocá <strong>Reportar criadero</strong>. El reporte se suma al mapa de riesgo al instante.</li>
        </ol>
        <p class="ayuda-tip">🔒 La foto y el punto exacto <strong>solo los ve el administrador</strong>. En el mapa público se muestra el barrio, no tu casa.</p>
        <p class="ayuda-tip">⏳ Se puede hacer <strong>un aporte por día</strong> desde cada celular (un reporte o un voto), para evitar abusos.</p>
      </div>
      <?= video('reportar', 'Video: cómo completar y enviar un reporte con tu ubicación.') ?>
      <?= captura('reportar-ubicacion', '"Marcar en el mapa": tocá el lugar exacto del criadero.') ?>
    </article>

    <article class="ayuda-tema" id="ay-sin-senal">
      <h3>📵 ¿Y si no tengo señal?</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>Completá el reporte igual y tocá <strong>Reportar criadero</strong>.</li>
          <li>La página avisa <strong>"Sin señal: guardamos el reporte en tu celular"</strong>. Abajo a la izquierda se ve cuántos reportes esperan.</li>
          <li>Cuando vuelve la conexión, <strong>se envían solos</strong> y aparece "✅ Se envió". No hace falta que hagas nada.</li>
          <li>Si querés forzar el envío, tocá <strong>Enviar ahora</strong> en ese aviso.</li>
        </ol>
        <p class="ayuda-tip">💡 El mapa y los datos que viste por última vez también quedan guardados para verlos sin internet.</p>
      </div>
      <?= captura('sin-senal', 'El reporte queda guardado en el celular y se envía al volver la señal.') ?>
    </article>

    <article class="ayuda-tema" id="ay-mapa">
      <h3>🗺️ Leer el mapa de riesgo</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>En el inicio, cada barrio tiene un color: <strong>🟢 bajo</strong> (0–1 criaderos activos), <strong>🟡 medio</strong> (2–3) y <strong>🔴 alto</strong> (4 o más). Un criadero "activo" es uno reportado que todavía no se controló.</li>
          <li>Tocá un barrio para ver sus números y sus reportes.</li>
          <li>Con los botones <strong>Todos / Alto / Medio / Bajo</strong> filtrás los barrios por nivel.</li>
          <li><strong>📐 Plano municipal</strong> muestra el plano con los nombres de los barrios; <strong>🌎 Mapa real</strong> muestra las calles reales (OpenStreetMap) con los centros de salud marcados.</li>
          <li>La <strong>alerta climática</strong> de arriba suma riesgo cuando llovió mucho y hace calor: son los días en que un criadero se vuelve más peligroso.</li>
        </ol>
      </div>
      <?= video('mapa', 'Video: filtrar por nivel de riesgo y pasar al mapa real.') ?>
      <?= captura('mapa', 'El plano municipal con el semáforo de cada barrio.') ?>
    </article>

    <article class="ayuda-tema" id="ay-salud">
      <h3>🏥 Centros de salud y cómo llegar</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>Entrá a <a href="<?= e(BASE_URL) ?>/centros-salud.php"><strong>Centros de salud</strong></a>. Cada tarjeta muestra dirección, horario y si está <strong>abierto ahora</strong>.</li>
          <li>Tocá el botón verde <strong>📞</strong> para llamar con un toque (y <strong>WhatsApp</strong> donde hay).</li>
          <li><strong>📍 ¿Cuál me queda más cerca?</strong> ordena los centros por distancia usando el GPS del celular.</li>
          <li><strong>🧭 Cómo llegar</strong> dibuja el camino desde donde estás, a pie o en auto, con las indicaciones paso a paso. <strong>Seguir con Google Maps</strong> abre la navegación por voz.</li>
          <li>Ante una emergencia, el botón rojo <strong>🚑 107</strong> llama directamente.</li>
        </ol>
      </div>
      <?= video('como-llegar', 'Video: encontrar el centro más cercano y ver el camino hasta el hospital.') ?>
      <?= captura('como-llegar', 'Las indicaciones paso a paso hasta el Hospital.') ?>
    </article>

    <article class="ayuda-tema" id="ay-sintomas">
      <h3>🩺 Test de síntomas</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>Entrá a <a href="<?= e(BASE_URL) ?>/test-sintomas.php"><strong>Test de Síntomas</strong></a> y marcá lo que sentís (fiebre, dolor de cabeza, manchas en la piel…).</li>
          <li>Tocá <strong>Evaluar síntomas</strong>: te dice si conviene <strong>consultar</strong>, <strong>ir a la guardia</strong> o <strong>llamar al 107</strong>, y dónde atenderte en El Colorado.</li>
        </ol>
        <p class="ayuda-tip">⚠️ Es orientación, no un diagnóstico. Con síntomas de alarma (sangrado, dolor de panza fuerte, vómitos seguidos) andá a la guardia sin esperar.</p>
      </div>
      <?= captura('test-sintomas', 'El test: marcá los síntomas y mirá la recomendación.') ?>
    </article>

    <article class="ayuda-tema" id="ay-prevencion">
      <h3>🛡️ Prevención y cuestionario</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li><a href="<?= e(BASE_URL) ?>/prevencion.php"><strong>Prevención</strong></a> explica las 3 acciones que más sirven (vaciar, dar vuelta, tirar) y el ciclo del mosquito.</li>
          <li><a href="<?= e(BASE_URL) ?>/cuestionario.php"><strong>Cuestionario</strong></a>: 5 preguntas para ver cuánto sabés. Tocá una respuesta, mirá la explicación y pasá a la siguiente. Al final ves tu puntaje y podés compartirlo.</li>
        </ol>
      </div>
      <?= captura('cuestionario', 'El cuestionario de prevención, pregunta por pregunta.') ?>
    </article>

    <article class="ayuda-tema" id="ay-mosqui">
      <h3>💬 Mosqui (asistente) y comentarios</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>El botón <strong>¿Dudas? Preguntale a Mosqui</strong> (abajo a la derecha) abre un chat. Preguntá sobre síntomas, repelentes, cómo eliminar criaderos o cómo usar la página.</li>
          <li>En <a href="<?= e(BASE_URL) ?>/comentarios.php"><strong>Comentarios</strong></a> podés dejar sugerencias o avisos para el equipo y votar los de otros vecinos. No tienen límite diario.</li>
        </ol>
        <p class="ayuda-tip">ℹ️ Mosqui da información general: no reemplaza la consulta médica.</p>
      </div>
      <?= captura('chatbot', 'El asistente Mosqui, con preguntas frecuentes para tocar.') ?>
    </article>

    <article class="ayuda-tema" id="ay-admin">
      <h3>🔐 Panel del administrador (municipio / operativos)</h3>
      <div class="ayuda-cuerpo">
        <ol>
          <li>Tocá <strong>🔐 Administrador</strong> arriba a la derecha e ingresá con tu usuario y contraseña.</li>
          <li>En el panel ves <strong>todos los reportes</strong> con filtros por estado, tipo, barrio y búsqueda. Cada uno se puede marcar como <strong>verificado</strong> o <strong>controlado</strong>, o borrar.</li>
          <li>Las <strong>fotos</strong> y los <strong>pines GPS</strong> exactos se ven solo acá. En <strong>🌎 Mapa real</strong>, la casilla <strong>🔥 Mapa de calor</strong> muestra dónde se concentran los criaderos activos.</li>
          <li><strong>📊 Excel</strong> descarga los reportes (con los filtros elegidos) en tres hojas: reportes, resumen por barrio e información.</li>
          <li><strong>Respaldo</strong>: permite descargar una copia de toda la base y restaurarla.</li>
        </ol>
      </div>
      <?= captura('admin-panel', 'El panel de administración con la lista de reportes.') ?>
      <?= captura('admin-calor', 'El mapa de calor: dónde se concentran los criaderos sin controlar.') ?>
    </article>

    <p class="ayuda-pie">¿Encontraste un error o algo que mejorar? Contanos en <a href="<?= e(BASE_URL) ?>/comentarios.php">Comentarios</a> o escribinos desde <a href="<?= e(BASE_URL) ?>/contacto.php">Contacto</a>.</p>
  </section>

<?php require __DIR__ . '/inc/scripts.php'; ?>
