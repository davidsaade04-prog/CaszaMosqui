<?php
/**
 * CaszaMosqui — Pantalla de bienvenida: el logo y la elección de modo (normal o inmersivo).
 * Se muestra cada vez que alguien entra a la página desde afuera (otro sitio, un link,
 * la app instalada o escribiendo la dirección). No aparece al volver al inicio desde otra
 * sección de CaszaMosqui ni con ?modo=normal (el botón "Modo normal" del modo inmersivo).
 * Sin JavaScript también funciona: las dos opciones son enlaces.
 */
if (($_GET['modo'] ?? '') === 'normal') return;

/** Mosquito dibujado (original), con alas que aletean. */
$mosquito = '<svg viewBox="0 0 64 40" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-linecap="round">'
    . '<path class="ala ala-a" d="M30 18 C24 4 12 2 8 6 C14 12 22 16 30 18Z" fill="currentColor" fill-opacity=".18" stroke-width="1.2"/>'
    . '<path class="ala ala-b" d="M32 18 C30 6 22 0 17 2 C20 9 26 15 32 18Z" fill="currentColor" fill-opacity=".14" stroke-width="1.2"/>'
    . '<path d="M14 21 L44 19" stroke-width="3.2"/><circle cx="47" cy="18.6" r="3" fill="currentColor" stroke="none"/>'
    . '<path d="M50 18 L61 15" stroke-width="1.3"/>'
    . '<path d="M24 21 L18 32 L14 36 M30 21 L28 33 L26 38 M36 20 L40 31 L44 35" stroke-width="1.1"/></g></svg>';
?>
<div class="bienvenida" data-js-bienvenida role="dialog" aria-modal="true" aria-labelledby="bienvenida-titulo">
  <script>
    // Decidir antes de que se pinte: si viene de otra sección de CaszaMosqui, no preguntar.
    (function () {
      var d = document.querySelector('[data-js-bienvenida]');
      var interno = false;
      try { interno = !!document.referrer && new URL(document.referrer).origin === location.origin; } catch (e) {}
      if (interno) { d.remove(); return; }
      document.documentElement.classList.add('con-bienvenida');
    })();
  </script>
  <div class="bv-fondo" aria-hidden="true"><span class="bv-piso"></span><span class="bv-luz"></span></div>
  <span class="bv-mosquito bv-mosquito-1" aria-hidden="true"><?= $mosquito ?></span>
  <span class="bv-mosquito bv-mosquito-2" aria-hidden="true"><?= $mosquito ?></span>

  <div class="bv-contenido">
    <div class="bv-logo">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-casza-hero.webp" alt="CaszaMosqui" width="1172" height="896" fetchpriority="high">
      <span class="bv-brillo" aria-hidden="true"></span>
    </div>
    <p class="bv-ceja">El Colorado, Formosa</p>
    <h1 id="bienvenida-titulo">¿Cómo querés recorrer CaszaMosqui?</h1>

    <div class="bv-opciones">
      <a class="bv-opcion" href="<?= e(BASE_URL) ?>/?modo=normal" data-js-modo-normal>
        <span class="bv-opcion-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 9h18M8 13h8M8 16.5h5"/></svg>
        </span>
        <span class="bv-opcion-txt">
          <strong>Modo normal</strong>
          <span>La página de siempre: mapa de riesgo, reportes y toda la información, ordenada para leer.</span>
        </span>
        <span class="bv-flecha" aria-hidden="true">→</span>
      </a>
      <a class="bv-opcion bv-opcion-3d" href="<?= e(BASE_URL) ?>/inmersivo.php" data-js-modo-inmersivo>
        <span class="bv-nuevo">Nuevo</span>
        <span class="bv-opcion-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.8 20.5 7.5v9L12 21.2 3.5 16.5v-9z"/><path d="M3.5 7.5 12 12.2l8.5-4.7M12 12.2v9"/></svg>
        </span>
        <span class="bv-opcion-txt">
          <strong>Modo inmersivo</strong>
          <span>El Colorado en 3D: cada barrio es una torre de luz y tenés todas las opciones flotando sobre el mapa.</span>
        </span>
        <span class="bv-flecha" aria-hidden="true">→</span>
      </a>
    </div>
    <p class="bv-pie">Podés cambiar de modo cuando quieras.</p>
  </div>
</div>
<script>
  (function () {
    var d = document.querySelector('[data-js-bienvenida]');
    if (!d || !d.isConnected) return;
    var html = document.documentElement;
    var normal = d.querySelector('[data-js-modo-normal]');
    var inmersivo = d.querySelector('[data-js-modo-inmersivo]');
    var reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function entrarNormal(ev) {
      if (ev) ev.preventDefault();
      d.classList.add('saliendo');
      setTimeout(function () {
        d.remove();
        html.classList.remove('con-bienvenida');
        var t = document.getElementById('hero-titulo');
        if (t) { t.setAttribute('tabindex', '-1'); t.focus({ preventScroll: true }); }
      }, reducir ? 0 : 650);
    }
    normal.addEventListener('click', entrarNormal);
    d.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') entrarNormal(ev);
      if (ev.key === 'Tab') {               // el foco queda dentro de las dos opciones
        var a = [normal, inmersivo], i = a.indexOf(document.activeElement);
        ev.preventDefault();
        a[(i + (ev.shiftKey ? a.length - 1 : 1)) % a.length].focus();
      }
    });
    // Ir al modo inmersivo con una transición de "entrada" al mapa
    inmersivo.addEventListener('click', function (ev) {
      if (reducir || ev.metaKey || ev.ctrlKey) return;
      ev.preventDefault();
      d.classList.add('al-3d');
      setTimeout(function () { location.href = inmersivo.href; }, 700);
    });
    // Precargar el motor 3D solo si la persona muestra interés en el modo inmersivo
    var precargado = false;
    function precargar() {
      if (precargado) return; precargado = true;
      ['/assets/vendor/three/three.module.min.js', '/assets/img/plano-holo.webp'].forEach(function (u) {
        var l = document.createElement('link'); l.rel = 'prefetch'; l.href = (window.BASE_URL || <?= json_encode(BASE_URL) ?>) + u; document.head.appendChild(l);
      });
    }
    ['pointerenter', 'focus', 'touchstart'].forEach(function (e) { inmersivo.addEventListener(e, precargar, { passive: true }); });
    d.setAttribute('tabindex', '-1');
    d.focus({ preventScroll: true });
  })();
</script>
