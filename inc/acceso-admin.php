<?php
/**
 * Botón de acceso del administrador (esquina superior derecha del encabezado)
 * + ventana de inicio de sesión. Requiere que la página haya definido:
 *   $reportes_logueado, $reportes_error, $reportes_csrf
 * El login real lo valida el servidor (reportes-auth.php); esto es solo la interfaz.
 */
$abrirLogin = !$reportes_logueado && ($reportes_error || isset($_GET['acceso']));
?>
<?php if ($reportes_logueado): ?>
  <a class="btn-admin" href="<?= e(BASE_URL) ?>/admin.php" title="Ir al panel de reportes">
    <span aria-hidden="true">📋</span><span class="btn-admin-txt">Panel de reportes</span>
  </a>
<?php else: ?>
  <button type="button" class="btn-admin" data-js-abrir-login aria-haspopup="dialog" title="Acceso administrador">
    <span aria-hidden="true">🔐</span><span class="btn-admin-txt">Administrador</span>
  </button>

  <dialog class="login-modal" id="login-admin" aria-labelledby="login-admin-titulo"<?= $abrirLogin ? ' data-abrir' : '' ?>>
    <form method="dialog" class="login-modal-cerrar-form">
      <button type="submit" class="login-modal-cerrar" aria-label="Cerrar">✕</button>
    </form>
    <div class="reportes-login-icon" aria-hidden="true">🔒</div>
    <h2 id="login-admin-titulo">Acceso administrador</h2>
    <p class="sub">Ingresá para gestionar los criaderos reportados. Los cambios se reflejan en el mapa de riesgo.</p>
    <form method="post" action="<?= e(BASE_URL) ?>/reportes-auth.php" class="reportes-login-form">
      <input type="hidden" name="accion" value="login">
      <input type="hidden" name="csrf" value="<?= e($reportes_csrf) ?>">
      <label for="login-usuario">
        Usuario
        <input id="login-usuario" name="usuario" type="text" autocomplete="username" required>
      </label>
      <label for="login-contrasena">
        Contraseña
        <input id="login-contrasena" name="contrasena" type="password" autocomplete="current-password" required>
      </label>
      <?php if ($reportes_error): ?>
        <p class="reportes-login-error" role="alert"><?= e($reportes_error) ?></p>
      <?php endif; ?>
      <button type="submit" class="btn-login">Ingresar</button>
    </form>
    <p class="reportes-login-note">Cualquier vecino puede reportar un criadero sin usuario.</p>
  </dialog>

  <script>
  (function () {
    var modal = document.getElementById('login-admin');
    if (!modal || typeof modal.showModal !== 'function') return;
    function abrir() {
      modal.showModal();
      setTimeout(function () { document.getElementById('login-usuario').focus(); }, 30);
    }
    document.querySelectorAll('[data-js-abrir-login]').forEach(function (b) { b.addEventListener('click', abrir); });
    // Clic fuera del recuadro = cerrar
    modal.addEventListener('click', function (ev) { if (ev.target === modal) modal.close(); });
    if (modal.hasAttribute('data-abrir')) abrir();
    // Quitar ?acceso=1 de la dirección para que no se reabra al recargar
    if (location.search.indexOf('acceso') !== -1 && history.replaceState) {
      history.replaceState(null, '', location.pathname + location.hash);
    }
  })();
  </script>
<?php endif; ?>
