<?php
/**
 * Enlaces de información (Ayuda, Acerca, Contacto, Privacidad) y botón para pasar al modo inmersivo.
 * Se usa en todas las cabeceras del modo normal.
 */
$_activa = $active_page ?? '';
$_clase = fn(string $c) => $c === $_activa ? ' class="active" aria-current="page"' : '';
?>
    <nav class="topbar-util" aria-label="Información">
      <a href="<?= e(BASE_URL) ?>/ayuda.php"<?= $_clase('ayuda') ?>>Ayuda</a>
      <a href="<?= e(BASE_URL) ?>/acerca.php"<?= $_clase('acerca') ?>>Acerca de nosotros</a>
      <a href="<?= e(BASE_URL) ?>/contacto.php"<?= $_clase('contacto') ?>>Contacto</a>
      <a href="<?= e(BASE_URL) ?>/privacidad.php"<?= $_clase('privacidad') ?>>Privacidad</a>
      <a href="<?= e(BASE_URL) ?>/inmersivo.php" class="topbar-inmersivo" title="Ver El Colorado en 3D">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 22V12M21 7l-9 5-9-5"/></svg>
        Modo inmersivo</a>
    </nav>
