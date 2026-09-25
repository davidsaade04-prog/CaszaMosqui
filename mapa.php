<?php /* Mapa de riesgo por barrio (se usa en el inicio y en el panel de administración) */ ?>
  <!-- MAPA DE RIESGO -->
  <section class="panel" id="mapa">
    <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Volver Al Inicio
    </a>
    <div class="panel-head">
      <h2>🗺️ Mapa de riesgo por barrio</h2>
      <div class="mapa-filtros" role="group" aria-label="Filtrar barrios por nivel de riesgo">
        <button type="button" data-nivel="todos" class="activo">Todos</button>
        <button type="button" data-nivel="alto"><i class="dot alto"></i> Alto</button>
        <button type="button" data-nivel="medio"><i class="dot medio"></i> Medio</button>
        <button type="button" data-nivel="bajo"><i class="dot bajo"></i> Bajo</button>
      </div>
      <div class="mapa-controles" role="group" aria-label="Controles de zoom del mapa">
        <button type="button" id="zoom-in" aria-label="Acercar">🔍+</button>
        <button type="button" id="zoom-out" aria-label="Alejar">🔍−</button>
        <button type="button" id="zoom-reset" aria-label="Restablecer vista">⌂</button>
      </div>
    </div>
    <div class="mapa-contenedor">
      <!-- Sidebar con lista de barrios -->
      <aside class="mapa-sidebar" aria-label="Lista de barrios">
        <h3>📍 Barrios</h3>
        <ul data-js-barra-barrios>
          <li><button type="button" data-barrio="todos" class="activo">Todos los barrios</button></li>
        </ul>
      </aside>
      <!-- Mapa con zoom/pan -->
      <div class="mapa-wrapper">
        <!-- Lienzo con la proporción exacta del plano: imagen vectorial + nombres de barrios se mueven juntos -->
        <div class="mapa-lienzo" data-js-mapa-lienzo>
          <img class="mapa-imagen" data-js-mapa-imagen src="<?= e(BASE_URL) ?>/assets/img/mapa-el-colorado.svg"
               alt="Plano de barrios de El Colorado" draggable="false" decoding="async">
          <div class="mapa-burbujas" data-js-mapa-burbujas aria-label="Barrios y nivel de riesgo"></div>
        </div>
      </div>
      <p class="mapa-nota">Plano oficial de barrios de El Colorado. Usá <strong>🔍+ / 🔍−</strong> o la ruedita del mouse para zoom, arrastrá para moverte y <strong>⌂</strong> para volver. <strong>Clic en un barrio</strong> (lista o nombre en el mapa) para acercarte y ver sus criaderos.</p>
    </div>
  </section>
