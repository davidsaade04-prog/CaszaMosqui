/* ============================================================
   CaszaMosqui — Pestaña "Mapa real" (OpenStreetMap + Leaflet)
   - Convive con el plano municipal: mismos datos, mismo semáforo, mismos filtros.
   - Los barrios se ubican con una transformación calculada a partir del plano
     municipal y de esquinas geolocalizadas con la API oficial Georef
     (apis.datos.gob.ar). Precisión aproximada: ±50 m.
   - Se carga solo cuando se abre la pestaña (no gasta datos si nadie la usa).
   Depende de app.js (state, nivelBarrio, seleccionarBarrio, renderMapa…),
   de assets/js/geo.js y de assets/js/leaflet.js.
   ============================================================ */
(function () {
  if (!document.querySelector('[data-js-mapa-real]') || !window.CaszaGeo) return;

  // Conversión plano → coordenadas reales: assets/js/geo.js
  const planoALatLng = window.CaszaGeo.planoALatLng;
  const LIMITES_PLANO = window.CaszaGeo.LIMITES_PLANO;

  // `state` es una constante global de app.js (no cuelga de window)
  const S = () => (typeof state !== 'undefined' ? state : null);
  const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  let mapa = null, capaBarrios = null, capaPlano = null, vistaActual = 'plano';
  const marcadores = {};

  function crearMapa() {
    if (mapa || !window.L) return;
    const cont = document.querySelector('[data-js-mapa-real]');
    mapa = L.map(cont, {
      zoomControl: true,
      minZoom: 13, maxZoom: 19,
      maxBounds: L.latLngBounds(LIMITES_PLANO).pad(1.2),
      maxBoundsViscosity: 0.8,
    });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
    }).addTo(mapa);
    mapa.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>');
    mapa.attributionControl.addAttribution('Plano: Municipalidad de El Colorado');
    // De lejos, nombres cortos y barrios sin criaderos como puntos (evita que se amontonen)
    const ajustarDetalle = () => cont.classList.toggle('mr-lejos', mapa.getZoom() < 16);
    mapa.on('zoomend', ajustarDetalle);

    // Plano municipal superpuesto (opcional): aporta los nombres de calles que faltan en el mapa
    const base = (window.BASE_URL ?? '');
    capaPlano = L.imageOverlay(base + '/assets/img/mapa-el-colorado.svg', LIMITES_PLANO, {
      opacity: 0.6, interactive: false, className: 'mapa-real-plano',
    });

    const Control = L.Control.extend({
      options: { position: 'topright' },
      onAdd() {
        const div = L.DomUtil.create('div', 'mapa-real-control');
        div.innerHTML = `
          <label><input type="checkbox" data-js-plano-encima> <span class="mr-txt-largo">Mostrar plano municipal encima</span><span class="mr-txt-corto">Plano encima</span></label>
          <label class="mapa-real-opacidad" hidden>Transparencia
            <input type="range" min="20" max="90" value="60" data-js-plano-opacidad></label>
          ${CENTROS.length ? '<label><input type="checkbox" data-js-ver-salud checked> 🏥 Centros de salud</label>' : ''}
          ${ES_ADMIN ? '<label><input type="checkbox" data-js-ver-gps checked> 📍 Criaderos con GPS (<span data-js-cuenta-gps>…</span>)</label>' : ''}
          ${ES_ADMIN && window.CaszaCalor ? `<label><input type="checkbox" data-js-ver-calor> 🔥 Mapa de calor</label>
            <div class="mapa-calor-leyenda" hidden><span class="mapa-calor-barra"></span>
              <span class="mapa-calor-txt"><span>Pocos</span><span>Muchos criaderos</span></span>
              <small>Criaderos sin controlar y verificados. Los que no tienen GPS se ubican en el centro de su barrio.</small></div>` : ''}`;
        L.DomEvent.disableClickPropagation(div);
        L.DomEvent.disableScrollPropagation(div);
        const chk = div.querySelector('[data-js-plano-encima]');
        const rango = div.querySelector('[data-js-plano-opacidad]');
        chk.addEventListener('change', () => {
          if (chk.checked) capaPlano.addTo(mapa); else capaPlano.remove();
          div.querySelector('.mapa-real-opacidad').hidden = !chk.checked;
          if (capaBarrios) capaBarrios.eachLayer((m) => m.setZIndexOffset(1000));
        });
        rango.addEventListener('input', () => capaPlano.setOpacity(rango.value / 100));
        div.querySelector('[data-js-ver-salud]')?.addEventListener('change', (ev) => {
          if (ev.target.checked) capaSalud.addTo(mapa); else capaSalud.remove();
        });
        div.querySelector('[data-js-ver-gps]')?.addEventListener('change', (ev) => {
          if (ev.target.checked) capaCriaderos.addTo(mapa); else capaCriaderos.remove();
        });
        div.querySelector('[data-js-ver-calor]')?.addEventListener('change', (ev) => {
          div.querySelector('.mapa-calor-leyenda').hidden = !ev.target.checked;
          if (ev.target.checked) { capaCalor.addTo(mapa); cargarCalor(); } else capaCalor.remove();
          document.querySelector('[data-js-mapa-real]').classList.toggle('con-calor', ev.target.checked);
        });
        return div;
      },
    });
    new Control().addTo(mapa);

    capaBarrios = L.layerGroup().addTo(mapa);
    dibujarSalud();
    if (ES_ADMIN) capaCriaderos = L.layerGroup().addTo(mapa);
    mapa.fitBounds(LIMITES_PLANO, { padding: [10, 10] });
    ajustarDetalle();
    dibujarBarrios();
    encuadrarBarrios();
    cargarPuntos();
  }

  // Encuadre inicial: solo la zona con barrios (más cerca que el plano completo)
  function encuadrarBarrios() {
    const pts = Object.values(marcadores).map((m) => m.getLatLng());
    if (pts.length) mapa.fitBounds(L.latLngBounds(pts), { padding: [30, 30] });
  }

  function dibujarBarrios() {
    if (!mapa || !capaBarrios) return;
    const riesgo = (S() && S().ultimaRiesgo) || [];
    const filtro = (S() && S().mapaFiltroNivel) || 'todos';
    const bonus = (window.CaszaClima && window.CaszaClima.listo) ? window.CaszaClima.bonus : 0;
    capaBarrios.clearLayers();
    riesgo.forEach((b) => {
      if (b.x == null || b.y == null) return;
      const activos = Number(b.indice) || 0;
      const nivel = typeof nivelBarrio === 'function' ? nivelBarrio(b) : (b.nivel || 'bajo');
      if (filtro !== 'todos' && filtro !== nivel) return;
      const sel = S() && S().filtros.barrio == b.id;
      const icono = L.divIcon({
        className: 'mr-icono',
        html: `<span class="mr-pin ${nivel}${activos === 0 ? ' none' : ''}${sel ? ' activo' : ''}"><span class="mr-largo">${esc(b.nombre)}</span><span class="mr-corto">${esc((typeof NOMBRE_CORTO !== 'undefined' && NOMBRE_CORTO[b.nombre]) || b.nombre)}</span>${activos > 0 ? `<b>${activos}</b>` : ''}</span>`,
        iconSize: null,
      });
      const extra = activos > 0 && bonus > 0 ? ` (+${bonus} por clima)` : '';
      const m = L.marker(planoALatLng(b.x, b.y), { icon: icono, title: `${b.nombre}: ${activos} criadero(s) sin controlar${extra}`, riseOnHover: true })
        .addTo(capaBarrios);
      m.on('click', () => {
        if (typeof seleccionarBarrio === 'function') seleccionarBarrio(b.id);
        mapa.flyTo(planoALatLng(b.x, b.y), Math.max(mapa.getZoom(), 16), { duration: 0.6 });
      });
      marcadores[b.id] = m;
    });
  }


  /* ---------- Centros de salud (para todos; datos de inc/centros-salud.php) ---------- */
  const CENTROS = (window.CENTROS_SALUD || []).filter((c) => c.lat != null);
  let capaSalud = null;
  function dibujarSalud() {
    if (!CENTROS.length) return;
    capaSalud = L.layerGroup().addTo(mapa);
    CENTROS.forEach((c) => {
      const m = L.marker([c.lat, c.lng], {
        icon: window.CaszaGeo.iconoPin(c.guardia ? '#dc2626' : '#0284c7', c.guardia ? '🏥' : '🩺'),
        title: c.nombre, zIndexOffset: 1500, riseOnHover: true,
      }).addTo(capaSalud);
      m.bindPopup(() => {
        const div = document.createElement('div');
        div.className = 'mr-popup';
        const h = window.CaszaSalud ? window.CaszaSalud.estadoHorario(c.horario) : { estado: 'sin-dato', texto: c.horario_txt };
        div.innerHTML = `<strong>${esc(c.nombre)}</strong>
          <span class="salud-estado ${h.estado}">${esc(h.texto)}</span>
          <small>📍 ${esc(c.direccion)}</small>
          <div class="mr-popup-acciones">
            ${c.telefonos[0] ? `<a class="salud-btn salud-llamar" href="tel:${esc(c.telefonos[0][2])}">📞 Llamar</a>` : ''}
            ${window.CaszaSalud && window.CaszaSalud.comoLlegar ? '<button type="button" class="salud-btn salud-ir" data-ir>🧭 Cómo llegar</button>' : ''}
          </div>`;
        div.querySelector('[data-ir]')?.addEventListener('click', () => { m.closePopup(); window.CaszaSalud.comoLlegar(c.id); });
        return div;
      }, { minWidth: 210 });
    });
  }

  /* ---------- Solo administrador: criaderos con ubicación GPS ---------- */
  // El público solo ve el semáforo por barrio; los pines exactos y las fotos son del panel.
  const ES_ADMIN = window.REPORTES_ACCESS === true && !!document.querySelector('#reportes');
  const COLOR_ESTADO = { pendiente: '#dc2626', verificado: '#d97706', controlado: '#16a34a' };
  const TXT_ESTADO = { pendiente: 'Sin controlar', verificado: 'Verificado', controlado: 'Controlado' };
  let capaCriaderos = null, pinesReporte = {}, verPendiente = null, recargaProgramada = null;
  const capaCalor = ES_ADMIN && window.CaszaCalor ? window.CaszaCalor.crear() : null;

  async function cargarCalor() {
    if (!capaCalor || !mapa || !mapa.hasLayer(capaCalor)) return;
    try {
      const reportes = await api('reportes-calor');
      capaCalor.setPuntos(window.CaszaCalor.prepararPuntos(reportes, (typeof state !== 'undefined' && state.barrios) || []));
    } catch (e) { /* sin sesión o sin conexión */ }
  }

  function urlFoto(id) { return (window.BASE_URL ?? '') + '/api/index.php?route=reportes/' + id + '/foto'; }

  async function guardarUbicacion(id, ubicacion) {
    await api(`reportes/${id}`, { method: 'PATCH', body: JSON.stringify({ ubicacion }) });
  }

  function popupReporte(p, marker) {
    const div = document.createElement('div');
    div.className = 'mr-popup';
    div.innerHTML = `
      <strong>${esc(p.icono)} ${esc(p.titulo)}</strong>
      <span class="mr-popup-estado" style="--pin:${COLOR_ESTADO[p.estado] || '#64748b'}">${TXT_ESTADO[p.estado] || esc(p.estado)}</span>
      <small>📍 ${esc(p.barrio)}${p.referencia ? ' · ' + esc(p.referencia) : ''}</small>
      <small>🏷️ ${esc(p.tipo)} · 🕒 ${esc(String(p.creado_en).slice(0, 16))}</small>
      ${Number(p.tiene_foto) === 1 ? `<img src="${urlFoto(p.id)}" alt="Foto del criadero" loading="lazy" data-ver-foto>` : ''}
      <div class="mr-popup-acciones">
        <button type="button" data-mover>✋ Mover pin</button>
        <button type="button" data-quitar class="peligro">🗑️ Quitar ubicación</button>
      </div>`;
    div.querySelector('[data-ver-foto]')?.addEventListener('click', () => window.CaszaAdmin && window.CaszaAdmin.verFoto(p.id));
    div.querySelector('[data-mover]').addEventListener('click', () => {
      marker.closePopup();
      marker.dragging.enable();
      marker.getElement()?.classList.add('moviendo');
      avisoMapa('✋ Arrastrá el pin hasta el lugar correcto y soltalo.');
    });
    div.querySelector('[data-quitar]').addEventListener('click', async () => {
      if (!confirm('¿Quitar la ubicación de este reporte? El reporte se mantiene, solo se borra el pin.')) return;
      try { await guardarUbicacion(p.id, null); avisoMapa('Ubicación quitada.'); await cargarPuntos(); if (typeof cargarReportes === 'function') cargarReportes(); }
      catch (e) { alert(e.message); }
    });
    return div;
  }

  function dibujarPuntos(puntos) {
    if (!capaCriaderos) return;
    capaCriaderos.clearLayers();
    pinesReporte = {};
    puntos.forEach((p) => {
      const m = L.marker([p.lat, p.lng], {
        icon: window.CaszaGeo.iconoPin(COLOR_ESTADO[p.estado], p.icono),
        title: `${p.titulo} (${TXT_ESTADO[p.estado] || p.estado})`,
        zIndexOffset: 2000, riseOnHover: true,
      }).addTo(capaCriaderos);
      m.bindPopup(() => popupReporte(p, m), { minWidth: 220, maxWidth: 260 });
      m.on('dragend', async () => {
        m.dragging.disable();
        m.getElement()?.classList.remove('moviendo');
        const ll = m.getLatLng();
        if (confirm('¿Guardar la nueva ubicación de este criadero?')) {
          try { await guardarUbicacion(p.id, { lat: ll.lat, lng: ll.lng }); avisoMapa('✅ Ubicación actualizada.'); }
          catch (e) { alert(e.message); }
        }
        // Se redibujan los pines (Leaflet ignora el primer toque tras arrastrar un pin)
        await cargarPuntos();
      });
      pinesReporte[p.id] = m;
    });
    const cuenta = document.querySelector('[data-js-cuenta-gps]');
    if (cuenta) cuenta.textContent = puntos.length;
    if (verPendiente && pinesReporte[verPendiente]) { abrirPin(verPendiente); verPendiente = null; }
  }

  async function cargarPuntos() {
    if (!ES_ADMIN || !mapa || typeof api !== 'function') return;
    try { dibujarPuntos(await api('reportes-mapa')); } catch (e) { /* sin sesión o sin conexión: no se muestran pines */ }
  }

  function programarRecarga() {
    if (!ES_ADMIN || !mapa) return;
    clearTimeout(recargaProgramada);
    recargaProgramada = setTimeout(() => { cargarPuntos(); cargarCalor(); }, 300);
  }

  function abrirPin(id) {
    const m = pinesReporte[id];
    if (!m) return false;
    mapa.setView(m.getLatLng(), 18, { animate: false });
    setTimeout(() => m.openPopup(), 50);   // el popup se acomoda solo dentro del mapa
    return true;
  }

  let avisoTimer = null;
  function avisoMapa(texto) {
    const cont = document.querySelector('[data-js-mapa-real]');
    let el = cont.querySelector('.mr-aviso');
    if (!el) { el = document.createElement('div'); el.className = 'mr-aviso'; cont.appendChild(el); }
    el.textContent = texto;
    el.hidden = false;
    clearTimeout(avisoTimer);
    avisoTimer = setTimeout(() => { el.hidden = true; }, 4000);
  }

  window.CaszaMapaReal = {
    verReporte(id) {
      mostrarVista('real');
      document.querySelector('#mapa')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      setTimeout(() => { if (!abrirPin(id)) { verPendiente = id; cargarPuntos(); } }, 120);
    },
  };

  // Cada vez que el plano se redibuja (datos nuevos, clima, filtros, selección), se actualiza el mapa real
  if (typeof renderMapa === 'function') {
    const renderOriginal = renderMapa;
    renderMapa = function (riesgo) {   // eslint-disable-line no-global-assign
      renderOriginal(riesgo);
      dibujarBarrios();
      programarRecarga();   // datos nuevos → pines del administrador al día
    };
  }

  function mostrarVista(vista) {
    vistaActual = vista;
    const plano = document.querySelector('#mapa .mapa-wrapper');
    const real = document.querySelector('#mapa .mapa-real');
    const controlesPlano = document.querySelector('#mapa .mapa-controles');
    document.querySelectorAll('#mapa [data-vista]').forEach((b) => {
      const activo = b.dataset.vista === vista;
      b.classList.toggle('activo', activo);
      b.setAttribute('aria-pressed', String(activo));
    });
    if (plano) plano.hidden = vista !== 'plano';
    if (real) real.hidden = vista !== 'real';
    if (controlesPlano) controlesPlano.hidden = vista !== 'plano';
    document.querySelector('#mapa .mapa-nota-plano')?.toggleAttribute('hidden', vista !== 'plano');
    document.querySelector('#mapa .mapa-nota-real')?.toggleAttribute('hidden', vista !== 'real');

    if (vista === 'real') {
      crearMapa();
      setTimeout(() => {
        if (!mapa) return;
        mapa.invalidateSize();
        const selId = S() && S().filtros.barrio;
        if (selId && marcadores[selId]) mapa.setView(marcadores[selId].getLatLng(), 16);
      }, 50);
    } else if (S()) {
      // El plano estuvo oculto: recalcular su encuadre
      const st = S();
      const b = st.filtros.barrio && st.barrios.find((x) => x.id == st.filtros.barrio);
      setTimeout(() => { if (b && typeof zoomEnBarrio === 'function') zoomEnBarrio(b); else if (typeof resetZoom === 'function') resetZoom(); }, 30);
    }
  }

  document.querySelectorAll('#mapa [data-vista]').forEach((b) =>
    b.addEventListener('click', () => mostrarVista(b.dataset.vista)));

  // Al tocar un barrio en la lista lateral con el mapa real abierto, centrarlo también ahí
  document.querySelector('[data-js-barra-barrios]')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-barrio]');
    if (!btn || vistaActual !== 'real' || !mapa) return;
    if (btn.dataset.barrio === 'todos') { encuadrarBarrios(); return; }
    const m = marcadores[btn.dataset.barrio];
    if (m) mapa.flyTo(m.getLatLng(), 16, { duration: 0.6 });
  });
})();
