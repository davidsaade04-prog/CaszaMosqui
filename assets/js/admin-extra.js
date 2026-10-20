/* ============================================================
   CaszaMosqui — Herramientas del panel de administración
   - Visor de fotos de los reportes (con opción de eliminar la foto).
   - Respaldo: descargar todos los datos y restaurarlos en otra base
     (para mudar la base gratuita de Render cuando vence).
   Depende de app.js (api, cargarReportes, cargarStats).
   ============================================================ */
(function () {
  const BASE = window.BASE_URL ?? '';
  const urlFoto = (id) => `${BASE}/api/index.php?route=reportes/${id}/foto&t=${Date.now()}`;

  /* ---------------- Visor de fotos ---------------- */
  const visor = document.createElement('dialog');
  visor.className = 'foto-visor';
  visor.innerHTML = `
    <form method="dialog" class="login-modal-cerrar-form"><button class="login-modal-cerrar" aria-label="Cerrar">✕</button></form>
    <img alt="Foto del criadero enviada por un vecino" data-js-visor-img>
    <div class="foto-visor-pie">
      <span data-js-visor-txt></span>
      <button type="button" class="btn-quitar" data-js-visor-borrar>🗑️ Eliminar foto</button>
    </div>`;
  document.body.appendChild(visor);
  const img = visor.querySelector('[data-js-visor-img]');
  const txt = visor.querySelector('[data-js-visor-txt]');
  let idActual = null;

  visor.addEventListener('click', (ev) => { if (ev.target === visor) visor.close(); });
  img.addEventListener('error', () => { txt.textContent = 'No se pudo cargar la foto.'; });
  visor.querySelector('[data-js-visor-borrar]').addEventListener('click', async () => {
    if (!idActual || !confirm('¿Eliminar esta foto? El reporte se mantiene.')) return;
    try {
      await api(`reportes/${idActual}/foto`, { method: 'DELETE' });
      visor.close();
      if (typeof cargarReportes === 'function') await cargarReportes();
      if (typeof cargarStats === 'function') cargarStats();   // refresca también los pines del mapa
    } catch (e) { alert(e.message); }
  });

  function verFoto(id) {
    idActual = id;
    txt.textContent = 'Reporte #' + id;
    img.src = urlFoto(id);
    if (typeof visor.showModal === 'function') visor.showModal(); else window.open(img.src, '_blank');
  }

  window.CaszaAdmin = { verFoto };

  /* ---------------- Respaldo / mudanza ---------------- */
  const sec = document.querySelector('[data-js-respaldo]');
  if (!sec) return;
  const estado = sec.querySelector('[data-js-respaldo-estado]');
  const input = sec.querySelector('[data-js-respaldo-archivo]');
  const aviso = (t, tipo) => { estado.textContent = t; estado.className = 'extra-estado' + (tipo ? ' ' + tipo : ''); };

  sec.querySelector('[data-js-respaldo-descargar]').addEventListener('click', () => {
    // Descarga directa (el navegador guarda el archivo .json)
    window.location.href = `${BASE}/api/index.php?route=respaldo`;
    aviso('⬇️ Descargando el respaldo… Guardalo en un lugar seguro.', 'ok');
  });

  sec.querySelector('[data-js-respaldo-restaurar]').addEventListener('click', () => input.click());

  input.addEventListener('change', async () => {
    const file = input.files && input.files[0];
    input.value = '';
    if (!file) return;
    let respaldo;
    try {
      respaldo = JSON.parse(await file.text());
    } catch (e) {
      aviso('⚠️ El archivo no es un respaldo válido (.json de CaszaMosqui).', 'err');
      return;
    }
    if (respaldo.app !== 'CaszaMosqui' || !respaldo.tablas) {
      aviso('⚠️ El archivo no es un respaldo de CaszaMosqui.', 'err');
      return;
    }
    const t = respaldo.totales || {};
    const resumen = `${t.reportes ?? '?'} reportes, ${t.comentarios ?? '?'} comentarios y ${t.fotos_reporte ?? 0} fotos (generado el ${respaldo.generado || '?'})`;
    const ok = prompt(
      `Vas a REEMPLAZAR todos los datos actuales por los del respaldo:\n${resumen}.\n\n` +
      'Los datos que hay ahora en esta base se borran. Para confirmar escribí RESTAURAR:'
    );
    if (ok !== 'RESTAURAR') { aviso('Restauración cancelada. No se cambió nada.'); return; }
    aviso('⏳ Restaurando… no cierres esta página.');
    try {
      const r = await apiRespuesta('respaldo', { method: 'POST', body: JSON.stringify({ confirmar: 'RESTAURAR', respaldo }) });
      const tt = r.totales || {};
      aviso(`✅ Respaldo restaurado: ${tt.reportes} reportes, ${tt.comentarios} comentarios y ${tt.fotos_reporte} fotos.`, 'ok');
      if (typeof cargarCatalogos === 'function') await cargarCatalogos();
      if (typeof cargarStats === 'function') await cargarStats();
      if (typeof cargarReportes === 'function') await cargarReportes();
    } catch (e) {
      aviso('⚠️ ' + e.message, 'err');
    }
  });
})();
