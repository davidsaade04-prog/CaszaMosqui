/* ============================================================
   CaszaMosqui — Convertidor al tema inmersivo (solo con ?embed=1)
   Recorre la página y oscurece lo que quedó claro: fondos blancos o pastel
   pasan a vidrio oscuro (manteniendo el tono: un aviso verde sigue verde),
   los textos oscuros se aclaran y los bordes claros pasan a cian suave.
   También actúa sobre lo que aparece después (resultados, cuestionario,
   listas que llegan de la API) gracias a un MutationObserver.
   Los colores fuertes (botones rojos, semáforo) no se tocan.
   ============================================================ */
(function () {
  if (!document.documentElement.classList.contains('tema-inmersivo')) return;

  const SALTAR = new Set(['SCRIPT', 'STYLE', 'IMG', 'VIDEO', 'CANVAS', 'IFRAME', 'svg', 'path', 'SVG', 'PICTURE', 'SOURCE', 'BR', 'OPTION']);
  const tocados = new Map();   // elemento → propiedades que cambiamos (para recalcular)

  function rgba(txt) {
    const m = /rgba?\(([^)]+)\)/.exec(txt || '');
    if (!m) return null;
    const p = m[1].split(/[ ,/]+/).filter(Boolean).map(Number);
    return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
  }
  function luz({ r, g, b }) {
    const c = [r, g, b].map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function hsl({ r, g, b }) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2;
    if (max === min) return { h: 0, s: 0, l };
    const d = max - min, s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    const h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
    return { h: h * 60, s, l };
  }

  function poner(el, prop, valor) {
    el.style.setProperty(prop, valor, 'important');
    const lista = tocados.get(el) || new Set();
    lista.add(prop);
    tocados.set(el, lista);
  }
  function limpiar(el) {
    const lista = tocados.get(el);
    if (lista) { lista.forEach((p) => el.style.removeProperty(p)); tocados.delete(el); }
  }

  /** Color de fondo efectivo (el primer ancestro opaco), con los valores originales. */
  function fondoDe(el) {
    for (let n = el; n && n.nodeType === 1; n = n.parentElement) {
      const c = rgba(getComputedStyle(n).backgroundColor);
      if (c && c.a > 0.5) return c;
    }
    return null;
  }

  function convertir(el) {
    if (!el || el.nodeType !== 1 || SALTAR.has(el.tagName) || el.closest('[data-sin-tema], .leaflet-tile-pane, svg')) return;
    const cs = getComputedStyle(el);

    // Fondo claro → vidrio oscuro (con el tono del original si tenía color)
    const bg = rgba(cs.backgroundColor);
    if (bg && bg.a > 0.35 && luz(bg) > 0.62) {
      const { h, s } = hsl(bg);
      poner(el, 'background-color', s < 0.18 ? 'rgba(9, 20, 34, .55)' : `hsla(${h.toFixed(0)}, ${Math.min(70, s * 100).toFixed(0)}%, 16%, .62)`);
    }
    // Degradados claros de fondo (tarjetas con background-image)
    if (cs.backgroundImage && cs.backgroundImage.includes('gradient') && /(255|25[0-4]|24\d|23\d), ?(255|25[0-4]|24\d|23\d)/.test(cs.backgroundImage)) {
      poner(el, 'background-image', 'none');
      if (!tocados.get(el)?.has('background-color')) poner(el, 'background-color', 'rgba(9, 20, 34, .55)');
    }

    // Botones de color vivo y claro (celeste) con texto blanco: texto oscuro para que se lea
    const tx = rgba(cs.color);
    if (bg && bg.a > 0.8 && tx && luz(tx) > 0.8) {
      const lb = luz(bg);
      if (lb > 0.34 && lb <= 0.62 && hsl(bg).s > 0.4) poner(el, 'color', '#04202a');
    }

    // Texto oscuro → claro, salvo que esté sobre un color fuerte (chips del semáforo)
    if (tx && luz(tx) < 0.2) {
      const fondo = fondoDe(el);
      const sobreColor = fondo && !tocados.has(el) && luz(fondo) > 0.12 && luz(fondo) < 0.62 && hsl(fondo).s > 0.35;
      if (!sobreColor) {
        const { h, s } = hsl(tx);
        poner(el, 'color', s < 0.2 ? '#e6f6fa' : `hsl(${h.toFixed(0)}, ${Math.min(85, s * 100 + 10).toFixed(0)}%, 76%)`);
      }
    }

    // Bordes claros → cian suave
    ['Top', 'Right', 'Bottom', 'Left'].forEach((lado) => {
      const w = parseFloat(cs['border' + lado + 'Width']);
      if (!w) return;
      const c = rgba(cs['border' + lado + 'Color']);
      if (c && c.a > 0.3 && luz(c) > 0.6 && hsl(c).s < 0.5) poner(el, 'border-' + lado.toLowerCase() + '-color', 'rgba(103, 232, 249, .22)');
    });
  }

  /** Vuelve a calcular bloques que cambiaron, sin transiciones: así se leen los colores finales. */
  function recalcular(nodos) {
    const html = document.documentElement;
    html.classList.add('tema-recalculando');
    nodos.forEach((n) => { if (n.isConnected) { limpiar(n); n.querySelectorAll('*').forEach(limpiar); } });
    void html.offsetHeight;
    nodos.forEach((n) => { if (n.isConnected) recorrer(n); });
    void html.offsetHeight;
    html.classList.remove('tema-recalculando');
  }

  function recorrer(raiz) {
    if (raiz.nodeType !== 1) return;
    convertir(raiz);
    raiz.querySelectorAll('*').forEach(convertir);
  }

  function arrancar() {
    recalcular([document.body]);
    document.documentElement.classList.add('tema-listo');
    // Avisar al modo inmersivo que la sección ya se puede mostrar
    if (window.parent !== window) window.parent.postMessage({ tipo: 'casza-tema-listo' }, location.origin);

    let pendientes = new Set(), programado = false;
    const obs = new MutationObserver((cambios) => {
      cambios.forEach((c) => {
        if (c.type === 'childList') c.addedNodes.forEach((n) => { if (n.nodeType === 1) pendientes.add(n); });
        else if (c.type === 'attributes' && c.target.nodeType === 1) {
          // Cambió la clase (respuesta correcta, resultado del test…): volver a calcular ese bloque
          pendientes.add(c.target);
        }
      });
      if (!programado) {
        programado = true;
        requestAnimationFrame(() => {
          programado = false; const lote = pendientes; pendientes = new Set();
          recalcular(lote);
          // Segunda pasada por si algo cambió después (contenido que llega tarde)
          setTimeout(() => recalcular(lote), 450);
        });
      }
    });
    obs.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'hidden'] });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', arrancar);
  else arrancar();
})();
