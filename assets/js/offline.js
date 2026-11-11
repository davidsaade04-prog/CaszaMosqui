/* ============================================================
   CaszaMosqui — App instalable y modo sin internet (lado de la página)
   - Registra el service worker (sw.js).
   - Botón "📲 Instalar app" (Android/PC) e instrucciones para iPhone.
   - Aviso de "sin conexión" y cola de reportes hechos sin señal:
       window.CaszaOffline.encolar(datos)  → guarda el reporte en el celular
       window.CaszaOffline.enviar()        → intenta mandar la cola
     La cola se envía sola al volver la señal (y en segundo plano con
     Background Sync, donde el navegador lo permite).
   Funciona igual sin service worker (navegadores viejos): solo no instala.
   ============================================================ */
(function () {
  const BASE = window.BASE_URL ?? '';
  const DB_NOMBRE = 'casza-offline', DB_STORE = 'cola';
  const enApp = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

  /* ---------------- IndexedDB (misma base que usa sw.js) ---------------- */
  function abrirDB() {
    return new Promise((resolve, reject) => {
      if (!('indexedDB' in window)) { reject(new Error('Este navegador no puede guardar reportes sin señal.')); return; }
      const r = indexedDB.open(DB_NOMBRE, 1);
      r.onupgradeneeded = () => r.result.createObjectStore(DB_STORE, { keyPath: 'cliente_id' });
      r.onsuccess = () => resolve(r.result);
      r.onerror = () => reject(r.error);
    });
  }
  async function operar(modo, fn) {
    const db = await abrirDB();
    return new Promise((resolve, reject) => {
      const t = db.transaction(DB_STORE, modo);
      const req = fn(t.objectStore(DB_STORE));
      t.oncomplete = () => resolve(req ? req.result : undefined);
      t.onerror = () => reject(t.error);
    });
  }
  const leerCola = () => operar('readonly', (s) => s.getAll()).catch(() => []);

  function nuevoId() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'c' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
  }

  /* ---------------- Aviso de conexión / cola ---------------- */
  const aviso = document.createElement('div');
  aviso.className = 'estado-conexion';
  aviso.setAttribute('role', 'status');
  aviso.setAttribute('aria-live', 'polite');
  aviso.hidden = true;
  document.body.appendChild(aviso);

  let ultimoTotal = 0;
  async function actualizarAviso(mensajeExtra) {
    const cola = await leerCola();
    const pendientes = cola.filter((i) => !i.error);
    const conError = cola.filter((i) => i.error);
    ultimoTotal = pendientes.length;
    const partes = [];
    if (!navigator.onLine) partes.push('<strong>📵 Sin conexión.</strong> Podés seguir usando la app y reportar criaderos.');
    if (pendientes.length) {
      partes.push(`⏳ ${pendientes.length === 1 ? '1 reporte espera' : pendientes.length + ' reportes esperan'} señal para enviarse.` +
        (navigator.onLine ? ' <button type="button" data-js-reintentar>Enviar ahora</button>' : ''));
    }
    conError.forEach((i) => {
      partes.push(`⚠️ “${escapar(i.datos.titulo)}” no se pudo enviar: ${escapar(i.error)} ` +
        `<button type="button" data-js-descartar="${escapar(i.cliente_id)}">Descartar</button>`);
    });
    if (mensajeExtra) partes.push(mensajeExtra);
    aviso.innerHTML = partes.join('<br>');
    aviso.hidden = partes.length === 0;
    aviso.classList.toggle('offline', !navigator.onLine);
  }
  function escapar(t) {
    return String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  aviso.addEventListener('click', async (ev) => {
    if (ev.target.closest('[data-js-reintentar]')) enviar();
    const d = ev.target.closest('[data-js-descartar]');
    if (d && confirm('¿Descartar este reporte? No se va a enviar.')) {
      await operar('readwrite', (s) => s.delete(d.dataset.jsDescartar));
      actualizarAviso();
    }
  });

  /* ---------------- Cola ---------------- */
  async function encolar(datos) {
    const item = { cliente_id: datos.cliente_id || nuevoId(), datos, creado: new Date().toISOString() };
    item.datos.cliente_id = item.cliente_id;
    await operar('readwrite', (s) => s.put(item));
    // Envío en segundo plano aunque se cierre la app (Chrome/Android)
    try {
      const reg = await navigator.serviceWorker?.ready;
      if (reg && reg.sync) await reg.sync.register('casza-enviar-reportes');
    } catch (e) { /* sin Background Sync: lo envía la página al volver la señal */ }
    actualizarAviso();
    return item.cliente_id;
  }

  let enviando = false;
  async function enviarSinLock() {
    const cola = (await leerCola()).filter((i) => !i.error);
    let enviados = 0;
    for (const item of cola) {
      let res, json;
      try {
        res = await fetch(BASE + '/api/index.php?route=reportes', {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(item.datos),
        });
        json = await res.json().catch(() => ({}));
      } catch (e) {
        break;   // sigue sin señal
      }
      if (res.ok && json.ok) {
        await operar('readwrite', (s) => s.delete(item.cliente_id));
        enviados++;
      } else if (res.status >= 400 && res.status < 500 && !json.offline) {
        item.error = json.error || 'El servidor rechazó el reporte.';
        await operar('readwrite', (s) => s.put(item));
      } else {
        break;   // servidor caído o dormido: se reintenta más tarde
      }
    }
    return enviados;
  }

  async function enviar() {
    if (enviando || !navigator.onLine) return 0;
    enviando = true;
    let n = 0;
    try {
      n = navigator.locks
        ? await navigator.locks.request('casza-cola', enviarSinLock)
        : await enviarSinLock();
    } finally {
      enviando = false;
      if (n > 0) avisarEnviados(n); else actualizarAviso();
    }
    return n;
  }

  function avisarEnviados(n) {
    actualizarAviso(`✅ ${n === 1 ? 'Se envió 1 reporte' : `Se enviaron ${n} reportes`} guardado${n === 1 ? '' : 's'} sin señal. ¡Gracias!`);
    setTimeout(() => actualizarAviso(), 6000);
    // Refrescar mapa e indicadores
    if (typeof cargarStats === 'function') cargarStats().catch(() => {});
    if (typeof cargarReportes === 'function' && window.REPORTES_ACCESS === true) cargarReportes().catch(() => {});
  }

  window.addEventListener('online', () => { actualizarAviso(); enviar(); });
  window.addEventListener('offline', () => actualizarAviso());
  document.addEventListener('visibilitychange', () => { if (!document.hidden) enviar(); });

  window.CaszaOffline = { encolar, enviar, pendientes: () => ultimoTotal, nuevoId };

  /* ---------------- Service worker ---------------- */
  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', async () => {
      try {
        const reg = await navigator.serviceWorker.register(BASE + '/sw.js', { scope: BASE + '/' });
        await navigator.serviceWorker.ready;
        // Guardar los archivos que esta página ya cargó (antes de existir el service worker)
        const urls = performance.getEntriesByType('resource').map((e) => e.name)
          .concat([...document.querySelectorAll('script[src], link[rel=stylesheet][href], img[src]')]
            .map((el) => el.src || el.href));
        (reg.active || navigator.serviceWorker.controller)?.postMessage({ tipo: 'guardar', urls: [...new Set(urls)] });
      } catch (e) {
        console.warn('No se pudo activar el modo sin internet:', e);
      }
    });
    navigator.serviceWorker.addEventListener('message', (ev) => {
      if (ev.data && ev.data.tipo === 'reporte-enviado') avisarEnviados(1);
    });
  }

  /* ---------------- Botón "Instalar app" ---------------- */
  let pedidoInstalar = null;
  const esIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

  function crearBoton() {
    if (enApp || document.querySelector('.btn-instalar')) return null;
    const topbar = document.querySelector('.topbar');
    if (!topbar) return null;
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn-instalar';
    b.innerHTML = '<span aria-hidden="true">📲</span><span class="btn-instalar-txt">Instalar app</span>';
    b.title = 'Instalar CaszaMosqui en este dispositivo';
    topbar.appendChild(b);
    b.addEventListener('click', instalar);
    return b;
  }

  async function instalar() {
    if (pedidoInstalar) {
      pedidoInstalar.prompt();
      const r = await pedidoInstalar.userChoice.catch(() => null);
      pedidoInstalar = null;
      if (r && r.outcome === 'accepted') document.querySelector('.btn-instalar')?.remove();
      return;
    }
    // iPhone/iPad: Safari no muestra el aviso de instalación; se explica el paso a paso
    const d = document.createElement('dialog');
    d.className = 'login-modal instalar-modal';
    d.innerHTML = `
      <form method="dialog" class="login-modal-cerrar-form"><button class="login-modal-cerrar" aria-label="Cerrar">✕</button></form>
      <div class="reportes-login-icon" aria-hidden="true">📲</div>
      <h2>Instalar CaszaMosqui</h2>
      ${esIOS
        ? `<ol class="instalar-pasos"><li>Abrí esta página en <strong>Safari</strong>.</li>
             <li>Tocá el botón <strong>Compartir</strong> (el cuadrado con la flecha ⬆️).</li>
             <li>Elegí <strong>“Agregar a inicio”</strong> y tocá <strong>Agregar</strong>.</li></ol>`
        : `<ol class="instalar-pasos"><li>Abrí el menú del navegador (<strong>⋮</strong> arriba a la derecha).</li>
             <li>Elegí <strong>“Instalar app”</strong> o <strong>“Agregar a pantalla de inicio”</strong>.</li></ol>`}
      <p class="sub">Queda con su ícono, como cualquier aplicación, y funciona aunque no tengas señal.</p>`;
    document.body.appendChild(d);
    d.addEventListener('close', () => d.remove());
    d.addEventListener('click', (ev) => { if (ev.target === d) d.close(); });
    d.showModal ? d.showModal() : d.setAttribute('open', '');
  }

  window.addEventListener('beforeinstallprompt', (ev) => {
    ev.preventDefault();
    pedidoInstalar = ev;
    crearBoton();
  });
  window.addEventListener('appinstalled', () => document.querySelector('.btn-instalar')?.remove());
  // En iPhone no existe beforeinstallprompt: se muestra el botón con las instrucciones
  if (esIOS && !enApp) document.addEventListener('DOMContentLoaded', crearBoton);

  /* ---------------- Inicio ---------------- */
  const iniciar = () => { actualizarAviso(); enviar(); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
