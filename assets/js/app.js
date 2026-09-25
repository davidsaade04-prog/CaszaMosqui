/* ============================================================
   CaszaMosqui — app.js
   Consume la API REST (/api/?route=...) y renderiza el dashboard:
   mapa de riesgo, KPIs, reportes, formulario y quiz.
   ============================================================ */

const API = () => window.BASE_URL + '/api/';

function tieneAccesoReportes() {
  return window.REPORTES_ACCESS === true;
}

/* ---------- Utilidades ---------- */
const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const ESCAPAR = (txt = '') =>
  String(txt).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));

async function apiRespuesta(route, options = {}) {
  console.log('API call:', route);
  const res = await fetch(API() + '?route=' + route, {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  console.log('API response:', res.status, route);
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json.ok === false) throw new Error(json.error || 'Error en la API');
  return json;
}

async function api(route, options = {}) {
  const respuesta = await apiRespuesta(route, options);
  return respuesta.data;
}

function fechaRelativa(dt) {
  const ms = new Date(dt.replace(' ', 'T')) - new Date();
  const s = Math.round(Math.abs(ms) / 1000);
  if (s < 60) return 'hace un momento';
  const m = Math.round(s / 60);
  if (m < 60) return `hace ${m} min`;
  const h = Math.round(m / 60);
  if (h < 24) return `hace ${h} h`;
  return `hace ${Math.round(h / 24)} d`;
}

const ESTADOS = {
  pendiente: 'Sin controlar',
  verificado: 'Verificado',
  controlado: 'Controlado',
};

const NIVELES = {
  alto:  { color: '#dc2626', label: 'Riesgo alto' },
  medio: { color: '#d97706', label: 'Riesgo medio' },
  bajo:  { color: '#16a34a', label: 'Riesgo bajo' },
};

const RIESGO_ROJO_MINIMO = 5;
const RIESGO_AMARILLO_MINIMO = 3;

function nivelRiesgo(activos) {
  const cantidad = Math.max(0, Number(activos) || 0);
  if (cantidad >= RIESGO_ROJO_MINIMO) return 'alto';
  if (cantidad >= RIESGO_AMARILLO_MINIMO) return 'medio';
  return 'bajo';
}

/* ---------- Estado global ---------- */
const state = {
  tipos: [],
  barrios: [],
  filtros: { tipo: '', barrio: '', estado: '', q: '' },
  paginaReportes: 1,
  reportesRequestId: 0,
  quizIndex: 0,
  quizScore: 0,
  quizDone: false,
  mapaFiltroNivel: 'todos',
  // Zoom/pan state
  mapaZoom: 1,
  mapaPanX: 0,
  mapaPanY: 0,
  mapaEnfocadoBarrio: null,
};

const REPORTES_POR_PAGINA = 4;

/* ---------- Límite diario (1 interacción por dispositivo) ---------- */
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

function actualizarEstadoLimite() {
  const bloqueado = !puedeInteractuar();
  $$('[data-js-form] button[type=submit]').forEach((b) => { b.disabled = bloqueado; });
  $$('[data-voto]').forEach((b) => { b.disabled = bloqueado; });
}

/* ---------- Quiz (concientización) ---------- */
const QUIZ = [
  {
    q: '¿Dónde se reproduce el mosquito del dengue (Aedes aegypti)?',
    opciones: ['En agua limpia y estancada', 'En agua sucia de cloaca', 'En pastizales secos', 'En la tierra de las macetas'],
    correcta: 0,
    expl: 'El Aedes aegypti se cría en recipientes con agua limpia y estancada cerca de las casas.',
  },
  {
    q: '¿Cada cuánto conviene revisar patios y «descacharrar»?',
    opciones: ['Una vez al año', 'Semanalmente', 'Cada 6 meses', 'Solo cuando hay casos'],
    correcta: 1,
    expl: 'Cada semana: vaciar y limpiar recipientes evita que las larvas completen su ciclo.',
  },
  {
    q: '¿Qué hacemos con los neumáticos viejos que juntan agua?',
    opciones: ['Tirarlos al patio', 'Llenarlos de agua a propósito', 'Guardarlos bajo techo o perforarlos', 'Enterrarlos en el jardín'],
    correcta: 2,
    expl: 'Bajo techo o perforados no acumulan agua: se elimina el criadero.',
  },
  {
    q: '¿Cuáles son síntomas típicos del dengue?',
    opciones: ['Tos y congestión nasal', 'Fiebre alta, dolor de cabeza y articulaciones', 'Solo cansancio', 'Pérdida de apetito únicamente'],
    correcta: 1,
    expl: 'Fiebre alta, dolor detrás de los ojos, muscular y articular, sarpullido: ¡a consultar!',
  },
  {
    q: 'Frente a fiebre y dolor, ¿qué se recomienda?',
    opciones: ['Tomar aspirina y esperar varios días', 'Automedicarse con antibióticos', 'No hacer nada', 'Consultar al centro de salud y NO automedicarse'],
    correcta: 3,
    expl: 'Nunca te automediques (la aspirina puede complicar el dengue). Consultá siempre a un profesional.',
  },
];

function renderQuiz() {
  console.log('renderQuiz() called');
  const cont = $('[data-js-quiz]');
  console.log('Quiz container found:', !!cont);
  if (!cont) {
    console.error('Quiz container NOT found!');
    return;
  }

  if (state.quizDone) {
    const pct = Math.round((state.quizScore / QUIZ.length) * 100);
    const msg =
      state.quizScore >= 4
        ? '🏆 ¡Sos un/a experto/a en prevención! Compartí CaszaMosqui con tu barrio.'
        : state.quizScore === 3
          ? '💪 ¡Muy bien! Seguí aprendiendo y mirá la guía de prevención.'
          : '📖 Repasá la guía de prevención: cada reporte y cada casa limpia cuentan.';
    cont.innerHTML = `
      <div class="quiz-card">
        <h3>Resultado: ${state.quizScore}/${QUIZ.length} (${pct}%)</h3>
        <p>${msg}</p>
        <button data-js-quiz-reiniciar>Volver a intentar</button>
      </div>`;
    return;
  }

  const q = QUIZ[state.quizIndex];
  cont.innerHTML = `
    <div class="quiz-card">
      <div class="quiz-bar"><i style="width:${(state.quizIndex / QUIZ.length) * 100}%"></i></div>
      <h3>Pregunta ${state.quizIndex + 1} de ${QUIZ.length}: ${ESCAPAR(q.q)}</h3>
      <div class="quiz-opciones">
        ${q.opciones.map((op, i) =>
          `<button class="quiz-opcion" data-js-respuesta data-i="${i}">${ESCAPAR(op)}</button>`).join('')}
      </div>
      <p class="quiz-foot">🏆 ${state.quizScore} aciertos hasta ahora</p>
    </div>`;
}

function responderQuiz(sel) {
  const q = QUIZ[state.quizIndex];
  const correcta = q.correcta;
  const ok = sel.dataset.i == correcta;

  $$('[data-js-respuesta]', $('[data-js-quiz]')).forEach((b) => {
    b.disabled = true;
    if (b.dataset.i == correcta) b.classList.add('ok');
    else if (b === sel && !ok) b.classList.add('fail');
  });

  if (ok) state.quizScore++;
  const cont = $('[data-js-quiz]');
  const foot = $('.quiz-foot', cont);
  const expl = document.createElement('p');
  expl.innerHTML = `<span class="${ok ? 'ok' : 'err'} form-msg">${ok ? '✅ ¡Correcto!' : '❌ Incorrecto.'}</span>
                    💡 ${ESCAPAR(q.expl)}`;
  expl.style.marginTop = '10px';
  const nextBtn = document.createElement('button');
  nextBtn.textContent = state.quizIndex + 1 >= QUIZ.length ? 'Ver resultado' : 'Siguiente pregunta →';
  nextBtn.style.marginTop = '12px';
  nextBtn.addEventListener('click', () => {
    state.quizIndex++;
    if (state.quizIndex >= QUIZ.length) state.quizDone = true;
    renderQuiz();
  });
  expl.appendChild(nextBtn);
  foot.after(expl);
}

/* ---------- Cargar opciones ---------- */
async function cargarCatalogos() {
  const [tipos, barrios] = await Promise.all([api('tipos'), api('barrios')]);
  state.tipos = tipos;
  state.barrios = barrios;

  const filtroTipo = $('[data-js-filtros] select[name=tipo]');
  const filtroBarrio = $('[data-js-filtros] select[name=barrio]');
  const formTipo = $('[data-js-form] select[name=tipo_id]');
  const formBarrio = $('[data-js-form] select[name=barrio_id]');

  const optsTipo = tipos.map((t) => `<option value="${t.id}">${ESCAPAR(t.icono)} ${ESCAPAR(t.nombre)}</option>`).join('');
  const optsBarrio = barrios.map((b) => `<option value="${b.id}">${ESCAPAR(b.nombre)}</option>`).join('');

  if (filtroTipo) filtroTipo.innerHTML = '<option value="">Todos los tipos</option>' + optsTipo;
  if (filtroBarrio) filtroBarrio.innerHTML = '<option value="">Todos los barrios</option>' + optsBarrio;
  if (formTipo) formTipo.innerHTML = '<option value="">— Elegir tipo —</option>' + optsTipo;
  if (formBarrio) formBarrio.innerHTML = '<option value="">— Elegir barrio —</option>' + optsBarrio;

  // Sidebar de barrios en el mapa (se actualizará con datos de riesgo en cargarStats)
  renderBarriosSidebar(barrios, state.ultimaRiesgo || []);
}

/* ---------- Sidebar de barrios ---------- */
function renderBarriosSidebar(barrios, riesgo) {
  const ul = $('[data-js-barra-barrios]');
  if (!ul) return;

  // Crear mapa de índice por barrio_id desde los datos de riesgo (stats)
  const indicePorBarrio = {};
  if (riesgo) {
    riesgo.forEach((r) => { indicePorBarrio[r.id] = r.indice || 0; });
  }

  const items = barrios.map((b) => {
    const n = b.nombre.length > 22 ? b.nombre.substring(0, 21) + '…' : b.nombre;
    const idx = indicePorBarrio[b.id] !== undefined ? indicePorBarrio[b.id] : (b.indice !== undefined ? b.indice : 0);
    const nivel = nivelRiesgo(idx);
    const cls = nivel;
    return `<li>
      <button type="button" data-barrio="${b.id}" data-x="${b.x}" data-y="${b.y}" title="${ESCAPAR(b.nombre)}">
        <span>${ESCAPAR(n)}</span>
        <span class="barrio-indice ${cls}">${idx}</span>
      </button>
    </li>`;
  }).join('');

  ul.innerHTML = `<li><button type="button" data-barrio="todos" class="activo">Todos los barrios</button></li>` + items;
}

/* ---------- Stats + mapa + KPIs ---------- */
const NIVEL_LABEL = { alto: '🔴', medio: '🟠', bajo: '🟢' };

async function cargarStats() {
  const stats = await api('stats');
  const porEstado = Object.fromEntries(stats.por_estado.map((s) => [s.estado, s.total]));

  $('[data-kpi=total] strong').textContent = stats.total;
  $('[data-kpi=pendiente] strong').textContent = porEstado.pendiente || 0;
  $('[data-kpi=verificado] strong').textContent = porEstado.verificado || 0;
  $('[data-kpi=controlado] strong').textContent = porEstado.controlado || 0;

  // Hero KPIs
  const hero = $('[data-js-hero-kpis]');
  if (hero) {
    hero.innerHTML = `
      <div class="hero-kpi"><strong>${stats.total}</strong><span>criaderos<br>reportados</span></div>
      <div class="hero-kpi"><strong>${stats.controlados_pct}%</strong><span>de reportes<br>controlados</span></div>`;
  }

  // Tipos más comunes
  const tipos = $('[data-js-tipos]');
  if (tipos) {
    tipos.innerHTML = stats.por_tipo
      .map((t) => `
        <div class="sector" style="--color:${ESCAPAR(t.color)}">
          <span class="icono">${t.icono}</span>
          <strong>${t.total}</strong>
          <span>${ESCAPAR(t.nombre || t.tipo)}</span>
        </div>`).join('')
      || '<p class="placeholder">Aún no hay reportes.</p>';
  }

  state.ultimaRiesgo = stats.riesgo_barrios;
  renderMapa(stats.riesgo_barrios);
  renderMapaFiltros();
  // Actualizar sidebar con los números actuales
  renderBarriosSidebar(state.barrios, stats.riesgo_barrios);
}

// Cuenta cuántos barrios hay en cada nivel y lo muestra en los botones de filtro
function renderMapaFiltros() {
  const riesgo = state.ultimaRiesgo || [];
  const cuenta = { alto: 0, medio: 0, bajo: 0 };
  riesgo.forEach((b) => { cuenta[nivelBarrio(b)]++; });
  $$('#mapa .mapa-filtros button[data-nivel]').forEach((btn) => {
    const n = btn.dataset.nivel;
    let badge = $('.cuenta', btn);
    if (n === 'todos') return;
    if (!badge) { badge = document.createElement('span'); badge.className = 'cuenta'; btn.appendChild(badge); }
    badge.textContent = cuenta[n];
  });
}

// El semáforo depende únicamente de los criaderos sin controlar.
// El clima se muestra aparte y no puede convertir un barrio en rojo.
function nivelBarrio(b) {
  return nivelRiesgo(b.indice);
}

/* ---------- Zoom/pan helpers ---------- */
function aplicarTransformMapa() {
  const imagen = $('[data-js-mapa-imagen]');
  const burbujas = $('[data-js-mapa-burbujas]');
  if (!imagen || !burbujas) return;
  const t = `translate(${state.mapaPanX}px, ${state.mapaPanY}px) scale(${state.mapaZoom})`;
  imagen.style.transform = t;
  burbujas.style.transform = t;
}

function resetZoom() {
  state.mapaZoom = 1;
  state.mapaPanX = 0;
  state.mapaPanY = 0;
  state.mapaEnfocadoBarrio = null;
  aplicarTransformMapa();
}

function zoomEnBarrio(barrio) {
  if (!barrio) { resetZoom(); return; }
  const wrapper = $('.mapa-wrapper');
  if (!wrapper) return;
  const rect = wrapper.getBoundingClientRect();
  const centroX = rect.width / 2;
  const centroY = rect.height / 2;
  // Zoom nivel 2.5
  state.mapaZoom = 2.5;
  // Centrar el barrio en el viewport
  state.mapaPanX = centroX - (barrio.x / 100) * rect.width * state.mapaZoom;
  state.mapaPanY = centroY - (barrio.y / 100) * rect.height * state.mapaZoom;
  state.mapaEnfocadoBarrio = barrio.id;
  aplicarTransformMapa();
}

function aplicarZoom(delta) {
  const nuevoZoom = Math.max(1, Math.min(4, state.mapaZoom * delta));
  if (nuevoZoom === state.mapaZoom) return;
  state.mapaZoom = nuevoZoom;
  if (nuevoZoom === 1) { resetZoom(); return; }
  aplicarTransformMapa();
}

/* ---------- Render mapa con zoom/pan ---------- */
function renderMapa(riesgo) {
  const contenedorBurbujas = $('[data-js-mapa-burbujas]');
  const imagen = $('[data-js-mapa-imagen]');
  if (!contenedorBurbujas || !imagen) return;

  const filtroNivel = state.mapaFiltroNivel || 'todos';
  const bonus = (window.CaszaClima && window.CaszaClima.listo) ? window.CaszaClima.bonus : 0;

  // La imagen de fondo ya está en CSS, no hace falta tocarla
  // Solo renderizamos las burbujas
  contenedorBurbujas.innerHTML = riesgo.map((b) => {
    const activos = Number(b.indice) || 0;
    const nivel = nivelBarrio(b);
    const oculto = (filtroNivel !== 'todos' && filtroNivel !== nivel) ? ' oculto' : '';
    const sel = state.filtros.barrio == b.id ? ' activo' : '';
    const extraClima = activos > 0 && bonus > 0 ? ` · alerta climática: +${bonus}` : '';
    return `
      <button type="button" class="mapa-node ${nivel}${sel}${oculto}" style="left:${b.x}%;top:${b.y}%"
              data-barrio="${b.id}" data-nivel="${nivel}" data-x="${b.x}" data-y="${b.y}"
              title="${ESCAPAR(b.nombre)}: ${activos} criadero(s) sin controlar${extraClima} · ${NIVELES[nivel].label}">
        <span class="burbuja">${activos}</span>
        <span class="nombre">${ESCAPAR(b.nombre)}</span>
      </button>`;
  }).join('');

  if (!riesgo.length) {
    contenedorBurbujas.innerHTML = '<p class="placeholder" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">Sin datos para el mapa.</p>';
  }
  // Mantener zoom actual al re-renderizar
  aplicarTransformMapa();
}

/* ---------- Reportes ---------- */
function reporteCard(r) {
  const acciones = [];
  if (r.estado === 'pendiente') {
    acciones.push(`<button class="btn-mini" data-estado data-id="${r.id}" data-sig="verificado">🔎 Verificar</button>`);
  } else if (r.estado === 'verificado') {
    acciones.push(`<button class="btn-mini btn-verde" data-estado data-id="${r.id}" data-sig="controlado">✅ Controlar</button>`);
  }
  acciones.push(`<button class="btn-voto" data-voto data-id="${r.id}">👍 ${r.votos}</button>`);
  acciones.push(`<button class="btn-del" data-eliminar data-id="${r.id}" title="Eliminar">✕</button>`);

  return `
    <article class="reporte" style="--color:${ESCAPAR(r.color)}">
      <div>
        <h3>${r.icono} ${ESCAPAR(r.titulo)}</h3>
        <p>${ESCAPAR(r.descripcion)}</p>
        <div class="meta">
          <span class="badge ${ESCAPAR(r.estado)}">${ESTADOS[r.estado] || r.estado}</span>
          <span>📍 Barrio ${ESCAPAR(r.barrio)}</span>
          ${r.referencia ? `<span>🧭 ${ESCAPAR(r.referencia)}</span>` : ''}
          <span>🏷️ ${ESCAPAR(r.tipo)}</span>
          <span>🕒 ${fechaRelativa(r.creado_en)}</span>
        </div>
      </div>
      <div class="acciones">${acciones.join(' ')}</div>
    </article>`;
}

function paginaItems(pagina, totalPaginas) {
  if (totalPaginas <= 7) {
    return Array.from({ length: totalPaginas }, (_, i) => i + 1);
  }

  const numeros = [...new Set([1, totalPaginas, pagina - 1, pagina, pagina + 1])]
    .filter((n) => n >= 1 && n <= totalPaginas)
    .sort((a, b) => a - b);
  const items = [];

  numeros.forEach((numero, index) => {
    if (index > 0 && numero - numeros[index - 1] > 1) items.push('…');
    items.push(numero);
  });

  return items;
}

function renderPaginacion(meta = {}) {
  const nav = $('[data-js-paginacion]');
  if (!nav) return;

  const pagina = Math.max(1, Number(meta.pagina) || state.paginaReportes || 1);
  const porPagina = Math.max(1, Number(meta.por_pagina) || REPORTES_POR_PAGINA);
  const total = Math.max(0, Number(meta.total) || 0);
  const totalPaginas = Math.max(0, Number(meta.total_paginas) || 0);

  state.paginaReportes = pagina;
  if (totalPaginas <= 1) {
    nav.hidden = true;
    return;
  }

  nav.hidden = false;
  const anterior = $('[data-pagina-anterior]', nav);
  const siguiente = $('[data-pagina-siguiente]', nav);
  const numeros = $('[data-js-paginacion-numeros]', nav);
  const info = $('[data-js-paginacion-info]', nav);

  if (anterior) anterior.disabled = pagina <= 1;
  if (siguiente) siguiente.disabled = pagina >= totalPaginas;
  if (numeros) {
    numeros.innerHTML = paginaItems(pagina, totalPaginas).map((item) => {
      if (item === '…') return '<span class="paginacion-ellipsis" aria-hidden="true">…</span>';
      const activo = item === pagina;
      return `<button type="button" class="reportes-pagina${activo ? ' activo' : ''}"
        data-pagina-reportes="${item}"${activo ? ' aria-current="page"' : ''}>${item}</button>`;
    }).join('');
  }
  if (info) {
    const desde = meta.desde !== undefined ? Number(meta.desde) : Math.min(total, (pagina - 1) * porPagina + 1);
    const hasta = meta.hasta !== undefined ? Number(meta.hasta) : Math.min(total, pagina * porPagina);
    info.textContent = `Mostrando ${desde}–${hasta} de ${total} reporte${total === 1 ? '' : 's'}`;
  }
}

async function cargarReportes() {
  console.log('cargarReportes() called');
  if (!tieneAccesoReportes()) return;

  const cont = $('[data-js-reportes]');
  if (!cont) return;

  const requestId = ++state.reportesRequestId;
  const f = state.filtros;
  const paginaSolicitada = Math.max(1, Number(state.paginaReportes) || 1);
  const params = new URLSearchParams();
  if (f.tipo) params.set('tipo', f.tipo);
  if (f.barrio) params.set('barrio', f.barrio);
  if (f.estado) params.set('estado', f.estado);
  if (f.q) params.set('q', f.q);
  params.set('pagina', paginaSolicitada);
  params.set('por_pagina', REPORTES_POR_PAGINA);

  cont.innerHTML = '<p class="loading">Cargando reportes…</p>';
  const respuesta = await apiRespuesta('reportes&' + params.toString());
  if (requestId !== state.reportesRequestId) return;

  const reportes = Array.isArray(respuesta.data) ? respuesta.data : [];
  const paginacion = respuesta.meta || {
    pagina: paginaSolicitada,
    por_pagina: REPORTES_POR_PAGINA,
    total: reportes.length,
    total_paginas: reportes.length ? 1 : 0,
    desde: reportes.length ? 1 : 0,
    hasta: reportes.length,
  };

  // Actualizar el título según el filtro de barrio activo
  if (f.barrio) {
    const b = state.barrios.find((x) => x.id == f.barrio);
    if (b) $('.panel-head h2', $('#reportes')).innerHTML =
      `📋 Criaderos de <span class="mapa-seleccionado">${ESCAPAR(b.nombre)}</span>`;
  } else {
    $('.panel-head h2', $('#reportes')).innerHTML = '📋 Criaderos reportados por la comunidad';
  }

  renderPaginacion(paginacion);
  if (!reportes.length) {
    cont.innerHTML = '<p class="placeholder">No hay criaderos con esos filtros. ¡Reportá el primero!</p>';
    return;
  }
  cont.innerHTML = reportes.map(reporteCard).join('');
}

/* ---------- Acciones ---------- */
async function crearReporte(ev) {
  console.log('crearReporte() called');
  ev.preventDefault();
  if (!puedeInteractuar()) {
    alert('Ya realizaste tu interacción diaria (reportar, votar o comentar). Volvé mañana.');
    return;
  }
  const form = ev.currentTarget;
  const data = Object.fromEntries(new FormData(form).entries());
  console.log('Form data:', data);
  const msg = $('[data-js-form-msg]');
  msg.className = 'form-msg';

  try {
    await api('reportes', {
      method: 'POST',
      body: JSON.stringify({
        tipo_id: Number(data.tipo_id),
        barrio_id: Number(data.barrio_id),
        titulo: data.titulo.trim(),
        descripcion: data.descripcion.trim(),
        referencia: data.referencia.trim(),
      }),
    });
    incrementarLimite();
    actualizarEstadoLimite();
    state.paginaReportes = 1;
    msg.textContent = '✅ ¡Criadero reportado! Ya aparece en el mapa de tu barrio.';
    msg.classList.add('ok');
    form.reset();
    await Promise.all([cargarStats(), cargarReportes()]);
  } catch (err) {
    msg.textContent = '❌ ' + err.message;
    msg.classList.add('err');
  }
}

async function cambiarEstado(id, estado) {
  try {
    await api(`reportes/${id}`, { method: 'PATCH', body: JSON.stringify({ estado }) });
    await Promise.all([cargarStats(), cargarReportes()]);
  } catch (err) { alert(err.message); }
}

async function votarReporte(id) {
  if (!puedeInteractuar()) {
    alert('Ya realizaste tu interacción diaria (reportar, votar o comentar). Volvé mañana.');
    return;
  }
  try {
    await api(`reportes/${id}`, { method: 'PATCH', body: JSON.stringify({ votos: 1 }) });
    incrementarLimite();
    actualizarEstadoLimite();
    await cargarReportes();
  } catch (err) { alert(err.message); }
}

async function eliminarReporte(id) {
  if (!confirm('¿Eliminar este reporte?')) return;
  try {
    await api(`reportes/${id}`, { method: 'DELETE' });
    await Promise.all([cargarStats(), cargarReportes()]);
  } catch (err) { alert(err.message); }
}

/* ---------- Navegación ---------- */
function navegar(dest) {
  console.log('navegar() called with:', dest);
  $$('[data-nav]').forEach((x) => x.classList.remove('active'));
  const target = dest === 'reportes' || dest === 'prevencion' || dest === 'quiz'
    ? $('#' + dest)
    : dest === 'nuevo' ? $('#nuevo') : $('#mapa');
  console.log('Target element found:', !!target);
  if (target) {
    window.scrollTo({ top: target.offsetTop - 16, behavior: 'smooth' });
    const nav = $(`.topbar a[data-nav="${dest}"]`);
    if (nav) nav.classList.add('active');
  }
}

/* ---------- Eventos ---------- */
function bind() {
  // Nav
  $$('[data-nav]').forEach((a) => a.addEventListener('click', (ev) => {
    console.log('Nav click:', a.dataset.nav);
    ev.preventDefault();
    navegar(a.dataset.nav);
  }));

  // Mapa: clic en barrio filtra reportes
  $('[data-js-mapa]')?.addEventListener('click', (ev) => {
    const node = ev.target.closest('[data-barrio]');
    if (!node) return;
    state.filtros.barrio = state.filtros.barrio == node.dataset.barrio ? '' : node.dataset.barrio;
    state.paginaReportes = 1;
    const selBarrio = $('[data-js-filtros] select[name=barrio]');
    if (selBarrio) selBarrio.value = state.filtros.barrio;   // mantener el filtro visible sincronizado
    renderMapa(state.ultimaRiesgo || []);
    cargarReportes();
    $('#reportes').scrollIntoView({ behavior: 'smooth' });
  });

  // Sidebar de barrios: clic centra el mapa en el barrio CON ZOOM
  $('[data-js-barra-barrios]')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-barrio]');
    if (!btn) return;
    const barrioId = btn.dataset.barrio;
    // Actualizar UI sidebar
    $$('[data-js-barra-barrios] button').forEach((b) => b.classList.remove('activo'));
    btn.classList.add('activo');
    if (barrioId === 'todos') {
      // Mostrar todos - reset zoom
      state.filtros.barrio = '';
      state.paginaReportes = 1;
      resetZoom();
      renderMapa(state.ultimaRiesgo || []);
      cargarReportes();
      return;
    }
    // Centrar mapa en el barrio CON ZOOM
    const barrio = state.barrios.find((b) => b.id == barrioId);
    if (barrio) {
      state.filtros.barrio = barrioId;
      state.paginaReportes = 1;
      // Actualizar filtro visible
      const selBarrio = $('[data-js-filtros] select[name=barrio]');
      if (selBarrio) selBarrio.value = barrioId;
      renderMapa(state.ultimaRiesgo || []);
      cargarReportes();
      // ZOOM real al barrio
      setTimeout(() => zoomEnBarrio(barrio), 50);
    }
  });

  // Formulario nuevo reporte
  $('[data-js-form]')?.addEventListener('submit', crearReporte);

  // Filtros
  $('[data-js-filtros]')?.addEventListener('change', async (ev) => {
    const f = new FormData(ev.currentTarget);
    state.filtros = {
      tipo: f.get('tipo') || '',
      barrio: f.get('barrio') || '',
      estado: f.get('estado') || '',
      q: f.get('q')?.trim() || '',
    };
    state.paginaReportes = 1;
    await cargarReportes();
  });

  $('[data-js-reset]')?.addEventListener('click', () => {
    $('[data-js-filtros]')?.reset();
    state.filtros = { tipo: '', barrio: '', estado: '', q: '' };
    state.paginaReportes = 1;
    cargarReportes();
    cargarStats();
  });

  // Acciones en listado de reportes
  $('[data-js-reportes]')?.addEventListener('click', (ev) => {
    const target = ev.target.closest('button');
    if (!target) return;
    // Los atributos data-estado / data-voto / data-eliminar no tienen valor (dataset = ""),
    // por eso se pregunta si existen con hasAttribute y no por su valor.
    if (target.hasAttribute('data-estado')) cambiarEstado(target.dataset.id, target.dataset.sig);
    if (target.hasAttribute('data-voto')) votarReporte(target.dataset.id);
    if (target.hasAttribute('data-eliminar')) eliminarReporte(target.dataset.id);
  });

  // Paginación del listado de reportes
  $('[data-js-paginacion]')?.addEventListener('click', (ev) => {
    const anterior = ev.target.closest('[data-pagina-anterior]');
    const siguiente = ev.target.closest('[data-pagina-siguiente]');
    const numero = ev.target.closest('button[data-pagina-reportes]');
    let pagina = state.paginaReportes;

    if (anterior) {
      if (anterior.disabled) return;
      pagina -= 1;
    } else if (siguiente) {
      if (siguiente.disabled) return;
      pagina += 1;
    } else if (numero) {
      pagina = Number(numero.dataset.paginaReportes);
    } else {
      return;
    }

    if (!Number.isInteger(pagina) || pagina < 1 || pagina === state.paginaReportes) return;
    state.paginaReportes = pagina;
    cargarReportes();
    $('#reportes')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  // Quiz
  $('[data-js-quiz]')?.addEventListener('click', (ev) => {
    const respuesta = ev.target.closest('[data-js-respuesta]');
    if (respuesta && !respuesta.disabled) responderQuiz(respuesta);
    if (ev.target.closest('[data-js-quiz-reiniciar]')) {
      state.quizIndex = 0; state.quizScore = 0; state.quizDone = false;
      renderQuiz();
    }
  });

  // Filtros del mapa (riesgo alto/medio/bajo)
  $('#mapa .mapa-filtros')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-nivel]');
    if (!btn) return;
    state.mapaFiltroNivel = btn.dataset.nivel;
    // Actualizar UI
    $$('#mapa .mapa-filtros button').forEach((b) => b.classList.remove('activo'));
    btn.classList.add('activo');
    // Re-renderizar mapa con filtro
    renderMapa(state.ultimaRiesgo || []);
  });

  // Controles de zoom del mapa
  $('#zoom-in')?.addEventListener('click', () => { aplicarZoom(1.5); });
  $('#zoom-out')?.addEventListener('click', () => { aplicarZoom(1/1.5); });
  $('#zoom-reset')?.addEventListener('click', () => { resetZoom(); });

  // Click en burbuja del mapa -> zoom a ese barrio
  $('[data-js-mapa-burbujas]')?.addEventListener('click', (ev) => {
    const node = ev.target.closest('.mapa-node[data-barrio]');
    if (!node) return;
    const barrioId = node.dataset.barrio;
    if (barrioId === 'todos') return;
    const barrio = state.barrios.find((b) => b.id == barrioId);
    if (barrio) zoomEnBarrio(barrio);
  });

  // Cuando llegan los datos de clima, recalcular el semáforo
  document.addEventListener('clima:listo', () => {
    renderMapa(state.ultimaRiesgo || []);
    renderMapaFiltros();
  });
}

/* ---------- Init ---------- */
async function init() {
  console.log('init() started');
  bind();
  actualizarEstadoLimite();
  try {
    console.log('Loading catalogs...');
    await cargarCatalogos();
    console.log('Loading stats...');
    const stats = await api('stats');
    state.ultimaRiesgo = stats.riesgo_barrios;
    console.log('Loading stats UI...');
    cargarStats();
    console.log('Loading reports...');
    if (tieneAccesoReportes()) {
      await cargarReportes();
    }
  } catch (err) {
    console.error('Init error:', err);
    const contReportes = $('[data-js-reportes]');
    if (contReportes) {
      contReportes.innerHTML = `<p class="placeholder">⚠️ ${ESCAPAR(err.message)}</p>`;
    }
  }
  console.log('Rendering quiz...');
  renderQuiz();
  console.log('init() completed');
}

document.addEventListener('DOMContentLoaded', init);