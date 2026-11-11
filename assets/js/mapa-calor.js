/* ============================================================
   CaszaMosqui — Mapa de calor del panel de administración
   Muestra dónde se concentran los criaderos activos (sin controlar + verificados).
   - Reportes con GPS: en su punto exacto.
   - Reportes sin GPS: en el centro de su barrio (repartidos en un círculo chico
     para que se note la cantidad).
   Dibujado en un <canvas> propio (sin librerías externas), sobre Leaflet.
   Uso (lo hace mapa-real.js): const capa = CaszaCalor.crear(); capa.addTo(mapa); capa.setPuntos([...]);
   ============================================================ */
(function () {
  if (!window.L) return;

  const RADIO_METROS = 170;        // alcance de cada criadero (≈ dos manzanas)
  const PESO = { pendiente: 1, verificado: 1.3 };   // un verificado ya está confirmado en el lugar
  const MAX_INTENSIDAD = 2.4;      // con 3–4 criaderos superpuestos el color llega al rojo
  const OPACIDAD = 0.72;

  // Paleta: transparente → verde → amarillo → naranja → rojo
  const paleta = (() => {
    const c = document.createElement('canvas');
    c.width = 256; c.height = 1;
    const g = c.getContext('2d');
    const grad = g.createLinearGradient(0, 0, 256, 0);
    grad.addColorStop(0.00, 'rgba(22,163,74,0)');
    grad.addColorStop(0.20, 'rgba(22,163,74,0.75)');
    grad.addColorStop(0.45, 'rgba(234,179,8,0.9)');
    grad.addColorStop(0.70, 'rgba(249,115,22,1)');
    grad.addColorStop(1.00, 'rgba(220,38,38,1)');
    g.fillStyle = grad;
    g.fillRect(0, 0, 256, 1);
    return g.getImageData(0, 0, 256, 1).data;
  })();

  const CapaCalor = L.Layer.extend({
    initialize() { this._puntos = []; },

    setPuntos(puntos) { this._puntos = puntos || []; this._dibujar(); return this; },

    onAdd(mapa) {
      this._mapa = mapa;
      this._canvas = L.DomUtil.create('canvas', 'mapa-calor-canvas leaflet-zoom-hide');
      mapa.getPanes().overlayPane.appendChild(this._canvas);
      mapa.on('moveend zoomend resize', this._dibujar, this);
      this._dibujar();
    },

    onRemove(mapa) {
      L.DomUtil.remove(this._canvas);
      mapa.off('moveend zoomend resize', this._dibujar, this);
    },

    _dibujar() {
      if (!this._mapa || !this._canvas) return;
      const mapa = this._mapa, canvas = this._canvas;
      const tam = mapa.getSize();
      const esquina = mapa.containerPointToLayerPoint([0, 0]);
      L.DomUtil.setPosition(canvas, esquina);
      canvas.width = tam.x; canvas.height = tam.y;
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, tam.x, tam.y);
      if (!this._puntos.length) return;

      // Radio en píxeles según el zoom actual
      const centro = mapa.getCenter();
      const p1 = mapa.latLngToContainerPoint(centro);
      const p2 = mapa.latLngToContainerPoint([centro.lat + RADIO_METROS / 111320, centro.lng]);
      const r = Math.max(24, Math.abs(p1.y - p2.y));

      // 1) Intensidad en escala de grises (alfa acumulada)
      const sello = document.createElement('canvas');
      sello.width = sello.height = r * 2;
      const sg = sello.getContext('2d');
      const grad = sg.createRadialGradient(r, r, 0, r, r, r);
      grad.addColorStop(0, 'rgba(0,0,0,1)');
      grad.addColorStop(1, 'rgba(0,0,0,0)');
      sg.fillStyle = grad;
      sg.fillRect(0, 0, r * 2, r * 2);

      this._puntos.forEach((pt) => {
        const p = mapa.latLngToContainerPoint([pt.lat, pt.lng]);
        if (p.x < -r || p.y < -r || p.x > tam.x + r || p.y > tam.y + r) return;
        ctx.globalAlpha = Math.min(1, pt.peso / MAX_INTENSIDAD);
        ctx.drawImage(sello, p.x - r, p.y - r);
      });
      ctx.globalAlpha = 1;

      // 2) Colorear según la intensidad
      const img = ctx.getImageData(0, 0, tam.x, tam.y);
      const d = img.data;
      for (let i = 0; i < d.length; i += 4) {
        const a = d[i + 3];
        if (!a) continue;
        const j = a * 4;
        d[i] = paleta[j]; d[i + 1] = paleta[j + 1]; d[i + 2] = paleta[j + 2];
        d[i + 3] = paleta[j + 3] * OPACIDAD;
      }
      ctx.putImageData(img, 0, 0);
    },
  });

  /** Convierte los reportes de la API en puntos con peso. barrios: state.barrios (x, y del plano). */
  function prepararPuntos(reportes, barrios) {
    const porBarrio = {};
    const centroBarrio = {};
    (barrios || []).forEach((b) => {
      if (b.x != null && b.y != null) centroBarrio[b.id] = window.CaszaGeo.planoALatLng(b.x, b.y);
    });
    const puntos = [];
    reportes.forEach((r) => {
      const peso = PESO[r.estado] || 1;
      if (r.lat != null && r.lng != null) { puntos.push({ lat: r.lat, lng: r.lng, peso }); return; }
      (porBarrio[r.barrio_id] = porBarrio[r.barrio_id] || []).push(peso);
    });
    // Sin GPS: repartidos en un círculo de ~60 m alrededor del centro del barrio
    Object.entries(porBarrio).forEach(([id, pesos]) => {
      const c = centroBarrio[id];
      if (!c) return;
      pesos.forEach((peso, i) => {
        const ang = (i / pesos.length) * Math.PI * 2;
        const dist = pesos.length > 1 ? 60 : 0;
        puntos.push({
          lat: c[0] + (Math.sin(ang) * dist) / 111320,
          lng: c[1] + (Math.cos(ang) * dist) / (111320 * Math.cos((c[0] * Math.PI) / 180)),
          peso,
        });
      });
    });
    return puntos;
  }

  window.CaszaCalor = {
    crear: () => new CapaCalor(),
    prepararPuntos,
  };
})();
