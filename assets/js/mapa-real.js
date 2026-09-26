/* ============================================================
   CaszaMosqui — Pestaña "Mapa real" (OpenStreetMap + Leaflet)
   - Convive con el plano municipal: mismos datos, mismo semáforo, mismos filtros.
   - Los barrios se ubican con una transformación calculada a partir del plano
     municipal y de esquinas geolocalizadas con la API oficial Georef
     (apis.datos.gob.ar). Precisión aproximada: ±50 m.
   - Se carga solo cuando se abre la pestaña (no gasta datos si nadie la usa).
   Depende de app.js (state, nivelBarrio, seleccionarBarrio, renderMapa…)
   y de assets/vendor/leaflet/leaflet.js.
   ============================================================ */
(function () {
  if (!document.querySelector('[data-js-mapa-real]')) return;

  // Plano recortado: 2400 × 2675 px (viewBox 30 240 576 642 a 300 dpi) → lat/lon
  const PLANO_W = 2400, PLANO_H = 2675;
  const AF_LAT = [-1.1498059675629435e-07, -1.337972814725863e-05, -26.296628714163564];
  const AF_LON = [1.4575534747793875e-05, 1.9014188133714693e-07, -59.383492970162585];
  function planoALatLng(xPct, yPct) {
    const px = (Number(xPct) / 100) * PLANO_W, py = (Number(yPct) / 100) * PLANO_H;
    return [AF_LAT[0] * px + AF_LAT[1] * py + AF_LAT[2], AF_LON[0] * px + AF_LON[1] * py + AF_LON[2]];
  }
  const ESQ_NO = planoALatLng(0, 0), ESQ_SE = planoALatLng(100, 100);
  const LIMITES_PLANO = [[ESQ_SE[0], ESQ_NO[1]], [ESQ_NO[0], ESQ_SE[1]]];   // [[sur, oeste], [norte, este]]

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
            <input type="range" min="20" max="90" value="60" data-js-plano-opacidad></label>`;
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
        return div;
      },
    });
    new Control().addTo(mapa);

    capaBarrios = L.layerGroup().addTo(mapa);
    mapa.fitBounds(LIMITES_PLANO, { padding: [10, 10] });
    ajustarDetalle();
    dibujarBarrios();
    encuadrarBarrios();
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

  // Cada vez que el plano se redibuja (datos nuevos, clima, filtros, selección), se actualiza el mapa real
  if (typeof renderMapa === 'function') {
    const renderOriginal = renderMapa;
    renderMapa = function (riesgo) {   // eslint-disable-line no-global-assign
      renderOriginal(riesgo);
      dibujarBarrios();
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
