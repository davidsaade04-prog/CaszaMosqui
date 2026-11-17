/* ============================================================
   CaszaMosqui — Rendición de cuentas en el panel de administración
   - Pedidos y reclamos de vecinos (Ley 25.326, arts. 14 y 16): ver, responder y cerrar.
   - Registro de cambios: quién hizo qué y cuándo (solo lectura).
   Depende de app.js (api, ESCAPAR).
   ============================================================ */
(function () {
  const contReclamos = document.querySelector('[data-js-reclamos]');
  const contHistorial = document.querySelector('[data-js-historial]');
  if (!contReclamos || !contHistorial) return;

  const esc = (t) => (typeof ESCAPAR === 'function' ? ESCAPAR(t ?? '') : String(t ?? ''));
  const TIPO = {
    acceso: '🔎 Acceso a datos', rectificacion: '✏️ Corregir un dato', supresion: '🗑️ Borrar un dato',
    comentario: '⚑ Reclamo por comentario', error: '🐞 Error en la página', otro: '📝 Otro',
  };
  const ESTADO = { nuevo: 'Nuevo', en_curso: 'En curso', resuelto: 'Resuelto' };
  const ACCION = {
    creado: 'Creado', estado: 'Cambio de estado', ubicacion: 'Ubicación', foto_borrada: 'Foto borrada', borrado: 'Borrado',
    limpieza: 'Limpieza automática', respaldo_descargado: 'Respaldo descargado', respaldo_restaurado: 'Respaldo restaurado',
  };
  const ENTIDAD = { reporte: 'Reporte', comentario: 'Comentario', reclamo: 'Pedido', sistema: 'Sistema' };
  const fecha = (dt) => {
    const d = new Date(String(dt).replace(' ', 'T'));
    return isNaN(d) ? esc(dt) : d.toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
  };
  // Días desde que llegó el pedido (para no pasarse de los plazos de la ley)
  const dias = (dt) => Math.floor((Date.now() - new Date(String(dt).replace(' ', 'T'))) / 86400000);

  /* ---------------- Pedidos y reclamos ---------------- */
  const filtroReclamos = document.querySelector('[data-js-reclamos-filtro]');
  async function cargarReclamos() {
    try {
      const estado = filtroReclamos.value;
      const lista = await api('reclamos' + (estado ? '&estado=' + estado : ''));
      if (!lista.length) {
        contReclamos.innerHTML = `<p class="placeholder">No hay pedidos ${estado ? 'en este estado' : 'todavía'}. 👍</p>`;
        return;
      }
      contReclamos.innerHTML = lista.map((r) => {
        const d = dias(r.creado_en);
        const atraso = r.estado !== 'resuelto' && d >= 5;
        return `
        <article class="reclamo-card estado-${esc(r.estado)}" data-reclamo="${Number(r.id)}">
          <header>
            <strong>Pedido #${Number(r.id)} · ${TIPO[r.tipo] || esc(r.tipo)}</strong>
            <span class="reclamo-fecha${atraso ? ' atrasado' : ''}">${fecha(r.creado_en)}${r.estado !== 'resuelto' ? ` · hace ${d} día(s)` : ''}</span>
          </header>
          ${r.referencia ? `<p class="reclamo-ref">Referencia: <strong>${esc(r.referencia)}</strong></p>` : ''}
          <p class="reclamo-detalle">${esc(r.detalle)}</p>
          ${r.contacto ? `<p class="reclamo-contacto">Responder a: <strong>${esc(r.contacto)}</strong></p>` : '<p class="reclamo-contacto">Sin contacto: si hace falta, publicá la respuesta o resolvelo directamente.</p>'}
          <div class="reclamo-gestion">
            <select data-reclamo-estado aria-label="Estado del pedido #${Number(r.id)}">
              ${Object.entries(ESTADO).map(([v, t]) => `<option value="${v}"${v === r.estado ? ' selected' : ''}>${t}</option>`).join('')}
            </select>
            <textarea data-reclamo-respuesta rows="2" maxlength="2000" placeholder="Qué se hizo o qué se respondió (queda registrado)">${esc(r.respuesta || '')}</textarea>
            <button type="button" data-reclamo-guardar>Guardar</button>
          </div>
        </article>`;
      }).join('');
    } catch (e) {
      contReclamos.innerHTML = `<p class="placeholder">⚠️ ${esc(e.message)}</p>`;
    }
  }
  filtroReclamos.addEventListener('change', cargarReclamos);
  contReclamos.addEventListener('click', async (ev) => {
    const b = ev.target.closest('[data-reclamo-guardar]');
    if (!b) return;
    const card = b.closest('[data-reclamo]');
    b.disabled = true;
    try {
      await api('reclamos/' + card.dataset.reclamo, {
        method: 'PATCH',
        body: JSON.stringify({
          estado: card.querySelector('[data-reclamo-estado]').value,
          respuesta: card.querySelector('[data-reclamo-respuesta]').value,
        }),
      });
      await Promise.all([cargarReclamos(), cargarHistorial()]);
    } catch (e) { alert(e.message); b.disabled = false; }
  });

  /* ---------------- Registro de cambios ---------------- */
  const filtroHistorial = document.querySelector('[data-js-historial-filtro]');
  async function cargarHistorial() {
    try {
      const entidad = filtroHistorial.value;
      const lista = await api('historial' + (entidad ? '&entidad=' + entidad : ''));
      if (!lista.length) { contHistorial.innerHTML = '<p class="placeholder">Todavía no hay cambios registrados.</p>'; return; }
      contHistorial.innerHTML = `
        <table class="historial-tabla">
          <thead><tr><th>Fecha</th><th>Quién</th><th>Sobre qué</th><th>Qué pasó</th><th>Detalle</th></tr></thead>
          <tbody>${lista.map((h) => `
            <tr>
              <td>${fecha(h.creado_en)}</td>
              <td>${esc(h.usuario || '—')}</td>
              <td>${ENTIDAD[h.entidad] || esc(h.entidad)}${h.entidad_id ? ' #' + Number(h.entidad_id) : ''}</td>
              <td>${ACCION[h.accion] || esc(h.accion)}</td>
              <td>${esc(h.detalle)}</td>
            </tr>`).join('')}</tbody>
        </table>`;
    } catch (e) {
      contHistorial.innerHTML = `<p class="placeholder">⚠️ ${esc(e.message)}</p>`;
    }
  }
  filtroHistorial.addEventListener('change', cargarHistorial);

  // Los cambios hechos en la lista de reportes también se ven acá
  document.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-js-reportes] button, .foto-visor button')) setTimeout(cargarHistorial, 1200);
  });

  cargarReclamos();
  cargarHistorial();
})();
