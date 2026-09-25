/* ============================================================
   Comentarios — CaszaMosqui
   1 interacción por día por dispositivo (localStorage)
   ============================================================ */

alert('comentarios.js EXECUTING');

console.log('comentarios.js PARSED - file loaded');

const API = () => window.BASE_URL + '/api/';

const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const ESCAPAR = (txt = '') =>
  String(txt).replace(/[&<>"']/g, (c) => ({
    '&': '&', '<': '<', '>': '>', '"': '"', "'": ''',
  }[c]);

async function api(route, options = {}) {
  const res = await fetch(API() + '?route=' + route, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json.ok === false) throw new Error(json.error || 'Error en la API');
  return json.data;
}

/* ---------- Límite diario por dispositivo (localStorage) ---------- */
const LIMITE_KEY = 'casza_limite_diario';

function obtenerLimite() {
  try {
    const data = JSON.parse(localStorage.getItem(LIMITE_KEY) || '{}');
    const hoy = new Date().toISOString().split('T')[0];
    return data[hoy] || 0;
  } catch { return 0; }
}

function incrementarLimite() {
  try {
    const data = JSON.parse(localStorage.getItem(LIMITE_KEY) || '{}');
    const hoy = new Date().toISOString().split('T')[0];
    data[hoy] = (data[hoy] || 0) + 1;
    localStorage.setItem(LIMITE_KEY, JSON.stringify(data));
  } catch {}
}

function puedeInteractuar() {
  return obtenerLimite() === 0;
}

function actualizarAviso() {
  const aviso = $('#aviso-diario');
  const btn = $('#btn-enviar');
  const ta = $('#textarea-comentario');
  if (!puedeInteractuar()) {
    aviso.textContent = '⚠️ Ya realizaste tu interacción hoy. Volvé mañana.';
    aviso.classList.add('bloqueado');
    if (btn) btn.disabled = true;
    if (ta) ta.disabled = true;
  } else {
    aviso.textContent = '✅ Podés enviar un comentario hoy (límite: 1 por día por dispositivo).';
    aviso.classList.remove('bloqueado');
    if (btn) btn.disabled = false;
    if (ta) ta.disabled = false;
  }
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

/* ---------- Cargar comentarios ---------- */
async function cargarComentarios() {
  try {
    const data = await api('comentarios');
    renderComentarios(data);
  } catch (err) {
    $('#lista-comentarios').innerHTML = `<p class="placeholder">⚠️ ${ESCAPAR(err.message)}</p>`;
  }
}

function renderComentarios(comentarios) {
  const cont = $('#lista-comentarios');
  if (!comentarios.length) {
    cont.innerHTML = '<p class="empty-state">No hay comentarios aún. ¡Sé el primero en opinar!</p>';
    return;
  }
  cont.innerHTML = comentarios.map((c) => `
    <article class="comentario-card">
      <div class="comentario-header">
        <span class="comentario-barrio">${ESCAPAR(c.barrio_nombre)}</span>
        <span class="comentario-fecha">${formatearFecha(c.creado_en)}</span>
      </div>
      <p class="comentario-texto">${ESCAPAR(c.texto)}</p>
    </article>
  `).join('');
}

function formatearFecha(dt) {
  const d = new Date(dt.replace(' ', 'T'));
  return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

/* ---------- Enviar comentario ---------- */
async function enviarComentario(ev) {
  ev.preventDefault();
  if (!puedeInteractuar()) {
    alert('Ya realizaste tu interacción diaria. Volvé mañana.');
    return;
  }
  const form = ev.currentTarget;
  const data = Object.fromEntries(new FormData(form).entries());
  const btn = $('#btn-enviar');
  const original = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Enviando…';
  try {
    await api('comentarios', {
      method: 'POST',
      body: JSON.stringify({
        barrio_id: Number(data.barrio_id),
        tipo: data.tipo,
        texto: data.texto.trim(),
      }),
    });
    incrementarLimite();
    form.reset();
    actualizarAviso();
    await cargarComentarios();
    alert('¡Comentario enviado! Gracias por participar.');
  } catch (err) {
    alert('Error: ' + err.message);
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
  actualizarAviso();
  await Promise.all([cargarBarriosSelect(), cargarComentarios()]);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}