/* ============================================================
   CaszaMosqui — Ubicación GPS y foto (opcionales) del formulario de reporte
   - "Usar mi ubicación": GPS del celular (el navegador pide permiso; requiere https).
   - "Marcar en el mapa": tocar el punto en un mapa chico. El pin se puede arrastrar.
   - Sugiere el barrio según el punto (el vecino lo puede cambiar).
   - Foto: se achica en el mismo celular (máx. 1280 px, ~150 KB) antes de subirla.
     Al redibujarla se eliminan los metadatos ocultos (GPS, modelo del teléfono).
   - Expone window.CaszaReporteExtra = { datos(), limpiar() } para app.js.
   Depende de assets/js/leaflet.js y assets/js/geo.js.
   ============================================================ */
(function () {
  const form = document.querySelector('[data-js-form]');
  if (!form) return;

  const $ = (sel) => form.querySelector(sel);
  const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const Geo = window.CaszaGeo;

  /* ---------------- Ubicación ---------------- */
  const btnGps = $('[data-js-gps]'), btnMarcar = $('[data-js-marcar]'), btnQuitarUbic = $('[data-js-quitar-ubic]');
  const estadoGps = $('[data-js-gps-estado]'), contMapa = $('[data-js-mini-mapa]');
  let mapa = null, pin = null, circulo = null, ubicacion = null;

  function aviso(el, texto, tipo) {
    el.textContent = texto || '';
    el.className = 'extra-estado' + (tipo ? ' ' + tipo : '');
  }

  function crearMapa(centro, zoom) {
    contMapa.hidden = false;
    if (!window.L) { aviso(estadoGps, 'No se pudo cargar el mapa. Podés reportar igual sin ubicación.', 'err'); return false; }
    if (!mapa) {
      mapa = L.map(contMapa, { minZoom: 13, maxZoom: 19, maxBounds: L.latLngBounds(Geo.LIMITES_PLANO).pad(1.5) });
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
      }).addTo(mapa);
      mapa.attributionControl.setPrefix(false);
      mapa.on('click', (ev) => fijarUbicacion(ev.latlng.lat, ev.latlng.lng, null, 'mapa'));
    }
    setTimeout(() => { mapa.invalidateSize(); mapa.setView(centro, zoom); }, 30);
    return true;
  }

  function centroInicial() {
    const sel = form.querySelector('select[name=barrio_id]');
    const barrios = (typeof state !== 'undefined' && state.barrios) || [];
    const b = sel && barrios.find((x) => String(x.id) === sel.value);
    if (b) return { centro: Geo.planoALatLng(b.x, b.y), zoom: 17 };
    const [[s, o], [n, e]] = Geo.LIMITES_PLANO;
    return { centro: [(s + n) / 2, (o + e) / 2], zoom: 15 };
  }

  function sugerirBarrio(lat, lng) {
    const barrios = (typeof state !== 'undefined' && state.barrios) || [];
    const sug = Geo.barrioMasCercano(lat, lng, barrios);
    const sel = form.querySelector('select[name=barrio_id]');
    if (!sug || !sel) return '';
    if (!sel.value) {   // solo completa si el vecino todavía no eligió
      sel.value = String(sug.barrio.id);
      return ` Barrio sugerido: <strong>${esc(sug.barrio.nombre)}</strong> (podés cambiarlo arriba).`;
    }
    if (sel.value !== String(sug.barrio.id)) {
      return ` ¿Es en <strong>${esc(sug.barrio.nombre)}</strong>? Revisá el barrio elegido.`;
    }
    return '';
  }

  function fijarUbicacion(lat, lng, precision, origen) {
    if (!Geo.dentroDeZona(lat, lng)) {
      aviso(estadoGps, '⚠️ Ese punto está fuera de El Colorado. Marcá el lugar del criadero en el mapa.', 'err');
      return;
    }
    ubicacion = { lat: Number(lat.toFixed(6)), lng: Number(lng.toFixed(6)) };
    if (!mapa) crearMapa([lat, lng], 17);
    if (!pin) {
      pin = L.marker([lat, lng], { icon: Geo.iconoPin('#dc2626', '🦟'), draggable: true, autoPan: true, title: 'Arrastrá para ajustar' }).addTo(mapa);
      pin.on('dragend', () => { const p = pin.getLatLng(); fijarUbicacion(p.lat, p.lng, null, 'ajuste'); });
    } else {
      pin.setLatLng([lat, lng]);
    }
    if (circulo) { circulo.remove(); circulo = null; }
    if (precision) {
      circulo = L.circle([lat, lng], { radius: precision, color: '#0e7490', weight: 1, fillOpacity: 0.12, interactive: false }).addTo(mapa);
    }
    if (origen === 'gps') mapa.setView([lat, lng], 18);
    btnQuitarUbic.hidden = false;
    const extra = sugerirBarrio(lat, lng);
    const txt = origen === 'gps'
      ? `✅ Ubicación tomada${precision ? ` (precisión ±${Math.round(precision)} m)` : ''}. Si no es exacta, arrastrá el pin.`
      : '✅ Punto marcado. Podés arrastrar el pin para ajustarlo.';
    estadoGps.innerHTML = txt + extra;
    estadoGps.className = 'extra-estado ok';
  }

  function quitarUbicacion() {
    ubicacion = null;
    if (pin) { pin.remove(); pin = null; }
    if (circulo) { circulo.remove(); circulo = null; }
    contMapa.hidden = true;
    btnQuitarUbic.hidden = true;
    aviso(estadoGps, '');
  }

  btnGps.addEventListener('click', () => {
    if (!('geolocation' in navigator) || !window.isSecureContext) {
      aviso(estadoGps, 'Tu navegador no permite usar el GPS en esta página. Marcá el punto en el mapa.', 'err');
      return;
    }
    btnGps.disabled = true;
    aviso(estadoGps, '⏳ Buscando tu ubicación… (aceptá el permiso si el celular lo pide)');
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        btnGps.disabled = false;
        const { latitude, longitude, accuracy } = pos.coords;
        if (!Geo.dentroDeZona(latitude, longitude)) {
          aviso(estadoGps, '⚠️ Tu ubicación parece estar fuera de El Colorado. Marcá el lugar del criadero en el mapa.', 'err');
          const c = centroInicial(); crearMapa(c.centro, c.zoom);
          return;
        }
        crearMapa([latitude, longitude], 18);
        fijarUbicacion(latitude, longitude, accuracy, 'gps');
      },
      (err) => {
        btnGps.disabled = false;
        const motivos = {
          1: 'No diste permiso para usar la ubicación. Podés habilitarlo en el navegador o marcar el punto en el mapa.',
          2: 'No se pudo obtener la ubicación (¿GPS apagado?). Probá de nuevo o marcá el punto en el mapa.',
          3: 'La ubicación tardó demasiado. Probá de nuevo al aire libre o marcá el punto en el mapa.',
        };
        aviso(estadoGps, '⚠️ ' + (motivos[err.code] || 'No se pudo obtener la ubicación.'), 'err');
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  });

  btnMarcar.addEventListener('click', () => {
    const c = ubicacion ? { centro: [ubicacion.lat, ubicacion.lng], zoom: 17 } : centroInicial();
    if (crearMapa(c.centro, c.zoom) && !ubicacion) {
      aviso(estadoGps, '👆 Tocá en el mapa el lugar exacto del criadero.');
      btnQuitarUbic.hidden = false;
    }
  });
  btnQuitarUbic.addEventListener('click', quitarUbicacion);

  /* ---------------- Foto ---------------- */
  const inCamara = $('[data-js-foto-input-camara]'), inGaleria = $('[data-js-foto-input-galeria]');
  const estadoFoto = $('[data-js-foto-estado]'), preview = $('[data-js-foto-preview]'), img = $('[data-js-foto-img]');
  const LADO_MAX = 1280, PESO_OBJETIVO = 350 * 1024;
  let foto = null;   // data URL lista para enviar

  $('[data-js-foto-camara]').addEventListener('click', () => inCamara.click());
  $('[data-js-foto-galeria]').addEventListener('click', () => inGaleria.click());

  async function cargarImagen(file) {
    if (window.createImageBitmap) {
      try { return await createImageBitmap(file, { imageOrientation: 'from-image' }); } catch (e) { /* sigue con <img> */ }
    }
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const im = new Image();
      im.onload = () => { URL.revokeObjectURL(url); resolve(im); };
      im.onerror = () => { URL.revokeObjectURL(url); reject(new Error('No se pudo leer la imagen.')); };
      im.src = url;
    });
  }

  const aBlob = (canvas, calidad) => new Promise((r) => canvas.toBlob(r, 'image/jpeg', calidad));
  const aDataUrl = (blob) => new Promise((r) => { const fr = new FileReader(); fr.onload = () => r(fr.result); fr.readAsDataURL(blob); });

  async function comprimir(file) {
    const fuente = await cargarImagen(file);
    let lado = LADO_MAX, calidad = 0.75, blob = null;
    for (let intento = 0; intento < 6; intento++) {
      const escala = Math.min(1, lado / Math.max(fuente.width, fuente.height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(fuente.width * escala);
      canvas.height = Math.round(fuente.height * escala);
      canvas.getContext('2d').drawImage(fuente, 0, 0, canvas.width, canvas.height);
      blob = await aBlob(canvas, calidad);
      if (blob && blob.size <= PESO_OBJETIVO) break;
      if (calidad > 0.55) calidad -= 0.1; else lado = Math.round(lado * 0.8);
    }
    if (fuente.close) fuente.close();
    if (!blob) throw new Error('No se pudo procesar la foto.');
    return { dataUrl: await aDataUrl(blob), kb: Math.round(blob.size / 1024) };
  }

  async function elegirFoto(ev) {
    const file = ev.target.files && ev.target.files[0];
    ev.target.value = '';   // permite volver a elegir la misma foto
    if (!file) return;
    if (!/^image\//.test(file.type)) { aviso(estadoFoto, '⚠️ El archivo elegido no es una imagen.', 'err'); return; }
    aviso(estadoFoto, '⏳ Preparando la foto…');
    try {
      const r = await comprimir(file);
      foto = r.dataUrl;
      img.src = foto;
      preview.hidden = false;
      aviso(estadoFoto, `✅ Foto lista (${r.kb} KB). Se envía junto con el reporte.`, 'ok');
    } catch (e) {
      foto = null;
      preview.hidden = true;
      aviso(estadoFoto, '⚠️ No se pudo usar esa foto. Probá con otra o reportá sin foto.', 'err');
    }
  }
  inCamara.addEventListener('change', elegirFoto);
  inGaleria.addEventListener('change', elegirFoto);

  function quitarFoto() {
    foto = null;
    img.removeAttribute('src');
    preview.hidden = true;
    aviso(estadoFoto, '');
  }
  $('[data-js-quitar-foto]').addEventListener('click', quitarFoto);

  /* ---------------- Para app.js ---------------- */
  window.CaszaReporteExtra = {
    datos() {
      const d = {};
      if (ubicacion) { d.lat = ubicacion.lat; d.lng = ubicacion.lng; }
      if (foto) d.foto = foto;
      return d;
    },
    limpiar() { quitarUbicacion(); quitarFoto(); },
  };
})();
