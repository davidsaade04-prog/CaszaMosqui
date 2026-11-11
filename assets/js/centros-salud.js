/* ============================================================
   CaszaMosqui — Centros de salud de El Colorado
   - Tarjetas con dirección, horario ("abierto ahora"), llamar y WhatsApp.
   - "Más cerca de mí": ordena por distancia con el GPS del celular.
   - Mapa con un pin por centro y "Cómo llegar": dibuja el recorrido desde donde
     estás (a pie o en auto) con las indicaciones paso a paso, y un botón para
     seguir la navegación en Google Maps.
   Los datos vienen de inc/centros-salud.php (window.CENTROS_SALUD), así la página
   los tiene aunque no haya internet. La ruta usa el servidor público OSRM de
   FOSSGIS (routing.openstreetmap.de) con datos de OpenStreetMap; si no responde,
   se muestra la línea recta y la distancia.
   ============================================================ */
(function () {
  const ZONA_HORARIA = 'America/Argentina/Buenos_Aires';
  const DIAS = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
  const RUTEO = {
    pie:  'https://routing.openstreetmap.de/routed-foot/route/v1/driving/',
    auto: 'https://routing.openstreetmap.de/routed-car/route/v1/driving/',
  };

  /* ---------------- Horarios (funciones puras, con pruebas en tests/) ---------------- */

  /** Día (1 = lunes … 7 = domingo) y minutos del día en la hora de Argentina. */
  function ahoraLocal(fecha) {
    const partes = new Intl.DateTimeFormat('en-US', {
      timeZone: ZONA_HORARIA, weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(fecha || new Date());
    const v = (t) => partes.find((p) => p.type === t).value;
    const dia = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].indexOf(v('weekday')) + 1;
    return { dia, min: Number(v('hour')) * 60 + Number(v('minute')) };
  }
  const aMin = (hhmm) => { const [h, m] = hhmm.split(':').map(Number); return h * 60 + m; };
  const hora = (hhmm) => hhmm.replace(/^0/, '').replace(/:00$/, '') + ' h';

  /**
   * Estado del horario: { estado: 'abierto' | 'cerrado' | 'sin-dato', texto }.
   * horario: '24h' | [{dias, desde, hasta}] | null
   */
  function estadoHorario(horario, fecha) {
    if (horario === '24h') return { estado: 'abierto', texto: 'Abierto las 24 h' };
    if (!Array.isArray(horario) || !horario.length) return { estado: 'sin-dato', texto: 'Horario a confirmar' };
    const { dia, min } = ahoraLocal(fecha);
    const abierta = horario.find((f) => f.dias.includes(dia) && min >= aMin(f.desde) && min < aMin(f.hasta));
    if (abierta) return { estado: 'abierto', texto: `Abierto ahora · cierra a las ${hora(abierta.hasta)}` };
    // Próxima apertura (hoy más tarde o los próximos 7 días)
    for (let k = 0; k <= 7; k++) {
      const d = ((dia - 1 + k) % 7) + 1;
      const franjas = horario.filter((f) => f.dias.includes(d) && (k > 0 || aMin(f.desde) > min))
        .sort((a, b) => aMin(a.desde) - aMin(b.desde));
      if (franjas.length) {
        const cuando = k === 0 ? 'hoy' : k === 1 ? 'mañana' : `el ${DIAS[d]}`;
        return { estado: 'cerrado', texto: `Cerrado · abre ${cuando} a las ${hora(franjas[0].desde)}` };
      }
    }
    return { estado: 'cerrado', texto: 'Cerrado' };
  }

  /* ---------------- Indicaciones de la ruta en castellano ---------------- */
  const GIRO = {
    left: 'Doblá a la izquierda', right: 'Doblá a la derecha',
    'slight left': 'Tomá levemente a la izquierda', 'slight right': 'Tomá levemente a la derecha',
    'sharp left': 'Doblá bien cerrado a la izquierda', 'sharp right': 'Doblá bien cerrado a la derecha',
    straight: 'Seguí derecho', uturn: 'Pegá la vuelta',
  };
  const RUMBO = ['norte', 'noreste', 'este', 'sureste', 'sur', 'suroeste', 'oeste', 'noroeste'];

  function metros(m) {
    if (m >= 1000) return (Math.round(m / 100) / 10).toString().replace('.', ',') + ' km';
    return Math.max(10, Math.round(m / 10) * 10) + ' m';
  }
  function minutos(seg) {
    const m = Math.max(1, Math.round(seg / 60));
    return m < 60 ? `${m} min` : `${Math.floor(m / 60)} h ${m % 60} min`;
  }

  /** Pasos de OSRM → [{texto, distancia}] en castellano rioplatense. */
  function indicaciones(pasos, destino) {
    const lista = [];
    (pasos || []).forEach((p) => {
      const tipo = p.maneuver?.type, mod = p.maneuver?.modifier, calle = (p.name || '').trim();
      const por = calle ? ` por ${calle}` : '';
      let texto;
      if (tipo === 'depart') {
        const rumbo = RUMBO[Math.round(((p.maneuver.bearing_after || 0) % 360) / 45) % 8];
        texto = `Salí hacia el ${rumbo}${por}`;
      } else if (tipo === 'arrive') {
        texto = `Llegaste a ${destino || 'destino'}`;
      } else if (tipo === 'roundabout' || tipo === 'rotary' || tipo === 'roundabout turn') {
        texto = `En la rotonda, tomá la ${p.maneuver.exit ? p.maneuver.exit + 'ª ' : ''}salida${calle ? ' hacia ' + calle : ''}`;
      } else if (tipo === 'new name' || tipo === 'continue' || (mod === 'straight' && tipo !== 'end of road')) {
        if (!calle && lista.length) { lista[lista.length - 1].distancia += p.distance; return; }
        texto = `Seguí${por || ' derecho'}`;
      } else if (tipo === 'merge') {
        texto = `Incorporate${calle ? ' a ' + calle : ''}`;
      } else {
        texto = (GIRO[mod] || 'Seguí') + por;
      }
      // Sumar tramos seguidos por la misma calle
      const ant = lista[lista.length - 1];
      if (ant && ant.texto === texto && tipo !== 'arrive') { ant.distancia += p.distance; return; }
      lista.push({ texto, distancia: p.distance || 0, llegada: tipo === 'arrive' });
    });
    return lista;
  }

  function enlaceGoogle(c, modo) {
    const destino = c.lat != null ? `${c.lat},${c.lng}` : `${c.direccion}, El Colorado, Formosa`;
    return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destino)
      + '&travelmode=' + (modo === 'auto' ? 'driving' : 'walking');
  }

  window.CaszaSalud = { estadoHorario, indicaciones, metros, minutos, enlaceGoogle };

  /* ======================= Página ======================= */
  const cont = typeof document !== 'undefined' && document.querySelector('[data-js-centros]');
  const CENTROS = (typeof window !== 'undefined' && window.CENTROS_SALUD) || [];
  if (!cont || !CENTROS.length) return;

  const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const panelRuta = document.querySelector('[data-js-ruta]');
  const btnCerca = document.querySelector('[data-js-mas-cerca]');
  const avisoCerca = document.querySelector('[data-js-cerca-aviso]');
  let miPosicion = null, distancias = {}, modo = 'pie', destinoActual = null, pedido = 0;
  let mapa = null, capaCentros = null, capaRuta = null, pines = {};

  /* ---------- Tarjetas ---------- */
  function tarjeta(c) {
    const h = estadoHorario(c.horario);
    const tels = (c.telefonos || []).map(([etq, ver, marcar, wa]) => `
      <a class="salud-btn salud-llamar" href="tel:${esc(marcar)}" aria-label="Llamar a ${esc(c.nombre)} (${esc(etq)})">📞 ${esc(ver)}</a>
      ${wa ? `<a class="salud-btn salud-wa" href="https://wa.me/${esc(marcar.replace(/\D/g, ''))}" target="_blank" rel="noopener">💬 WhatsApp</a>` : ''}`).join('');
    const dist = distancias[c.id] != null ? `<span class="salud-dist">📍 a ${metros(distancias[c.id])}</span>` : '';
    return `
      <article class="salud-card${c.guardia ? ' guardia' : ''}" data-centro="${esc(c.id)}">
        <header>
          <h3>${c.guardia ? '🏥' : '🩺'} ${esc(c.nombre)}</h3>
          <span class="salud-tipo">${esc(c.tipo)}</span>
        </header>
        <span class="salud-estado ${h.estado}">${esc(h.texto)}</span>
        <p class="salud-dato">📍 ${esc(c.direccion)}${c.barrio ? ` · B° ${esc(c.barrio)}` : ''} ${dist}</p>
        <p class="salud-dato">🕒 ${esc(c.horario_txt)}</p>
        ${c.nota ? `<p class="salud-nota">${esc(c.nota)}</p>` : ''}
        <div class="salud-acciones">
          ${tels || '<span class="salud-sin-tel">Teléfono a confirmar</span>'}
          <button type="button" class="salud-btn salud-ir" data-ir="${esc(c.id)}">🧭 Cómo llegar</button>
          ${c.lat != null ? `<button type="button" class="salud-btn" data-ver="${esc(c.id)}">🗺️ Ver en el mapa</button>` : ''}
        </div>
      </article>`;
  }

  function render() {
    const lista = [...CENTROS];
    if (miPosicion) lista.sort((a, b) => (distancias[a.id] ?? 1e9) - (distancias[b.id] ?? 1e9));
    cont.innerHTML = lista.map(tarjeta).join('');
  }

  /* ---------- Ubicación del vecino ---------- */
  function pedirUbicacion() {
    return new Promise((ok, mal) => {
      if (!navigator.geolocation) return mal(new Error('Tu navegador no permite usar la ubicación.'));
      navigator.geolocation.getCurrentPosition(
        (p) => ok([p.coords.latitude, p.coords.longitude]),
        (e) => mal(new Error(e.code === 1
          ? 'No diste permiso para usar tu ubicación. Activalo en el candado de la barra del navegador.'
          : 'No pudimos saber dónde estás. Probá al aire libre o con el GPS encendido.')),
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
    });
  }

  function calcularDistancias() {
    distancias = {};
    CENTROS.forEach((c) => { if (c.lat != null) distancias[c.id] = window.CaszaGeo.distancia(miPosicion, [c.lat, c.lng]); });
  }

  btnCerca?.addEventListener('click', async () => {
    btnCerca.disabled = true;
    avisoCerca.textContent = 'Buscando tu ubicación…';
    try {
      miPosicion = await pedirUbicacion();
      calcularDistancias();
      render();
      const lejos = Object.values(distancias).every((d) => d > 30000);
      avisoCerca.textContent = lejos
        ? 'Parece que estás lejos de El Colorado: las distancias son aproximadas.'
        : 'Ordenados del más cercano al más lejano (los que no tienen pin en el mapa van al final).';
      if (mapa) dibujarYo();
    } catch (e) { avisoCerca.textContent = e.message; }
    btnCerca.disabled = false;
  });

  /* ---------- Mapa ---------- */
  function crearMapa() {
    if (mapa || !window.L) return;
    const el = document.querySelector('[data-js-salud-mapa]');
    el.hidden = false;
    mapa = L.map(el, { minZoom: 12, maxZoom: 19 });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(mapa);
    mapa.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>');
    capaRuta = L.layerGroup().addTo(mapa);
    capaCentros = L.layerGroup().addTo(mapa);
    const conPin = CENTROS.filter((c) => c.lat != null);
    conPin.forEach((c) => {
      const m = L.marker([c.lat, c.lng], {
        icon: window.CaszaGeo.iconoPin(c.guardia ? '#dc2626' : '#0284c7', c.guardia ? '🏥' : '🩺'),
        title: c.nombre, riseOnHover: true,
      }).addTo(capaCentros);
      m.bindPopup(() => {
        const h = estadoHorario(c.horario);
        const div = document.createElement('div');
        div.className = 'mr-popup';
        div.innerHTML = `<strong>${esc(c.nombre)}</strong>
          <span class="salud-estado ${h.estado}">${esc(h.texto)}</span>
          <small>📍 ${esc(c.direccion)}</small>
          <div class="mr-popup-acciones">
            ${c.telefonos[0] ? `<a class="salud-btn salud-llamar" href="tel:${esc(c.telefonos[0][2])}">📞 Llamar</a>` : ''}
            <button type="button" class="salud-btn salud-ir" data-ir="${esc(c.id)}">🧭 Cómo llegar</button>
          </div>`;
        div.querySelector('[data-ir]').addEventListener('click', () => { m.closePopup(); comoLlegar(c.id); });
        return div;
      }, { minWidth: 210 });
      pines[c.id] = m;
    });
    mapa.fitBounds(L.latLngBounds(conPin.map((c) => [c.lat, c.lng])).pad(0.6), { maxZoom: 16 });
    if (miPosicion) dibujarYo();
  }

  let pinYo = null;
  function dibujarYo() {
    if (!mapa || !miPosicion) return;
    if (pinYo) pinYo.remove();
    pinYo = L.circleMarker(miPosicion, { radius: 8, color: '#fff', weight: 3, fillColor: '#2563eb', fillOpacity: 1 })
      .bindTooltip('Vos estás acá').addTo(mapa);
  }

  function verEnMapa(id) {
    crearMapa();
    const c = CENTROS.find((x) => x.id === id);
    document.querySelector('[data-js-salud-mapa]').scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => { mapa.invalidateSize(); mapa.setView([c.lat, c.lng], 17); pines[id]?.openPopup(); }, 250);
  }

  // El mapa se crea cuando la sección aparece en pantalla (no gasta datos si nadie baja hasta acá)
  if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver((e) => { if (e.some((x) => x.isIntersecting)) { crearMapa(); obs.disconnect(); } }, { rootMargin: '200px' });
    obs.observe(document.querySelector('[data-js-salud-mapa]'));
  }

  /* ---------- Cómo llegar ---------- */
  async function comoLlegar(id) {
    const c = CENTROS.find((x) => x.id === id);
    destinoActual = c;
    const n = ++pedido;
    panelRuta.hidden = false;
    panelRuta.innerHTML = `
      <div class="ruta-cabecera">
        <strong>🧭 Cómo llegar a ${esc(c.nombre)}</strong>
        <button type="button" class="ruta-cerrar" data-ruta-cerrar aria-label="Cerrar">✕</button>
      </div>
      <div class="ruta-modos" role="group" aria-label="Cómo vas">
        <button type="button" data-modo="pie" aria-pressed="${modo === 'pie'}">🚶 A pie</button>
        <button type="button" data-modo="auto" aria-pressed="${modo === 'auto'}">🚗 En auto / moto</button>
      </div>
      <p class="ruta-estado" data-ruta-estado>Buscando tu ubicación…</p>
      <ol class="ruta-pasos" data-ruta-pasos></ol>
      <a class="salud-btn salud-google" href="${esc(enlaceGoogle(c, modo))}" target="_blank" rel="noopener">📱 Seguir con Google Maps (navegación por voz)</a>`;
    panelRuta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    const estado = panelRuta.querySelector('[data-ruta-estado]');

    if (c.lat == null) {
      estado.innerHTML = `Todavía no tenemos el punto exacto de este lugar en nuestro mapa. Tocá <strong>Seguir con Google Maps</strong>: te lleva a <strong>${esc(c.direccion)}</strong>.`;
      return;
    }
    crearMapa();
    try {
      if (!miPosicion) { miPosicion = await pedirUbicacion(); calcularDistancias(); render(); }
    } catch (e) {
      if (n !== pedido) return;
      estado.innerHTML = `${esc(e.message)} Igual podés abrir la ruta en Google Maps.`;
      verEnMapa(c.id);
      return;
    }
    if (n !== pedido) return;
    dibujarYo();
    const recta = window.CaszaGeo.distancia(miPosicion, [c.lat, c.lng]);
    if (recta > 30000) {
      estado.textContent = `Estás a ${metros(recta)} de El Colorado: para un viaje tan largo usá Google Maps.`;
      return;
    }
    estado.textContent = 'Calculando el recorrido…';
    capaRuta.clearLayers();
    try {
      const url = `${RUTEO[modo]}${miPosicion[1]},${miPosicion[0]};${c.lng},${c.lat}?overview=full&geometries=geojson&steps=true`;
      const ctrl = new AbortController();
      const corte = setTimeout(() => ctrl.abort(), 12000);
      const res = await fetch(url, { signal: ctrl.signal });
      clearTimeout(corte);
      const datos = await res.json();
      if (n !== pedido) return;
      if (!res.ok || datos.code !== 'Ok' || !datos.routes?.length) throw new Error('sin ruta');
      const r = datos.routes[0];
      const linea = L.polyline(r.geometry.coordinates.map(([lng, lat]) => [lat, lng]),
        { color: '#2563eb', weight: 6, opacity: 0.85 }).addTo(capaRuta);
      mapa.fitBounds(linea.getBounds().pad(0.2));
      estado.innerHTML = `<strong>${metros(r.distance)}</strong> · unos <strong>${minutos(r.duration)}</strong> ${modo === 'pie' ? 'caminando' : 'en auto'}`;
      const pasos = indicaciones(r.legs[0].steps, c.nombre);
      panelRuta.querySelector('[data-ruta-pasos]').innerHTML = pasos.map((p) =>
        `<li${p.llegada ? ' class="llegada"' : ''}><span>${esc(p.texto)}</span>${!p.llegada && p.distancia ? `<small>${metros(p.distancia)}</small>` : ''}</li>`).join('');
      mapa.attributionControl.addAttribution('Ruta: <a href="https://routing.openstreetmap.de" target="_blank" rel="noopener">OSRM · FOSSGIS</a>');
    } catch (e) {
      if (n !== pedido) return;
      // Sin conexión o el servidor de rutas no respondió: línea recta y distancia
      const linea = L.polyline([miPosicion, [c.lat, c.lng]], { color: '#2563eb', weight: 4, dashArray: '8 8' }).addTo(capaRuta);
      mapa.fitBounds(linea.getBounds().pad(0.3));
      estado.innerHTML = `Estás a <strong>${metros(recta)}</strong> en línea recta. ${navigator.onLine ? 'No pudimos calcular el recorrido por las calles' : 'Sin internet no podemos calcular el recorrido'}: usá Google Maps para que te guíe.`;
    }
    document.querySelector('[data-js-salud-mapa]').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  panelRuta?.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-ruta-cerrar]')) {
      pedido++; panelRuta.hidden = true; capaRuta?.clearLayers(); return;
    }
    const b = ev.target.closest('[data-modo]');
    if (b && destinoActual && b.dataset.modo !== modo) { modo = b.dataset.modo; comoLlegar(destinoActual.id); }
  });

  cont.addEventListener('click', (ev) => {
    const ir = ev.target.closest('[data-ir]');
    if (ir) { comoLlegar(ir.dataset.ir); return; }
    const ver = ev.target.closest('[data-ver]');
    if (ver) verEnMapa(ver.dataset.ver);
  });

  render();
  // "Abierto ahora" se actualiza solo cada minuto
  setInterval(render, 60000);
  window.CaszaSalud.comoLlegar = comoLlegar;
})();
