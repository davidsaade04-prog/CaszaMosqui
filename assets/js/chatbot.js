/* ============================================================
   CaszaMosqui — Chatbot "Mosqui" (ventana flotante)
   Se incluye en cualquier página con:
     <link rel="stylesheet" href=".../assets/css/chatbot.css">
     <script>window.BASE_URL = '...';</script>
     <script src=".../assets/js/chatbot.js"></script>
   Habla con api/chat.php (base propia + IA opcional).
   ============================================================ */
(function () {
  const BASE = (window.CHAT_BASE_URL ?? window.BASE_URL ?? '.');
  const SUGERENCIAS_INICIALES = [
    '¿Cuáles son los síntomas del dengue?',
    '¿Qué hago si tengo fiebre?',
    '¿Cómo elimino criaderos en mi casa?',
    '¿Qué repelente conviene usar?',
  ];
  const historial = [];   // [{rol:'usuario'|'bot', texto}]
  let enviando = false;

  const esc = (t) => String(t).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // Formato simple y seguro: primero se escapa todo, después **negrita**, *cursiva* y listas
  function formatear(texto) {
    const lineas = esc(texto).split('\n');
    let html = '', enLista = null;
    const cerrar = () => { if (enLista) { html += `</${enLista}>`; enLista = null; } };
    for (let l of lineas) {
      l = l.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');
      const ul = l.match(/^\s*[-•]\s+(.*)$/), ol = l.match(/^\s*\d+[.)]\s+(.*)$/);
      if (ul || ol) {
        const tipo = ul ? 'ul' : 'ol';
        if (enLista !== tipo) { cerrar(); html += `<${tipo}>`; enLista = tipo; }
        html += `<li>${(ul || ol)[1]}</li>`;
      } else {
        cerrar();
        if (l.trim()) html += `<p>${l}</p>`;
      }
    }
    cerrar();
    return html;
  }

  /* ---------- Estructura ---------- */
  const raiz = document.createElement('div');
  raiz.className = 'mosqui';
  raiz.innerHTML = `
    <button type="button" class="mosqui-lanzador" aria-expanded="false" aria-controls="mosqui-panel">
      <span class="mosqui-lanzador-ico" aria-hidden="true">🦟</span>
      <span class="mosqui-lanzador-txt">¿Dudas? Preguntale a Mosqui</span>
    </button>
    <section class="mosqui-panel" id="mosqui-panel" role="dialog" aria-label="Chat con Mosqui" hidden>
      <header class="mosqui-cabecera">
        <span class="mosqui-avatar" aria-hidden="true">🦟</span>
        <div>
          <strong>Mosqui</strong>
          <small>Asistente de dengue, zika y chikungunya</small>
        </div>
        <button type="button" class="mosqui-cerrar" aria-label="Cerrar chat">✕</button>
      </header>
      <div class="mosqui-mensajes" aria-live="polite"></div>
      <div class="mosqui-sugerencias"></div>
      <form class="mosqui-form">
        <input type="text" name="mensaje" maxlength="500" autocomplete="off"
               placeholder="Escribí tu pregunta…" aria-label="Tu pregunta">
        <button type="submit" aria-label="Enviar">➤</button>
      </form>
      <p class="mosqui-aviso">Información general: no reemplaza la consulta médica. Emergencias: 107.</p>
    </section>`;
  document.body.appendChild(raiz);

  const lanzador = raiz.querySelector('.mosqui-lanzador');
  const panel = raiz.querySelector('.mosqui-panel');
  const lista = raiz.querySelector('.mosqui-mensajes');
  const sugerencias = raiz.querySelector('.mosqui-sugerencias');
  const form = raiz.querySelector('.mosqui-form');
  const input = form.querySelector('input');

  function agregar(rol, texto, fuente) {
    const div = document.createElement('div');
    div.className = `mosqui-msg mosqui-${rol}`;
    div.innerHTML = rol === 'bot' ? formatear(texto) : `<p>${esc(texto)}</p>`;
    if (rol === 'bot' && fuente === 'ia') {
      div.insertAdjacentHTML('beforeend', '<span class="mosqui-fuente">✨ Respuesta generada con IA</span>');
    }
    lista.appendChild(div);
    lista.scrollTop = lista.scrollHeight;
    historial.push({ rol, texto });
  }

  function mostrarSugerencias(items) {
    sugerencias.innerHTML = (items || []).map((s) => `<button type="button">${esc(s)}</button>`).join('');
    lista.scrollTop = lista.scrollHeight;   // que la última respuesta no quede tapada
  }

  function escribiendo(on) {
    let el = lista.querySelector('.mosqui-escribiendo');
    if (on && !el) {
      el = document.createElement('div');
      el.className = 'mosqui-msg mosqui-bot mosqui-escribiendo';
      el.innerHTML = '<span></span><span></span><span></span>';
      lista.appendChild(el);
      lista.scrollTop = lista.scrollHeight;
    } else if (!on && el) {
      el.remove();
    }
  }

  async function preguntar(texto) {
    texto = texto.trim();
    if (!texto || enviando) return;
    enviando = true;
    form.querySelector('button').disabled = true;
    mostrarSugerencias([]);
    const previo = historial.slice(-6);
    agregar('usuario', texto);
    input.value = '';
    escribiendo(true);
    try {
      const r = await fetch(BASE + '/api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mensaje: texto, historial: previo }),
      });
      const d = await r.json();
      escribiendo(false);
      if (!d.ok) throw new Error(d.error || 'Error');
      agregar('bot', d.respuesta, d.fuente);
      mostrarSugerencias(d.sugerencias || []);
    } catch (e) {
      escribiendo(false);
      agregar('bot', 'Uy, no pude responder ahora 😕. Probá de nuevo en un momento.');
    } finally {
      enviando = false;
      form.querySelector('button').disabled = false;
      input.focus();
    }
  }

  function abrir(on) {
    panel.hidden = !on;
    raiz.classList.toggle('abierto', on);
    lanzador.setAttribute('aria-expanded', String(on));
    if (on) {
      if (!lista.children.length) {
        agregar('bot', '¡Hola! Soy **Mosqui** 🦟. Preguntame lo que quieras sobre **dengue, zika y chikungunya**: síntomas, prevención, criaderos, repelentes o cómo usar esta página.');
        mostrarSugerencias(SUGERENCIAS_INICIALES);
      }
      setTimeout(() => input.focus(), 50);
    }
  }

  lanzador.addEventListener('click', () => abrir(panel.hidden));
  raiz.querySelector('.mosqui-cerrar').addEventListener('click', () => abrir(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) abrir(false); });
  form.addEventListener('submit', (e) => { e.preventDefault(); preguntar(input.value); });
  sugerencias.addEventListener('click', (e) => {
    const b = e.target.closest('button');
    if (b) preguntar(b.textContent);
  });

  // Permite abrir el chat desde cualquier botón/enlace con data-abrir-chat
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-abrir-chat]')) { e.preventDefault(); abrir(true); }
  });
  window.abrirMosqui = () => abrir(true);
})();
