/* ============================================================
   CaszaMosqui — Alerta climática
   Carga api/clima.php, dibuja el panel en #clima y expone:
   - window.CaszaClima  → { bonus, nivel, listo, datos }
   - window.riesgoConClima(activos) → { total, nivel }
   - evento 'clima:listo' en document (para redibujar el mapa)
   ============================================================ */
(function () {
  const cont = document.getElementById('clima');
  window.CaszaClima = { bonus: 0, nivel: 'bajo', listo: false, datos: null };

  // El clima se informa aparte: no modifica el semáforo de los barrios.
  // El color depende solo de la cantidad de criaderos sin controlar:
  // 0-2 bajo · 3-4 medio · 5 o más alto.
  window.riesgoConClima = function (activos) {
    const total = Math.max(0, Number(activos) || 0);
    const nivel = total >= 5 ? 'alto' : total >= 3 ? 'medio' : 'bajo';
    return { total, nivel };
  };

  const etiqueta = { alto: 'ALTO', medio: 'MEDIO', bajo: 'BAJO' };

  function render(c) {
    if (!cont) return;
    const max = Math.max(10, ...c.serie.map(d => d.lluvia));
    const barras = c.serie
      .map(d => `<i title="${d.fecha}: ${d.lluvia} mm" style="height:${Math.round((d.lluvia / max) * 100)}%"></i>`)
      .join('');

    cont.innerHTML = `
      <div class="panel-head">
        <h2>🌧️ Alerta climática</h2>
        <span class="clima-nivel ${c.nivel}">Riesgo climático ${etiqueta[c.nivel]}</span>
      </div>
      <p class="sub">${c.mensaje}</p>
      <div class="clima-datos">
        <div><strong>${c.lluvia_14d} mm</strong><span>lluvia últimos 14 días</span></div>
        <div><strong>${c.temp_media_7d} °C</strong><span>temperatura media 7 días</span></div>
        <div><strong>${c.lluvia_prevista_3d} mm</strong><span>lluvia prevista próximos días</span></div>
      </div>
      <div class="clima-barras" aria-label="Lluvia diaria de los últimos 14 días">${barras}</div>
      <p class="clima-eje">Lluvia diaria · últimos 14 días</p>
      <p class="clima-reco">👉 ${c.recomendacion}</p>
      <p class="mapa-nota">Fuente: ${c.fuente_texto} · ${c.ubicacion}</p>`;
  }

  fetch((window.BASE_URL || '.') + '/api/clima.php')
    .then(r => r.json())
    .then(c => {
      Object.assign(window.CaszaClima, { bonus: c.bonus, nivel: c.nivel, listo: true, datos: c });
      render(c);
      document.dispatchEvent(new CustomEvent('clima:listo', { detail: c }));
    })
    .catch(() => {
      if (cont) cont.innerHTML = '<p class="placeholder">No se pudo cargar el clima.</p>';
    });
})();
