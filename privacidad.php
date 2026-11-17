<?php
/**
 * CaszaMosqui — Privacidad, uso de datos, límites del sistema y vía de reclamo.
 * Marco: Ley 25.326 de Protección de Datos Personales (Argentina) y
 * Recomendación sobre la Ética de la IA de la UNESCO (2021).
 * Responsables y contacto: inc/equipo.php.
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$page_title  = 'Privacidad y datos';
$active_page = 'privacidad';

$reportes_logueado = reportes_logueado();
$reportes_error     = consumir_error_reportes();
$reportes_csrf      = reportes_csrf_token();
$reportes_usuario   = usuario_reportes();

$datos    = require __DIR__ . '/inc/equipo.php';
$contacto = $datos['contacto'];
$nombres  = implode(', ', array_map(fn ($m) => $m['nombre'], $datos['equipo']));
$tipoInicial = in_array($_GET['reclamo'] ?? '', ['acceso', 'rectificacion', 'supresion', 'error', 'comentario', 'otro'], true) ? $_GET['reclamo'] : '';
$refInicial  = mb_substr(preg_replace('/[^0-9A-Za-z#\- ]/', '', (string) ($_GET['ref'] ?? '')), 0, 60);
const ACTUALIZADO = '16 de noviembre de 2026';

require __DIR__ . '/inc/cabecera.php';
?>

  <section class="panel privacidad" id="privacidad">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <h2>🔒 Privacidad y uso de datos</h2>
    <p class="sub">Qué datos usa CaszaMosqui, para qué, quién los ve, cuánto tiempo se guardan y cómo pedir que los corrijan o borren.
      Seguimos la <strong>Ley 25.326 de Protección de Datos Personales</strong> y la <strong>Recomendación sobre la Ética de la IA de la UNESCO (2021)</strong>.
      Última actualización: <?= e(ACTUALIZADO) ?>.</p>

    <nav class="ayuda-indice" aria-label="Secciones">
      <a href="#pv-responsable">👥 Responsables</a>
      <a href="#pv-datos">📋 Qué datos y para qué</a>
      <a href="#pv-tiempo">⏳ Cuánto tiempo</a>
      <a href="#pv-ia">🤖 Inteligencia artificial</a>
      <a href="#pv-derechos">⚖️ Tus derechos</a>
      <a href="#limites">🚧 Límites del sistema</a>
      <a href="#reclamo">✍️ Pedidos y reclamos</a>
    </nav>

    <article class="pv-bloque" id="pv-responsable">
      <h3>👥 Quién es responsable</h3>
      <p>CaszaMosqui es un desarrollo del equipo <strong><?= e($nombres) ?></strong> (FormosaHack 2026, El Colorado, Formosa).
        Somos responsables de la base de datos y de responder cualquier pedido o reclamo sobre ella.</p>
      <p>Contacto: <?php if (!empty($contacto['email'])): ?><a href="mailto:<?= e($contacto['email']) ?>"><?= e($contacto['email']) ?></a><?php endif; ?>
        <?php if (!empty($contacto['whatsapp'])): ?> · WhatsApp <?= e($contacto['whatsapp_txt'] ?: $contacto['whatsapp']) ?><?php endif; ?>
        · o el <a href="#reclamo">formulario de esta página</a>.</p>
      <p class="pv-nota">Si un municipio u organismo adopta el sistema, pasa a ser también responsable de la base: debe designar a una persona que atienda los reclamos e
        inscribir la base ante la Agencia de Acceso a la Información Pública cuando corresponda.</p>
    </article>

    <article class="pv-bloque" id="pv-datos">
      <h3>📋 Qué datos usamos y para qué</h3>
      <div class="pv-tabla-wrap">
        <table class="pv-tabla">
          <thead><tr><th>Dónde</th><th>Qué datos</th><th>Para qué</th><th>Quién los ve</th></tr></thead>
          <tbody>
            <tr><td><strong>Reportar un criadero</strong></td>
              <td>Tipo de criadero, barrio, referencia, título y descripción. Opcionales: <strong>ubicación exacta</strong> (GPS) y <strong>foto</strong>
                (al subirla se le borran los datos ocultos del teléfono). Un número al azar del envío, para no duplicarlo si no hay señal.</td>
              <td>Armar el mapa de riesgo por barrio y planificar los operativos de descacharrado.</td>
              <td>Público: <strong>solo la cantidad por barrio</strong>. El texto, la foto y el GPS: <strong>solo el equipo</strong> en el panel.</td></tr>
            <tr><td><strong>Comentarios</strong></td>
              <td>Barrio, tipo y texto.</td>
              <td>Recibir sugerencias y avisos de la comunidad.</td>
              <td><strong>Son públicos.</strong> Por eso no se aceptan teléfonos, correos ni documentos, y el equipo puede borrar los que expongan a alguien.</td></tr>
            <tr><td><strong>Chat con Mosqui</strong></td>
              <td>Tu pregunta. <strong>No guardamos las conversaciones.</strong> Para limitar el uso se guarda por una hora un código anónimo derivado de la conexión.</td>
              <td>Responder dudas sobre dengue, zika, chikungunya y la página.</td>
              <td>Nadie del equipo. Si la pregunta no está en nuestra base, se envía a un servicio de IA externo (ver abajo).</td></tr>
            <tr><td><strong>Test de síntomas</strong></td>
              <td>Lo que marcás.</td>
              <td>Orientarte sobre si consultar o ir a la guardia.</td>
              <td><strong>Nadie: no sale de tu celular.</strong></td></tr>
            <tr><td><strong>Pedidos y reclamos</strong></td>
              <td>Tipo de pedido, n.º de reporte o comentario, detalle y, si querés, un contacto.</td>
              <td>Atender tu pedido y dejar constancia de la respuesta.</td>
              <td>Solo el equipo.</td></tr>
            <tr><td><strong>En tu celular</strong></td>
              <td>Reportes guardados sin señal, la fecha de tu último aporte (límite de uno por día) y la app instalada.</td>
              <td>Que la página funcione sin internet y evitar abusos.</td>
              <td>Nadie: queda en tu dispositivo. Se borra al borrar los datos del navegador.</td></tr>
          </tbody>
        </table>
      </div>
      <p>No vendemos ni cedemos datos, no los usamos para publicidad y no armamos perfiles de personas. No pedimos nombre, DNI ni teléfono para reportar.</p>
    </article>

    <article class="pv-bloque" id="pv-tiempo">
      <h3>⏳ Cuánto tiempo se guardan</h3>
      <ul>
        <li><strong>Foto y ubicación exacta:</strong> se borran solas <strong>90 días después</strong> de que el criadero se marca como controlado. Queda solo el barrio, que alcanza para las estadísticas.</li>
        <li><strong>Reportes y comentarios:</strong> mientras sirvan para el mapa y el seguimiento. Podés pedir que se borren antes (ver <a href="#reclamo">pedidos</a>).</li>
        <li><strong>Registro de cambios:</strong> el sistema guarda quién del equipo cambió o borró algo y cuándo, para poder rendir cuentas. No guarda el texto de lo borrado.</li>
      </ul>
    </article>

    <article class="pv-bloque" id="pv-ia">
      <h3>🤖 Inteligencia artificial</h3>
      <ul>
        <li><strong>Mosqui</strong> primero busca la respuesta en una base de información verificada por el equipo. Si no la encuentra, consulta a un modelo de IA
          (<strong>Claude, de Anthropic</strong>, con servidores fuera de Argentina) limitado a temas de dengue, zika y chikungunya.
          Esas respuestas se marcan con <em>"✨ Respuesta generada con IA"</em> y pueden tener errores: <strong>no reemplazan la consulta médica</strong>.
          No escribas datos personales en el chat.</li>
        <li><strong>El semáforo de riesgo no es una IA:</strong> son reglas simples y públicas. Criaderos activos en el barrio (sin controlar o verificados):
          0–1 bajo, 2–3 medio, 4 o más alto. Si el clima real es favorable para el mosquito, se suma un punto o dos; <strong>si no hay datos reales del clima, no se suma nada</strong>.</li>
        <li><strong>Las casas del modo inmersivo son ilustrativas:</strong> muestran cómo se llena cada manzana; no son las viviendas reales.</li>
        <li>Ninguna IA decide sola: la responsabilidad es siempre de las personas del equipo y de quien use el sistema.</li>
      </ul>
    </article>

    <article class="pv-bloque" id="pv-derechos">
      <h3>⚖️ Tus derechos (Ley 25.326)</h3>
      <ul>
        <li><strong>Acceso (art. 14):</strong> saber qué datos tuyos hay. Es gratuito.</li>
        <li><strong>Rectificación, actualización y supresión (art. 16):</strong> pedir que se corrijan o se borren.</li>
        <li><strong>Decisiones automatizadas (art. 20):</strong> CaszaMosqui no toma decisiones sobre personas. El semáforo solo orienta dónde trabajar,
          y cada reporte lo revisa una persona del equipo antes de marcarlo como verificado o controlado.</li>
        <li>Si no te respondemos o la respuesta no te conforma, podés reclamar ante la
          <a href="https://www.argentina.gob.ar/aaip" target="_blank" rel="noopener">Agencia de Acceso a la Información Pública</a>, el órgano de control de la ley.</li>
      </ul>
      <p class="pv-nota">Como los reportes son anónimos, para pedir algo sobre uno conviene tener su <strong>número de reporte</strong> (aparece al enviarlo)
        o describir bien el barrio, la fecha y lo que escribiste.</p>
    </article>

    <article class="pv-bloque" id="limites">
      <h3>🚧 Límites del sistema (lo que CaszaMosqui NO hace)</h3>
      <ul>
        <li><strong>No detecta criaderos por sí solo:</strong> depende de que los vecinos reporten. <strong>Pocos reportes no significa que no haya criaderos</strong>:
          los barrios con menos celulares o señal pueden aparecer con menos riesgo del real.</li>
        <li>El mapa <strong>incluye reportes todavía sin verificar</strong>. Un reporte falso o duplicado puede subir el nivel de un barrio hasta que el equipo lo revise.</li>
        <li>El nivel de un barrio <strong>no habla de las personas que viven ahí</strong>: indica dónde conviene revisar recipientes con agua.</li>
        <li>No diagnostica enfermedades: el test de síntomas y Mosqui solo orientan.</li>
        <li>Los datos del clima vienen de un servicio externo (Open-Meteo) y pueden faltar o atrasarse.</li>
      </ul>
    </article>

    <article class="pv-bloque pv-reclamo" id="reclamo">
      <h3>✍️ Pedidos y reclamos</h3>
      <p>Usá este formulario para pedir acceso, corrección o borrado de un dato, avisar un error del mapa o reclamar por un comentario.
        Te damos un <strong>número de pedido</strong>, y el equipo lo responde y deja constancia.</p>
      <form data-js-reclamo novalidate>
        <div class="grid">
          <label>¿Qué querés pedir? <span class="req">*</span>
            <select name="tipo" required>
              <option value="">— Elegir —</option>
              <option value="acceso"<?= $tipoInicial === 'acceso' ? ' selected' : '' ?>>Saber qué datos hay (acceso)</option>
              <option value="rectificacion"<?= $tipoInicial === 'rectificacion' ? ' selected' : '' ?>>Corregir un dato (rectificación)</option>
              <option value="supresion"<?= $tipoInicial === 'supresion' ? ' selected' : '' ?>>Borrar un reporte o una foto (supresión)</option>
              <option value="comentario"<?= $tipoInicial === 'comentario' ? ' selected' : '' ?>>Reclamar por un comentario</option>
              <option value="error"<?= $tipoInicial === 'error' ? ' selected' : '' ?>>Avisar un error del mapa o de la página</option>
              <option value="otro"<?= $tipoInicial === 'otro' ? ' selected' : '' ?>>Otro</option>
            </select>
          </label>
          <label>N.º de reporte o comentario <span class="opcional">(si lo tenés)</span>
            <input type="text" name="referencia" maxlength="60" placeholder="Ej.: #123" value="<?= e($refInicial) ?>">
          </label>
        </div>
        <label>Contanos tu pedido <span class="req">*</span>
          <textarea name="detalle" rows="4" maxlength="2000" required placeholder="Qué dato es, en qué barrio, qué querés que hagamos…"></textarea>
        </label>
        <label>¿Cómo te respondemos? <span class="opcional">(opcional: correo o teléfono)</span>
          <input type="text" name="contacto" maxlength="120" placeholder="Solo lo usamos para responder este pedido">
        </label>
        <label class="consentimiento">
          <input type="checkbox" name="acepta" required>
          <span>Acepto que el equipo use estos datos solo para atender mi pedido, según este aviso de privacidad.</span>
        </label>
        <button type="submit">Enviar pedido</button>
        <p class="form-msg" data-js-reclamo-msg aria-live="polite"></p>
      </form>
    </article>
  </section>

  <script>
    (function () {
      const form = document.querySelector('[data-js-reclamo]');
      const msg = document.querySelector('[data-js-reclamo-msg]');
      form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        msg.className = 'form-msg';
        const d = Object.fromEntries(new FormData(form).entries());
        if (!d.tipo || !d.detalle.trim()) { msg.textContent = 'Elegí qué querés pedir y contanos el detalle.'; msg.classList.add('err'); return; }
        if (d.acepta !== 'on') { msg.textContent = 'Para enviar el pedido tenés que aceptar el aviso.'; msg.classList.add('err'); return; }
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        try {
          const r = await fetch((window.BASE_URL || '.') + '/api/index.php?route=reclamos', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
            body: JSON.stringify({ tipo: d.tipo, referencia: d.referencia.trim(), detalle: d.detalle.trim(), contacto: d.contacto.trim(), acepta: true }),
          });
          const j = await r.json().catch(() => ({}));
          if (!r.ok || !j.ok) throw new Error(j.error || 'No se pudo enviar. Probá de nuevo.');
          form.reset();
          msg.innerHTML = `✅ Recibimos tu pedido. Tu número de pedido es <strong>#${Number(j.data.id)}</strong>. Guardalo para consultar por él.`;
          msg.classList.add('ok');
        } catch (e) {
          msg.textContent = '❌ ' + e.message;
          msg.classList.add('err');
        } finally { btn.disabled = false; }
      });
    })();
  </script>

<?php require __DIR__ . '/inc/scripts.php'; ?>
