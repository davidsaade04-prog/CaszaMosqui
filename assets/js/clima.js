/* ============================================================
   CaszaMosqui — Alerta climática
   Carga api/clima.php, dibuja el panel en #clima y expone:
   - window.CaszaClima  → { bonus, nivel, listo, datos }
   - window.riesgoConClima(activos) → { total, nivel }
   - evento 'clima:listo' en document (para redibujar el mapa)
   Se actualiza solo cada 10 minutos (y al volver a la pestaña).
   ============================================================ */
(function () {
  const cont = document.getElementById('clima');
  window.CaszaClima = { bonus: 0, nivel: 'bajo', listo: false, datos: null };

  // Riesgo final del barrio: el clima potencia los criaderos que YA existen.
  // Mismos umbrales del semáforo: 0-1 bajo · 2-3 medio · 4+ alto.
  window.riesgoConClima = function (activos) {
    const total = activos > 0 ? activos + window.CaszaClima.bonus : 0;
    const nivel = total >= 4 ? 'alto' : total >= 2 ? 'medio' : 'bajo';
    return { total, nivel };
  };

  const etiqueta = { alto: 'ALTO', medio: 'MEDIO', bajo: 'BAJO' };
  const REFRESCO_MS = 10 * 60 * 1000;
  let ultimaCarga = 0;

  function bloqueActual(a) {
    if (!a) return '';
    return `
      <div class="clima-ahora${a.lloviendo ? ' lloviendo' : ''}">
        <span class="clima-ahora-ico" aria-hidden="true">${a.icono}</span>
        <div class="clima-ahora-temp">
          <strong>${a.temperatura} °C</strong>
          <span>${a.descripcion}${a.lloviendo ? ` · ${a.lluvia} mm` : ''}</span>
        </div>
        <ul class="clima-ahora-extra">
          <li>🌡️ Sensación <b>${a.sensacion} °C</b></li>
          <li>💧 Humedad <b>${a.humedad}%</b></li>
          <li>🍃 Viento <b>${a.viento} km/h</b></li>
        </ul>
        <span class="clima-vivo"><i></i>En vivo · ${a.hora} h</span>
      </div>`;
  }

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
      ${bloqueActual(c.actual)}
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

  function cargar() {
    ultimaCarga = Date.now();
    fetch((window.BASE_URL ?? '.') + '/api/clima.php?t=' + ultimaCarga, { cache: 'no-store' })
      .then(r => r.json())
      .then(c => {
        Object.assign(window.CaszaClima, { bonus: c.bonus, nivel: c.nivel, listo: true, datos: c });
        render(c);
        document.dispatchEvent(new CustomEvent('clima:listo', { detail: c }));
      })
      .catch(() => {
        // Si falla una actualización se deja el último dato mostrado
        if (cont && !window.CaszaClima.listo) cont.innerHTML = '<p class="placeholder">No se pudo cargar el clima.</p>';
      });
  }

  cargar();
  setInterval(() => { if (!document.hidden) cargar(); }, REFRESCO_MS);
  // Al volver a la pestaña después de un rato, actualizar enseguida
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && Date.now() - ultimaCarga > REFRESCO_MS) cargar();
  });
})();
