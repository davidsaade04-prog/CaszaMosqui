/* ============================================================
   Comentarios — CaszaMosqui
   Sin límite: se puede comentar todas las veces que se quiera y se muestran todos
   ============================================================ */

const API = () => window.BASE_URL + '/api/';

const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const ESCAPAR = (txt = '') =>
  String(txt).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));

async function api(route, options = {}) {
  const res = await fetch(API() + '?route=' + route, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json.ok === false) throw new Error(json.error || 'Error en la API');
  return json.data;
}

/* ---------- Sin límite de comentarios ----------
   Cada vecino puede comentar todas las veces que quiera. Los comentarios NO usan
   el límite diario de la app (ese queda solo para reportes y votos). */
function mostrarAviso(texto, tipo = '') {
  const aviso = $('#aviso-diario');
  if (!aviso) return;
  aviso.textContent = texto;
  aviso.className = 'aviso' + (tipo ? ' ' + tipo : '');
}

/* ---------- Cargar barrios en el select ---------- */
async function cargarBarriosSelect() {
  try {
    const barrios = await api('barrios');
    const select = $('#select-barrio');
    if (!select) return;
    // Solo actualizar si el select tiene solo la opción por defecto (fallback server-side)
    if (select.options.length <= 1) {
      select.innerHTML = '<option value="">— Elegí tu barrio —</option>' +
        barrios.map((b) => `<option value="${b.id}">${ESCAPAR(b.nombre)}</option>`).join('');
    }
  } catch (err) {
    console.error('Error cargando barrios:', err);
  }
}

/* ---------- Cargar comentarios (todos) ---------- */
const TIPOS = {
  sugerencia:   '💡 Sugerencia',
  problema:     '⚠️ Problema',
  felicitacion: '👏 Felicitación',
  otro:         '📝 Otro',
};

async function cargarComentarios(idNuevo = null) {
  try {
    const data = await api('comentarios&limit=1000');
    renderComentarios(data, idNuevo);
  } catch (err) {
    $('#lista-comentarios').innerHTML = `<p class="placeholder">⚠️ ${ESCAPAR(err.message)}</p>`;
  }
}

function renderComentarios(comentarios, idNuevo = null) {
  const cont = $('#lista-comentarios');
  const contador = $('#contador-comentarios');
  if (contador) contador.textContent = comentarios.length ? `(${comentarios.length})` : '';
  if (!comentarios.length) {
    cont.innerHTML = '<p class="empty-state">No hay comentarios aún. ¡Sé el primero en opinar!</p>';
    return;
  }
  cont.innerHTML = comentarios.map((c) => `
    <article class="comentario-card tipo-${ESCAPAR(c.tipo || 'otro')}${c.id == idNuevo ? ' comentario-nuevo' : ''}" data-id="${c.id}">
      <div class="comentario-header">
        <span class="comentario-barrio">📍 ${ESCAPAR(c.barrio_nombre)}</span>
        <span class="comentario-tipo">${TIPOS[c.tipo] || TIPOS.otro}</span>
        <span class="comentario-fecha">${formatearFecha(c.creado_en)}</span>
      </div>
      <p class="comentario-texto">${ESCAPAR(c.texto)}</p>
    </article>
  `).join('');
  if (idNuevo) {
    cont.querySelector('.comentario-nuevo')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function formatearFecha(dt) {
  const d = new Date(String(dt).replace(' ', 'T'));
  if (isNaN(d)) return '';
  return d.toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

/* ---------- Enviar comentario ---------- */
async function enviarComentario(ev) {
  ev.preventDefault();
  const form = ev.currentTarget;
  const data = Object.fromEntries(new FormData(form).entries());
  const btn = $('#btn-enviar');
  const original = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Enviando…';
  mostrarAviso('');
  try {
    const nuevo = await api('comentarios', {
      method: 'POST',
      body: JSON.stringify({
        barrio_id: Number(data.barrio_id),
        tipo: data.tipo,
        texto: data.texto.trim(),
      }),
    });
    // Se limpia solo el texto: barrio y tipo quedan elegidos para comentar de nuevo rápido
    form.texto.value = '';
    mostrarAviso('✅ ¡Gracias! Tu comentario ya aparece en la lista.', 'ok');
    await cargarComentarios(nuevo && nuevo.id);
  } catch (err) {
    mostrarAviso('❌ ' + err.message, 'bloqueado');
  } finally {
    btn.disabled = false;
    btn.textContent = original;
  }
}

/* ---------- Init ---------- */
function bind() {
  $('#form-comentario')?.addEventListener('submit', enviarComentario);
}

async function init() {
  bind();
  await Promise.all([cargarBarriosSelect(), cargarComentarios()]);
}

// Funciona aunque el script se cargue después de que el DOM ya esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}