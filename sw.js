/* ============================================================
   CaszaMosqui — Service worker (app instalable + modo sin internet)

   Estrategias:
   - Archivos de la app (CSS, JS, imágenes, plano): se guardan al instalar y se
     sirven desde el celular; se actualizan en segundo plano.
   - Páginas y datos públicos de la API (barrios, mapa, clima, comentarios):
     primero internet; sin señal (o si el servidor tarda), la última copia guardada.
   - Mapa real (teselas de OpenStreetMap): solo las que el usuario ya miró,
     con tope, respetando la política de uso de OSM (no se descarga el mapa en masa).
   - Nunca se guarda nada del administrador (admin.php, reportes, fotos, respaldo).
   - Reportes hechos sin señal: quedan en una cola (IndexedDB "casza-offline")
     y se envían solos al volver la conexión (Background Sync donde existe;
     si no, lo hace la página al abrirse — ver assets/js/offline.js).

   Para publicar cambios en los archivos cacheados: subir VERSION.
   ============================================================ */
const VERSION = 'casza-v5-20261113';
const CACHE_APP = `${VERSION}-app`;
const CACHE_DATOS = `${VERSION}-datos`;
const CACHE_MAPA = 'casza-teselas-v1';   // independiente de VERSION: las teselas no cambian
const MAX_TESELAS = 400;
const ESPERA_RED_MS = 6000;               // Render gratuito puede tardar en "despertar"

const BASE = new URL('./', self.location).pathname;   // "/" en Render, "/formosahack-2026/" en XAMPP

const PRECARGA = [
  './',
  'reportar.php',
  'prevencion.php',
  'centros-salud.php',
  'cuestionario.php',
  'ayuda.php',
  'acerca.php',
  'contacto.php',
  'comentarios.php',
  'test-sintomas.php',
  'manifest.webmanifest',
  'assets/css/styles.css',
  'assets/css/chatbot.css',
  'assets/css/clima.css',
  'assets/css/leaflet.css',
  'assets/css/mapa-plano.css',
  'assets/js/app.js',
  'assets/js/calles.js',
  'assets/js/clima.js',
  'assets/js/leaflet.js',
  'assets/js/geo.js',
  'assets/js/mapa-real.js',
  'assets/js/centros-salud.js',
  'assets/fonts/inter-display-bold.woff',
  'assets/fonts/inter-display-extrabold.woff',
  'assets/img/logo-emblema.png',
  'assets/img/logo-casza-hero.webp',
  'assets/js/reporte-extra.js',
  'assets/js/offline.js',
  'assets/js/chatbot.js',
  'assets/js/comentarios.js',
  'assets/img/logo-casza.jpeg',
  'assets/img/mapa-el-colorado.svg',
  'assets/img/app/icono-192.png',
  'assets/img/logo-ipf.png',
  'assets/img/logo-rfa.png',
  'assets/img/logo-ia.png',
  'api/index.php?route=tipos',
  'api/index.php?route=barrios',
  'api/index.php?route=stats',
];

// Rutas de la API que se pueden guardar para verlas sin señal (datos públicos)
const API_PUBLICAS = new Set(['tipos', 'barrios', 'stats', 'comentarios', 'seguimiento']);

self.addEventListener('install', (ev) => {
  ev.waitUntil((async () => {
    const cache = await caches.open(CACHE_APP);
    // Uno por uno: si algún archivo falla, el resto igual queda guardado
    await Promise.all(PRECARGA.map(async (ruta) => {
      try {
        const res = await fetch(new Request(ruta, { cache: 'reload', credentials: 'same-origin' }));
        if (res.ok) await (ruta.startsWith('api/') ? (await caches.open(CACHE_DATOS)) : cache).put(ruta, res);
      } catch (e) { /* sin señal al instalar: se completará después */ }
    }));
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (ev) => {
  ev.waitUntil((async () => {
    const nombres = await caches.keys();
    await Promise.all(nombres
      .filter((n) => n.startsWith('casza-') && n !== CACHE_APP && n !== CACHE_DATOS && n !== CACHE_MAPA)
      .map((n) => caches.delete(n)));
    await self.clients.claim();
  })());
});

/* ---------------- Helpers ---------------- */
function rutaRelativa(url) {
  return url.pathname.startsWith(BASE) ? url.pathname.slice(BASE.length) : url.pathname;
}

function esRespuestaGuardable(res) {
  return res && res.ok && res.type === 'basic';
}

// Internet primero; si tarda más de ESPERA_RED_MS y hay copia, se usa la copia (la red sigue
// actualizando la copia en segundo plano). Sin copia, se espera a la red.
async function redPrimero(req, nombreCache, claveCache) {
  const cache = await caches.open(nombreCache);
  const clave = claveCache || req;
  const red = fetch(req).then(async (res) => {
    if (esRespuestaGuardable(res)) await cache.put(clave, res.clone());
    return res;
  });
  const copia = await cache.match(clave, { ignoreVary: true });
  if (!copia) {
    try { return await red; } catch (e) { return respuestaSinSenal(req); }
  }
  let timer;
  const espera = new Promise((resolve) => { timer = setTimeout(() => resolve(copia), ESPERA_RED_MS); });
  try {
    const res = await Promise.race([red, espera]);
    clearTimeout(timer);
    red.catch(() => {});
    return res;
  } catch (e) {
    clearTimeout(timer);
    return copia;
  }
}

// Archivos de la app: copia exacta primero (y se actualiza en segundo plano).
// Si cambió la versión (?v=…) se baja la nueva; sin señal, sirve cualquier versión guardada.
async function copiaPrimero(req, nombreCache) {
  const cache = await caches.open(nombreCache);
  const exacta = await cache.match(req);
  const red = fetch(req).then(async (res) => {
    if (esRespuestaGuardable(res)) await cache.put(req, res.clone());
    return res;
  });
  if (exacta) {
    red.catch(() => {});
    return exacta;
  }
  try {
    return await red;
  } catch (e) {
    return (await cache.match(req, { ignoreSearch: true })) || respuestaSinSenal(req);
  }
}

async function teselaMapa(req) {
  const cache = await caches.open(CACHE_MAPA);
  const copia = await cache.match(req);
  if (copia) return copia;
  try {
    const res = await fetch(req);
    if (res.ok || res.type === 'opaque') {
      await cache.put(req, res.clone());
      const claves = await cache.keys();
      if (claves.length > MAX_TESELAS) {
        await Promise.all(claves.slice(0, claves.length - MAX_TESELAS).map((k) => cache.delete(k)));
      }
    }
    return res;
  } catch (e) {
    return new Response('', { status: 504, statusText: 'Sin conexión' });
  }
}

function respuestaSinSenal(req) {
  if (req.mode === 'navigate') {
    return new Response(
      `<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
       <title>Sin conexión · CaszaMosqui</title>
       <body style="font-family:system-ui,sans-serif;background:#0b1220;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:24px">
       <div><div style="font-size:3rem">📵</div><h1 style="margin:.3em 0">Sin conexión</h1>
       <p>Esta página todavía no se guardó en tu celular.<br>Abrí <a style="color:#5eead4" href="${BASE}">el inicio de CaszaMosqui</a>: ahí podés reportar aunque no tengas señal.</p></div></body></html>`,
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  }
  const url = new URL(req.url);
  if (url.pathname.includes('/api/')) {
    return new Response(JSON.stringify({ ok: false, offline: true, error: 'Sin conexión a internet.' }),
      { status: 503, headers: { 'Content-Type': 'application/json; charset=utf-8' } });
  }
  return new Response('', { status: 503, statusText: 'Sin conexión' });
}

/* ---------------- Ruteo ---------------- */
self.addEventListener('fetch', (ev) => {
  const req = ev.request;
  if (req.method !== 'GET') return;   // los envíos (POST, PATCH…) van directo; la cola la maneja offline.js
  const url = new URL(req.url);

  // Teselas del mapa real
  if (url.hostname === 'tile.openstreetmap.org') {
    ev.respondWith(teselaMapa(req));
    return;
  }
  if (url.origin !== self.location.origin) return;

  const ruta = rutaRelativa(url);

  // Nada del administrador ni del login se guarda
  if (ruta.startsWith('admin.php') || ruta.startsWith('reportes-auth.php')) return;

  // API: solo rutas públicas
  if (ruta.startsWith('api/')) {
    if (ruta === 'api/clima.php') {
      ev.respondWith(redPrimero(req, CACHE_DATOS, new Request(new URL('api/clima.php', self.location).href)));
      return;
    }
    const r = (url.searchParams.get('route') || '').split('/')[0];
    if ((ruta === 'api/' || ruta === 'api/index.php') && API_PUBLICAS.has(r)) {
      // La app pide "api/?route=…"; se guarda siempre con la misma clave
      const clave = new Request(new URL('api/index.php' + url.search, self.location).href);
      ev.respondWith(redPrimero(req, CACHE_DATOS, clave));
    }
    return;   // el resto de la API (reportes, fotos, respaldo, chat) siempre va a internet
  }

  // Páginas públicas
  if (req.mode === 'navigate') {
    if (ruta === '' || ruta === 'index.php' || ruta === 'reportar.php' || ruta === 'prevencion.php'
        || ruta === 'centros-salud.php' || ruta === 'cuestionario.php'
        || ruta === 'ayuda.php' || ruta === 'acerca.php' || ruta === 'contacto.php'
        || ruta === 'comentarios.php' || ruta === 'test-sintomas.php') {
      // La página guardada no depende de la query (?origen=app, ?demo=1…)
      const clave = new Request(new URL(ruta === 'index.php' ? './' : ruta || './', self.location).href);
      ev.respondWith(redPrimero(req, CACHE_APP, clave));
    }
    return;
  }

  // Videos de la ayuda: directo de la red (el navegador los pide por partes, con Range)
  if (ruta.startsWith('assets/video/')) return;

  // Archivos de la app
  if (ruta.startsWith('assets/') || ruta === 'manifest.webmanifest') {
    ev.respondWith(copiaPrimero(req, CACHE_APP));
  }
});

/* ---------------- Cola de reportes sin señal (Background Sync) ---------------- */
const DB_NOMBRE = 'casza-offline', DB_STORE = 'cola';

function abrirDB() {
  return new Promise((resolve, reject) => {
    const r = indexedDB.open(DB_NOMBRE, 1);
    r.onupgradeneeded = () => r.result.createObjectStore(DB_STORE, { keyPath: 'cliente_id' });
    r.onsuccess = () => resolve(r.result);
    r.onerror = () => reject(r.error);
  });
}
function tx(db, modo, fn) {
  return new Promise((resolve, reject) => {
    const t = db.transaction(DB_STORE, modo);
    const out = fn(t.objectStore(DB_STORE));
    t.oncomplete = () => resolve(out && 'result' in out ? out.result : undefined);
    t.onerror = () => reject(t.error);
  });
}

function enviarCola() {
  // Un solo envío a la vez entre la página y el service worker (evita mandar dos veces)
  return self.navigator && self.navigator.locks
    ? self.navigator.locks.request('casza-cola', enviarColaAhora)
    : enviarColaAhora();
}

async function enviarColaAhora() {
  const db = await abrirDB();
  const pendientes = (await tx(db, 'readonly', (s) => s.getAll())).filter((i) => !i.error);
  let enviados = 0;
  for (const item of pendientes) {
    let res;
    try {
      res = await fetch(new URL('api/index.php?route=reportes', self.location).href, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item.datos),
      });
    } catch (e) {
      throw e;   // sigue sin señal: el navegador reintenta el sync más tarde
    }
    const json = await res.json().catch(() => ({}));
    if (res.ok && json.ok) {
      await tx(db, 'readwrite', (s) => s.delete(item.cliente_id));
      enviados++;
      const clientes = await self.clients.matchAll({ includeUncontrolled: true });
      clientes.forEach((c) => c.postMessage({ tipo: 'reporte-enviado', cliente_id: item.cliente_id, data: json.data }));
    } else if (res.status >= 400 && res.status < 500) {
      // Datos rechazados por el servidor (no es un problema de señal): se marca para revisión
      item.error = json.error || 'El servidor rechazó el reporte.';
      await tx(db, 'readwrite', (s) => s.put(item));
    }
  }
  return enviados;
}

self.addEventListener('sync', (ev) => {
  if (ev.tag === 'casza-enviar-reportes') ev.waitUntil(enviarCola());
});

self.addEventListener('message', (ev) => {
  const msg = ev.data || {};
  if (msg === 'saltar-espera') self.skipWaiting();
  // La página avisa qué archivos usó (cargados antes de que existiera el service worker)
  if (msg.tipo === 'guardar' && Array.isArray(msg.urls)) {
    ev.waitUntil((async () => {
      const cache = await caches.open(CACHE_APP);
      await Promise.all(msg.urls.slice(0, 80).map(async (u) => {
        try {
          const url = new URL(u, self.location);
          if (url.origin !== self.location.origin || !rutaRelativa(url).startsWith('assets/')) return;
          if (await cache.match(url.href)) return;
          const res = await fetch(url.href, { credentials: 'same-origin' });
          if (esRespuestaGuardable(res)) await cache.put(url.href, res);
        } catch (e) { /* sin señal */ }
      }));
    })());
  }
  if (msg.tipo === 'enviar-cola') ev.waitUntil(enviarCola().catch(() => 0));
});
