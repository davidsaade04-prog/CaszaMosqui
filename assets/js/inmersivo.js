/* ============================================================
   CaszaMosqui — Modo inmersivo (El Colorado en 3D)
   - Piso: el plano municipal en versión holográfica (assets/img/plano-holo.webp).
   - Cada barrio es una torre de luz: la altura = criaderos activos; el color = nivel
     de riesgo con el clima (el mismo algoritmo que el modo normal: clima.js / inc/riesgo.php).
   - Los centros de salud son balizas con una cruz.
   - Tocar una torre abre su ficha; las opciones flotantes abren cada sección en un panel
     (la página real con ?embed=1), sin salir del mapa.
   ============================================================ */
import * as THREE from 'three';
import { OrbitControls } from '../vendor/three/OrbitControls.js';

const BASE = window.BASE_URL || '';
const $ = (s) => document.querySelector(s);
const REDUCIR = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const MOVIL = window.matchMedia('(max-width: 760px)').matches;

/* ---------- Geometría del plano (mismas constantes que assets/js/geo.js) ---------- */
const PLANO_W = 2400, PLANO_H = 2675;
const AF_LAT = [-1.1498059675629435e-07, -1.337972814725863e-05, -26.296628714163564];
const AF_LON = [1.4575534747793875e-05, 1.9014188133714693e-07, -59.383492970162585];
const ANCHO = 100, LARGO = ANCHO * PLANO_H / PLANO_W;           // tamaño del piso en unidades 3D
const aEscena = (xPct, yPct) => [(xPct / 100 - 0.5) * ANCHO, (yPct / 100 - 0.5) * LARGO];
/** lat/lng → % del plano (inversa de planoALatLng) */
function latLngAPlano(lat, lng) {
  const a = AF_LAT[0], b = AF_LAT[1], c = AF_LON[0], d = AF_LON[1];
  const y1 = lat - AF_LAT[2], y2 = lng - AF_LON[2], det = a * d - b * c;
  const px = (d * y1 - b * y2) / det, py = (a * y2 - c * y1) / det;
  return [(px / PLANO_W) * 100, (py / PLANO_H) * 100];
}

const COLOR = { alto: 0xef4444, medio: 0xf59e0b, bajo: 0x22c55e, salud: 0x38bdf8 };
const NIVEL_TXT = { alto: 'Riesgo alto', medio: 'Riesgo medio', bajo: 'Riesgo bajo' };
const SECCIONES = {
  reportar:     { titulo: '📢 Reportar un criadero', url: 'reportar.php' },
  salud:        { titulo: '🏥 Centros de salud', url: 'centros-salud.php' },
  comentarios:  { titulo: '💬 Comentarios', url: 'comentarios.php' },
  sintomas:     { titulo: '🩺 Test de síntomas', url: 'test-sintomas.php' },
  prevencion:   { titulo: '🛡️ Prevención', url: 'prevencion.php' },
  cuestionario: { titulo: '🧠 Cuestionario', url: 'cuestionario.php' },
  ayuda:        { titulo: '❓ Ayuda', url: 'ayuda.php' },
};
const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/* ======================= Escena ======================= */
const canvas = $('[data-js-escena]');
const carga = $('[data-js-carga]');
let renderer;
try {
  renderer = new THREE.WebGLRenderer({ canvas, antialias: true, powerPreference: 'high-performance' });
} catch (e) {
  carga.classList.add('error');
  $('[data-js-carga-texto]').innerHTML = 'Tu dispositivo no puede mostrar el mapa en 3D.<br><a href="' + BASE + '/?modo=normal">Ir al modo normal</a>';
  throw e;
}
renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, MOVIL ? 1.6 : 2));
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.05;

const escena = new THREE.Scene();
escena.background = new THREE.Color(0x060b16);
escena.fog = new THREE.FogExp2(0x060b16, 0.0058);

const camara = new THREE.PerspectiveCamera(42, 1, 0.5, 900);
const VISTA = MOVIL ? new THREE.Vector3(0, 150, 112) : new THREE.Vector3(0, 70, 92);
camara.position.set(0, 230, 0.1);

const controles = new OrbitControls(camara, canvas);
controles.enableDamping = true;
controles.dampingFactor = 0.08;
controles.minDistance = 16;
controles.maxDistance = 190;
controles.minPolarAngle = 0.12;
controles.maxPolarAngle = 1.3;
controles.autoRotateSpeed = 0.35;
controles.enabled = false;

escena.add(new THREE.HemisphereLight(0x8fd3e3, 0x060b16, 1.1));
const sol = new THREE.DirectionalLight(0xffffff, 1.4);
sol.position.set(40, 90, 30);
escena.add(sol);

/* ---------- Texturas generadas (brillos) ---------- */
function texturaBrillo(interior = 'rgba(255,255,255,1)') {
  const c = document.createElement('canvas');
  c.width = c.height = 128;
  const g = c.getContext('2d');
  const r = g.createRadialGradient(64, 64, 0, 64, 64, 64);
  r.addColorStop(0, interior);
  r.addColorStop(0.25, 'rgba(255,255,255,.55)');
  r.addColorStop(1, 'rgba(255,255,255,0)');
  g.fillStyle = r;
  g.fillRect(0, 0, 128, 128);
  const t = new THREE.CanvasTexture(c);
  t.colorSpace = THREE.SRGBColorSpace;
  return t;
}
const BRILLO = texturaBrillo();

/* ---------- Piso: plano holográfico + grilla infinita + marco ---------- */
const piso = new THREE.Mesh(
  new THREE.PlaneGeometry(ANCHO, LARGO),
  new THREE.MeshBasicMaterial({ color: 0xffffff }),
);
piso.rotation.x = -Math.PI / 2;
escena.add(piso);

const grilla = new THREE.GridHelper(700, 140, 0x0e7490, 0x0c2d3a);
grilla.material.transparent = true;
grilla.material.opacity = 0.35;
grilla.position.y = -0.06;
escena.add(grilla);

const marco = new THREE.LineLoop(
  new THREE.BufferGeometry().setFromPoints([
    new THREE.Vector3(-ANCHO / 2, 0.05, -LARGO / 2), new THREE.Vector3(ANCHO / 2, 0.05, -LARGO / 2),
    new THREE.Vector3(ANCHO / 2, 0.05, LARGO / 2), new THREE.Vector3(-ANCHO / 2, 0.05, LARGO / 2),
  ]),
  new THREE.LineBasicMaterial({ color: 0x22d3ee, transparent: true, opacity: 0.8 }),
);
escena.add(marco);

// Barrido de radar: un anillo que recorre el pueblo cada pocos segundos
const radar = new THREE.Mesh(
  new THREE.RingGeometry(0.96, 1, 96),
  new THREE.MeshBasicMaterial({ color: 0x67e8f9, transparent: true, opacity: 0.5, blending: THREE.AdditiveBlending, depthWrite: false, side: THREE.DoubleSide }),
);
radar.rotation.x = -Math.PI / 2;
radar.position.y = 0.08;
escena.add(radar);

/* ---------- Barrios: torres de luz ---------- */
const torres = new Map();      // id → { grupo, cuerpo, halo, anillo, datos, altura, alturaObjetivo, enjambre }
const tocables = [];           // objetos que se pueden tocar (raycast)
const GEO_TORRE = new THREE.CylinderGeometry(1.35, 1.6, 1, 6, 1, false);
GEO_TORRE.translate(0, 0.5, 0);                // base apoyada en el piso
const GEO_BORDE = new THREE.EdgesGeometry(GEO_TORRE);
const GEO_ANILLO = new THREE.RingGeometry(1.9, 2.25, 48);

function alturaPara(activos) { return Math.min(26, 1.1 + activos * 2.6); }

function crearTorre(b) {
  const [x, z] = aEscena(Number(b.x), Number(b.y));
  const grupo = new THREE.Group();
  grupo.position.set(x, 0, z);

  const cuerpo = new THREE.Mesh(GEO_TORRE, new THREE.MeshStandardMaterial({
    color: COLOR.bajo, emissive: COLOR.bajo, emissiveIntensity: 0.55,
    metalness: 0.15, roughness: 0.35, transparent: true, opacity: 0.9,
  }));
  const borde = new THREE.LineSegments(GEO_BORDE, new THREE.LineBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.35 }));
  cuerpo.add(borde);
  cuerpo.scale.y = 0.01;
  cuerpo.userData = { tipo: 'barrio', id: b.id };
  grupo.add(cuerpo);

  const halo = new THREE.Sprite(new THREE.SpriteMaterial({ map: BRILLO, color: COLOR.bajo, transparent: true, opacity: 0.8, blending: THREE.AdditiveBlending, depthWrite: false }));
  halo.scale.set(6, 6, 1);
  grupo.add(halo);

  const anillo = new THREE.Mesh(GEO_ANILLO, new THREE.MeshBasicMaterial({ color: COLOR.bajo, transparent: true, opacity: 0, blending: THREE.AdditiveBlending, depthWrite: false, side: THREE.DoubleSide }));
  anillo.rotation.x = -Math.PI / 2;
  anillo.position.y = 0.1;
  grupo.add(anillo);

  escena.add(grupo);
  tocables.push(cuerpo);
  const t = { grupo, cuerpo, halo, anillo, datos: b, altura: 0.01, alturaObjetivo: 0.01, fase: Math.random() * Math.PI * 2, enjambre: null };
  torres.set(String(b.id), t);
  return t;
}

/* Mosquitos: un enjambre de puntitos alrededor de los barrios con más criaderos */
const GEO_MOSQUITO = (() => {
  const c = document.createElement('canvas');
  c.width = c.height = 32;
  const g = c.getContext('2d');
  g.fillStyle = '#fff';
  g.beginPath(); g.arc(16, 16, 5, 0, Math.PI * 2); g.fill();
  const t = new THREE.CanvasTexture(c);
  return t;
})();
function actualizarEnjambre(t, cantidad) {
  if (t.enjambre) { t.grupo.remove(t.enjambre); t.enjambre.geometry.dispose(); t.enjambre = null; }
  if (cantidad <= 0) return;
  const n = Math.min(36, cantidad * 6);
  const pos = new Float32Array(n * 3);
  const semillas = [];
  for (let i = 0; i < n; i++) semillas.push({ r: 2.2 + Math.random() * 3.2, a: Math.random() * Math.PI * 2, h: Math.random(), v: 0.6 + Math.random() * 1.4 });
  const geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  const puntos = new THREE.Points(geo, new THREE.PointsMaterial({ map: GEO_MOSQUITO, color: 0xfca5a5, size: 0.55, transparent: true, opacity: 0.9, depthWrite: false }));
  puntos.userData.semillas = semillas;
  t.enjambre = puntos;
  t.grupo.add(puntos);
}

/* ---------- Centros de salud: balizas ---------- */
const balizas = [];
function crearBaliza(c) {
  if (c.lat == null) return;
  const [xp, yp] = latLngAPlano(c.lat, c.lng);
  const [x, z] = aEscena(xp, yp);
  const grupo = new THREE.Group();
  grupo.position.set(x, 0, z);

  const haz = new THREE.Mesh(
    new THREE.CylinderGeometry(0.12, 0.12, 9, 8, 1, true).translate(0, 4.5, 0),
    new THREE.MeshBasicMaterial({ color: COLOR.salud, transparent: true, opacity: 0.55, blending: THREE.AdditiveBlending, depthWrite: false }),
  );
  grupo.add(haz);

  const cruz = new THREE.Group();
  const mat = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: c.guardia ? 0xef4444 : COLOR.salud, emissiveIntensity: 0.9, roughness: 0.3 });
  cruz.add(new THREE.Mesh(new THREE.BoxGeometry(2.2, 0.7, 0.7), mat));
  cruz.add(new THREE.Mesh(new THREE.BoxGeometry(0.7, 2.2, 0.7), mat));
  cruz.position.y = 10.2;
  grupo.add(cruz);

  const halo = new THREE.Sprite(new THREE.SpriteMaterial({ map: BRILLO, color: c.guardia ? 0xf87171 : COLOR.salud, transparent: true, opacity: 0.7, blending: THREE.AdditiveBlending, depthWrite: false }));
  halo.scale.set(5.5, 5.5, 1);
  halo.position.y = 10.2;
  grupo.add(halo);

  const base = new THREE.Mesh(new THREE.RingGeometry(0.9, 1.25, 40), new THREE.MeshBasicMaterial({ color: COLOR.salud, transparent: true, opacity: 0.8, blending: THREE.AdditiveBlending, side: THREE.DoubleSide, depthWrite: false }));
  base.rotation.x = -Math.PI / 2;
  base.position.y = 0.1;
  grupo.add(base);

  // Zona invisible más grande para tocarla fácil con el dedo
  const toque = new THREE.Mesh(new THREE.CylinderGeometry(1.8, 1.8, 12, 8).translate(0, 6, 0), new THREE.MeshBasicMaterial({ visible: false }));
  toque.userData = { tipo: 'centro', id: c.id };
  grupo.add(toque);
  tocables.push(toque);

  escena.add(grupo);
  balizas.push({ grupo, cruz, halo, datos: c, fase: Math.random() * 6 });
}
(window.CENTROS_SALUD || []).forEach(crearBaliza);

/* ======================= Datos ======================= */
let barrios = [];       // datos del barrio con nivel con clima
let climaBonus = 0, climaNivel = null;

function nivelConClima(activos) {
  const total = activos > 0 ? activos + climaBonus : 0;
  return total >= 4 ? 'alto' : total >= 2 ? 'medio' : 'bajo';
}

async function cargarDatos() {
  const [stats, clima] = await Promise.all([
    fetch(BASE + '/api/index.php?route=stats').then((r) => r.json()),
    fetch(BASE + '/api/clima.php').then((r) => r.json()).catch(() => null),
  ]);
  if (clima && clima.ok !== false) { climaBonus = Number(clima.bonus) || 0; climaNivel = clima.nivel || null; }
  barrios = (stats.data?.riesgo_barrios || []).filter((b) => b.x != null && b.y != null).map((b) => ({
    ...b, indice: Number(b.indice) || 0, nivel: nivelConClima(Number(b.indice) || 0),
  }));
  barrios.forEach((b) => {
    const t = torres.get(String(b.id)) || crearTorre(b);
    t.datos = b;
    t.alturaObjetivo = alturaPara(b.indice);
    const col = new THREE.Color(COLOR[b.nivel]);
    t.cuerpo.material.color.copy(col);
    t.cuerpo.material.emissive.copy(col);
    t.halo.material.color.copy(col);
    t.anillo.material.color.copy(col);
    t.anillo.material.opacity = b.nivel === 'bajo' ? 0 : 0.8;
    if ((t.enjambreN || 0) !== b.indice) { actualizarEnjambre(t, b.indice >= 2 ? b.indice : 0); t.enjambreN = b.indice; }
  });
  // KPIs
  $('[data-kpi=activos]').textContent = barrios.reduce((s, b) => s + b.indice, 0);
  $('[data-kpi=alto]').textContent = barrios.filter((b) => b.nivel === 'alto').length;
  const chip = $('[data-js-clima]');
  if (climaNivel && climaBonus > 0) {
    chip.hidden = false;
    chip.className = 'inm-kpi inm-kpi-clima ' + climaNivel;
    chip.innerHTML = `🌧️ Clima: riesgo <b>${climaNivel.toUpperCase()}</b>`;
    chip.title = `El clima suma +${climaBonus} al riesgo de los barrios con criaderos`;
  } else chip.hidden = true;
  if (fichaActual) mostrarFicha(fichaActual.tipo, fichaActual.id, false);
}

/* ======================= Etiquetas HTML ======================= */
const capaEtiquetas = $('[data-js-etiquetas]');
const etiquetas = new Map();
function etiquetaDe(clave, html, clase) {
  let el = etiquetas.get(clave);
  if (!el) {
    el = document.createElement('span');
    el.className = 'inm-etiqueta ' + clase;
    capaEtiquetas.appendChild(el);
    etiquetas.set(clave, el);
  }
  if (el.dataset.html !== html) { el.innerHTML = html; el.dataset.html = html; }
  return el;
}
const v3 = new THREE.Vector3();
function ubicarEtiquetas() {
  const w = canvas.clientWidth, h = canvas.clientHeight;
  const distancia = camara.position.distanceTo(controles.target);
  const candidatas = [];
  torres.forEach((t, id) => {
    const b = t.datos;
    const importante = b.nivel !== 'bajo' || id === hoverId || id === seleccionId;
    const visible = importante || distancia < 55;
    const el = etiquetaDe('b' + id, `${esc(b.nombre)}${b.indice ? ` <b>${b.indice}</b>` : ''}`, 'barrio ' + b.nivel);
    v3.set(t.grupo.position.x, t.altura + 2.2, t.grupo.position.z).project(camara);
    const enPantalla = v3.z < 1 && Math.abs(v3.x) < 1.1 && Math.abs(v3.y) < 1.1;
    candidatas.push({ el, x: (v3.x + 1) / 2 * w, y: (1 - v3.y) / 2 * h, ok: visible && enPantalla, prio: (id === seleccionId ? 100 : id === hoverId ? 90 : 0) + b.indice * 2 + (b.nivel === 'alto' ? 5 : 0), z: v3.z });
  });
  balizas.forEach((bz) => {
    const el = etiquetaDe('c' + bz.datos.id, `${bz.datos.guardia ? '🏥' : '🩺'} ${esc(bz.datos.nombre.replace(/ "Opizzo Traverso"/, ''))}`, 'centro');
    v3.set(bz.grupo.position.x, 12.4, bz.grupo.position.z).project(camara);
    const enPantalla = v3.z < 1 && Math.abs(v3.x) < 1.1 && Math.abs(v3.y) < 1.1;
    const id = 'c:' + bz.datos.id;
    const visible = bz.datos.guardia || distancia < 70 || id === hoverId || id === seleccionId;
    candidatas.push({ el, x: (v3.x + 1) / 2 * w, y: (1 - v3.y) / 2 * h, ok: visible && enPantalla, prio: (id === seleccionId ? 100 : id === hoverId ? 90 : 0) + (bz.datos.guardia ? 40 : 3), z: v3.z });
  });
  // Evitar que se encimen: entran primero las más importantes
  candidatas.sort((a, b) => b.prio - a.prio);
  const ocupadas = [];
  candidatas.forEach((c) => {
    if (!c.ok) { c.el.classList.remove('ver'); return; }
    const ancho = c.el.offsetWidth || 90, alto = 24;
    const r = { x1: c.x - ancho / 2, x2: c.x + ancho / 2, y1: c.y - alto, y2: c.y };
    const choca = ocupadas.some((o) => r.x1 < o.x2 && r.x2 > o.x1 && r.y1 < o.y2 && r.y2 > o.y1);
    if (choca && c.prio < 90) { c.el.classList.remove('ver'); return; }
    ocupadas.push(r);
    c.el.classList.add('ver');
    c.el.style.transform = `translate(${c.x.toFixed(1)}px, ${c.y.toFixed(1)}px) translate(-50%, -100%)`;
    c.el.style.zIndex = String(Math.round((1 - c.z) * 1000) + c.prio);
  });
}

/* ======================= Tocar torres y balizas ======================= */
const rayo = new THREE.Raycaster();
const puntero = new THREE.Vector2();
let hoverId = null, seleccionId = null, abajo = null;

function objetoEn(ev) {
  const r = canvas.getBoundingClientRect();
  puntero.set(((ev.clientX - r.left) / r.width) * 2 - 1, -((ev.clientY - r.top) / r.height) * 2 + 1);
  rayo.setFromCamera(puntero, camara);
  const hit = rayo.intersectObjects(tocables, false)[0];
  return hit ? hit.object.userData : null;
}
const claveDe = (u) => (u ? (u.tipo === 'centro' ? 'c:' + u.id : String(u.id)) : null);

canvas.addEventListener('pointermove', (ev) => {
  if (ev.pointerType === 'touch') return;
  const u = objetoEn(ev);
  hoverId = claveDe(u);
  canvas.style.cursor = u ? 'pointer' : 'grab';
});
canvas.addEventListener('pointerdown', (ev) => { abajo = { x: ev.clientX, y: ev.clientY }; });
canvas.addEventListener('pointerup', (ev) => {
  if (!abajo || Math.hypot(ev.clientX - abajo.x, ev.clientY - abajo.y) > 6) { abajo = null; return; }
  abajo = null;
  const u = objetoEn(ev);
  if (u) mostrarFicha(u.tipo, u.id, true);
  else cerrarFicha();
});

/* ---------- Ficha ---------- */
const ficha = $('[data-js-ficha]');
let fichaActual = null;
function mostrarFicha(tipo, id, enfocar) {
  fichaActual = { tipo, id };
  if (tipo === 'barrio') {
    const t = torres.get(String(id));
    if (!t) return;
    const b = t.datos;
    seleccionId = String(id);
    ficha.className = 'inm-ficha ' + b.nivel;
    ficha.innerHTML = `
      <button type="button" class="inm-ficha-x" data-ficha-cerrar aria-label="Cerrar">✕</button>
      <p class="inm-ficha-tipo">Barrio</p>
      <h3>${esc(b.nombre)}</h3>
      <span class="inm-nivel ${b.nivel}">${NIVEL_TXT[b.nivel]}</span>
      <p class="inm-ficha-dato"><b>${b.indice}</b> ${b.indice === 1 ? 'criadero activo' : 'criaderos activos'} (reportados y sin controlar)</p>
      ${climaBonus > 0 && b.indice > 0 ? `<p class="inm-ficha-nota">El clima de estos días suma +${climaBonus} al riesgo.</p>` : ''}
      <div class="inm-ficha-acciones">
        <button type="button" class="inm-btn inm-btn-rojo" data-abrir="reportar" data-barrio="${esc(b.id)}">📢 Reportar en este barrio</button>
      </div>`;
    if (enfocar) enfocarEn(t.grupo.position, t.altura);
  } else {
    const bz = balizas.find((x) => x.datos.id === id);
    if (!bz) return;
    const c = bz.datos;
    seleccionId = 'c:' + id;
    ficha.className = 'inm-ficha salud';
    const tel = c.telefonos && c.telefonos[0];
    ficha.innerHTML = `
      <button type="button" class="inm-ficha-x" data-ficha-cerrar aria-label="Cerrar">✕</button>
      <p class="inm-ficha-tipo">${esc(c.tipo)}</p>
      <h3>${esc(c.nombre)}</h3>
      <p class="inm-ficha-dato">📍 ${esc(c.direccion)}</p>
      <p class="inm-ficha-dato">🕒 ${esc(c.horario_txt)}</p>
      <div class="inm-ficha-acciones">
        ${tel ? `<a class="inm-btn inm-btn-verde" href="tel:${esc(tel[2])}">📞 Llamar</a>` : ''}
        <button type="button" class="inm-btn" data-abrir="salud">🧭 Cómo llegar</button>
      </div>`;
    if (enfocar) enfocarEn(bz.grupo.position, 10);
  }
  ficha.hidden = false;
  document.body.classList.add('con-ficha');
  requestAnimationFrame(() => ficha.classList.add('abierta'));
}
function cerrarFicha() {
  fichaActual = null;
  seleccionId = null;
  ficha.classList.remove('abierta');
  document.body.classList.remove('con-ficha');
  setTimeout(() => { if (!fichaActual) ficha.hidden = true; }, 250);
}
ficha.addEventListener('click', (ev) => { if (ev.target.closest('[data-ficha-cerrar]')) cerrarFicha(); });

/* ---------- Movimientos de cámara ---------- */
let vuelo = null;   // { desdePos, haciaPos, desdeObj, haciaObj, t0, dur }
const suave = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
function volar(haciaPos, haciaObj, dur = 1400) {
  if (REDUCIR) { camara.position.copy(haciaPos); controles.target.copy(haciaObj); return; }
  vuelo = { desdePos: camara.position.clone(), haciaPos: haciaPos.clone(), desdeObj: controles.target.clone(), haciaObj: haciaObj.clone(), t0: performance.now(), dur };
}
function enfocarEn(pos, altura) {
  const obj = new THREE.Vector3(pos.x, Math.min(altura, 8) * 0.4, pos.z);
  const dir = camara.position.clone().sub(controles.target).normalize();
  const dist = MOVIL ? 58 : 48;
  volar(obj.clone().add(dir.multiplyScalar(dist)), obj, 1100);
  pausarGiro();
}

/* Giro automático lento hasta que la persona toca; vuelve después de un rato quieto */
let giroTimer = null;
function pausarGiro() {
  controles.autoRotate = false;
  clearTimeout(giroTimer);
  if (!REDUCIR) giroTimer = setTimeout(() => { if (!panelAbierto && !fichaActual) controles.autoRotate = true; }, 25000);
}
controles.addEventListener('start', () => { vuelo = null; pausarGiro(); ocultarGesto(); });
// Que la cámara no se vaya del pueblo
const LIM = new THREE.Vector3(ANCHO * 0.55, 0, LARGO * 0.55);
controles.addEventListener('change', () => {
  controles.target.x = THREE.MathUtils.clamp(controles.target.x, -LIM.x, LIM.x);
  controles.target.z = THREE.MathUtils.clamp(controles.target.z, -LIM.z, LIM.z);
  controles.target.y = THREE.MathUtils.clamp(controles.target.y, 0, 10);
});

const gesto = $('[data-js-gesto]');
function ocultarGesto() { gesto?.classList.add('oculto'); }

/* ======================= Panel de secciones ======================= */
const panel = $('[data-js-panel]');
const frame = $('[data-js-panel-frame]');
let panelAbierto = false, ultimoFoco = null;
function abrirSeccion(clave, extra = '') {
  const s = SECCIONES[clave];
  if (!s) return;
  const url = `${BASE}/${s.url}?embed=1${extra}`;
  ultimoFoco = document.activeElement;
  $('[data-js-panel-titulo]').textContent = s.titulo;
  $('[data-js-panel-link]').href = `${BASE}/${s.url}${extra ? '?' + extra.slice(1) : ''}`;
  frame.src = url;
  panel.hidden = false;
  panelAbierto = true;
  controles.autoRotate = false;
  document.body.classList.add('con-panel');
  requestAnimationFrame(() => panel.classList.add('abierto'));
  setTimeout(() => panel.querySelector('.inm-panel-x').focus(), 60);
}
function cerrarSeccion() {
  if (!panelAbierto) return;
  panelAbierto = false;
  panel.classList.remove('abierto');
  document.body.classList.remove('con-panel');
  setTimeout(() => { if (!panelAbierto) { panel.hidden = true; frame.src = 'about:blank'; } }, 320);
  cargarDatos().catch(() => {});
  if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
  pausarGiro();
}
document.addEventListener('click', (ev) => {
  const b = ev.target.closest('[data-abrir]');
  if (b) { ev.preventDefault(); abrirSeccion(b.dataset.abrir, b.dataset.barrio ? '&barrio=' + encodeURIComponent(b.dataset.barrio) : ''); return; }
  if (ev.target.closest('[data-js-panel-cerrar]')) cerrarSeccion();
});
document.addEventListener('keydown', (ev) => {
  if (ev.key !== 'Escape') return;
  if (panelAbierto) cerrarSeccion();
  else if (fichaActual) cerrarFicha();
});
// Un reporte enviado desde el panel actualiza las torres al instante
window.addEventListener('message', (ev) => {
  if (ev.origin === location.origin && ev.data && ev.data.tipo === 'casza-actualizar') cargarDatos().catch(() => {});
});

// En el celular la barra de opciones se desliza: quitar el esfumado al llegar al final
const barraOrbes = document.querySelector('.inm-orbes');
barraOrbes.addEventListener('scroll', () => {
  barraOrbes.classList.toggle('al-final', barraOrbes.scrollLeft + barraOrbes.clientWidth >= barraOrbes.scrollWidth - 4);
}, { passive: true });

/* ======================= Bucle de dibujo ======================= */
function ajustarTamano() {
  const w = window.innerWidth, h = window.innerHeight;
  renderer.setSize(w, h, false);
  camara.aspect = w / h;
  camara.fov = w < h ? 55 : 42;
  camara.updateProjectionMatrix();
}
window.addEventListener('resize', ajustarTamano);
ajustarTamano();

const reloj = new THREE.Clock();
let cuadro = 0;
function dibujar() {
  requestAnimationFrame(dibujar);
  if (document.hidden) return;
  cuadro++;
  // Con el panel abierto en el celular el mapa casi no se ve: dibujar menos seguido
  if (panelAbierto && MOVIL && cuadro % 4) return;
  const t = reloj.getElapsedTime();

  if (vuelo) {
    const k = Math.min(1, (performance.now() - vuelo.t0) / vuelo.dur);
    const e = suave(k);
    camara.position.lerpVectors(vuelo.desdePos, vuelo.haciaPos, e);
    controles.target.lerpVectors(vuelo.desdeObj, vuelo.haciaObj, e);
    if (k >= 1) vuelo = null;
  }
  controles.update();

  torres.forEach((tr, id) => {
    tr.altura += (tr.alturaObjetivo - tr.altura) * 0.06;
    tr.cuerpo.scale.y = tr.altura;
    const resalta = id === hoverId || id === seleccionId;
    tr.cuerpo.material.emissiveIntensity += ((resalta ? 1.1 : 0.55) - tr.cuerpo.material.emissiveIntensity) * 0.15;
    const pulso = REDUCIR ? 0 : Math.sin(t * 2 + tr.fase);
    tr.halo.position.y = tr.altura + 0.6;
    const s = (tr.datos.nivel === 'alto' ? 7.5 : tr.datos.nivel === 'medio' ? 6 : 4.5) + pulso * 0.6 + (resalta ? 2 : 0);
    tr.halo.scale.set(s, s, 1);
    if (tr.anillo.material.opacity > 0 && !REDUCIR) {
      const k = (t * 0.6 + tr.fase) % 1;
      tr.anillo.scale.setScalar(1 + k * (tr.datos.nivel === 'alto' ? 2.6 : 1.8));
      tr.anillo.material.opacity = 0.8 * (1 - k);
    }
    if (tr.enjambre) {
      const pos = tr.enjambre.geometry.attributes.position;
      tr.enjambre.userData.semillas.forEach((m, i) => {
        const a = m.a + t * m.v;
        pos.setXYZ(i, Math.cos(a) * m.r, tr.altura * (0.35 + m.h * 0.75) + Math.sin(t * 3 * m.v + i) * 0.5, Math.sin(a * 1.3) * m.r);
      });
      pos.needsUpdate = true;
    }
  });

  balizas.forEach((bz) => {
    if (!REDUCIR) {
      bz.cruz.rotation.y = t * 0.8 + bz.fase;
      bz.cruz.position.y = 10.2 + Math.sin(t * 1.6 + bz.fase) * 0.4;
      bz.halo.position.y = bz.cruz.position.y;
    }
    const id = 'c:' + bz.datos.id, resalta = id === hoverId || id === seleccionId;
    const s = 5.5 + (resalta ? 2.5 : 0);
    bz.halo.scale.set(s, s, 1);
  });

  if (!REDUCIR) {
    const k = (t / 7) % 1;
    const r = 4 + k * 82;
    radar.scale.set(r, r, 1);
    radar.material.opacity = 0.45 * (1 - k);
  } else radar.visible = false;

  renderer.render(escena, camara);
  ubicarEtiquetas();
}

/* ======================= Arranque ======================= */
function listo() {
  carga.classList.add('lista');
  setTimeout(() => carga.remove(), 900);
  document.body.classList.add('lista');
  controles.enabled = true;
  volar(VISTA, new THREE.Vector3(0, 0, MOVIL ? 14 : 4), 2600);
  if (!REDUCIR) setTimeout(() => { if (!fichaActual && !panelAbierto && !vuelo) controles.autoRotate = true; }, 2700);
  setTimeout(ocultarGesto, 9000);
}

new THREE.TextureLoader().load(BASE + '/assets/img/plano-holo.webp', (tex) => {
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
  piso.material.map = tex;
  piso.material.needsUpdate = true;
});

dibujar();
Promise.race([
  cargarDatos(),
  new Promise((ok) => setTimeout(ok, 6000)),
]).catch(() => {
  $('[data-js-carga-texto]').textContent = 'Sin conexión: mostramos el mapa con los últimos datos guardados.';
}).finally(() => setTimeout(listo, 350));
// Actualizar solo cada 2 minutos
setInterval(() => { if (!document.hidden && !panelAbierto) cargarDatos().catch(() => {}); }, 120000);
