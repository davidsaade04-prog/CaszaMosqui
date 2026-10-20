/* ============================================================
   CaszaMosqui — Utilidades geográficas compartidas
   Convierte posiciones del plano municipal (x, y en %) a coordenadas reales.
   La transformación se calculó con 7 esquinas de El Colorado ubicadas con la
   API oficial Georef (apis.datos.gob.ar) y buscadas en el plano: error ≈ 7–60 m.
   ============================================================ */
(function () {
  // Plano recortado: 2400 × 2675 px (viewBox 30 240 576 642 a 300 dpi) → lat/lon
  const PLANO_W = 2400, PLANO_H = 2675;
  const AF_LAT = [-1.1498059675629435e-07, -1.337972814725863e-05, -26.296628714163564];
  const AF_LON = [1.4575534747793875e-05, 1.9014188133714693e-07, -59.383492970162585];

  function planoALatLng(xPct, yPct) {
    const px = (Number(xPct) / 100) * PLANO_W, py = (Number(yPct) / 100) * PLANO_H;
    return [AF_LAT[0] * px + AF_LAT[1] * py + AF_LAT[2], AF_LON[0] * px + AF_LON[1] * py + AF_LON[2]];
  }

  const ESQ_NO = planoALatLng(0, 0), ESQ_SE = planoALatLng(100, 100);
  // [[sur, oeste], [norte, este]] del plano municipal
  const LIMITES_PLANO = [[ESQ_SE[0], ESQ_NO[1]], [ESQ_NO[0], ESQ_SE[1]]];
  // Zona aceptada para un reporte (la misma que valida el servidor en api/index.php)
  const ZONA = { latMin: -26.37, latMax: -26.26, lngMin: -59.43, lngMax: -59.30 };

  function dentroDeZona(lat, lng) {
    return lat >= ZONA.latMin && lat <= ZONA.latMax && lng >= ZONA.lngMin && lng <= ZONA.lngMax;
  }

  // Distancia aproximada en metros (suficiente para distancias de un pueblo)
  function distancia(a, b) {
    const kLat = 111320, kLng = 111320 * Math.cos((a[0] * Math.PI) / 180);
    return Math.hypot((a[0] - b[0]) * kLat, (a[1] - b[1]) * kLng);
  }

  /** Barrio cuyo centro está más cerca del punto (sugerencia: el vecino puede cambiarlo). */
  function barrioMasCercano(lat, lng, barrios) {
    let mejor = null, dMin = Infinity;
    (barrios || []).forEach((b) => {
      if (b.x == null || b.y == null) return;
      const d = distancia([lat, lng], planoALatLng(b.x, b.y));
      if (d < dMin) { dMin = d; mejor = b; }
    });
    return mejor ? { barrio: mejor, metros: Math.round(dMin) } : null;
  }

  /** Pin propio (no depende de las imágenes de Leaflet). color: CSS */
  function iconoPin(color, contenido) {
    if (!window.L) return null;
    return L.divIcon({
      className: 'casza-pin-icono',
      html: `<span class="casza-pin" style="--pin:${color || '#dc2626'}"><span>${contenido || ''}</span></span>`,
      iconSize: [30, 40], iconAnchor: [15, 38], popupAnchor: [0, -34],
    });
  }

  window.CaszaGeo = { iconoPin, planoALatLng, LIMITES_PLANO, ZONA, dentroDeZona, distancia, barrioMasCercano };
})();
