<?php
/**
 * Comentarios y Sugerencias por Barrio — CaszaMosqui
 * FormosaHack 2026
 * 1 comentario por día por dispositivo (localStorage)
 */
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/db.php';

// Cargar barrios server-side como fallback
$barrios = [];
try {
    $stmt = db()->query('SELECT id, nombre FROM barrios ORDER BY nombre');
    $barrios = $stmt->fetchAll();
} catch (Exception $e) {
    $barrios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comentarios · <?= e(APP_NAME) ?></title>
  <link rel="icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" type="image/jpeg">
  <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/styles.css">
  <style>
    .comentarios-container { max-width: 900px; margin: 0 auto; }
    .comentario-form { background: var(--panel); border: 1px solid var(--borde); border-radius: var(--radio); box-shadow: var(--sombra); padding: 24px; margin-bottom: 24px; }
    .comentario-form h3 { margin: 0 0 16px; font-size: 1.15rem; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
    .comentario-form label { margin-bottom: 14px; }
    .comentario-form select, .comentario-form textarea {
      width: 100%; border: 1px solid var(--borde); border-radius: 8px; padding: 10px 12px;
      font-size: .97rem; font-family: inherit; background: #fff;
    }
    .comentario-form textarea { min-height: 120px; resize: vertical; }
    .comentario-form .btn { width: 100%; padding: 12px; font-size: 1rem; }
    .comentario-form .aviso { font-size: .85rem; color: var(--suave); margin-top: 8px; }
    .comentario-form .aviso.bloqueado { color: var(--rojo); font-weight: 600; }
    .comentarios-lista { display: flex; flex-direction: column; gap: 16px; }
    .comentario-card {
      background: var(--panel); border: 1px solid var(--borde); border-radius: var(--radio);
      box-shadow: var(--sombra); padding: 20px;
      border-left: 4px solid var(--acento);
    }
    .comentario-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px; }
    .comentario-barrio { font-weight: 700; color: var(--acento); font-size: .95rem; }
    .comentario-fecha { font-size: .8rem; color: var(--suave); }
    .comentario-texto { color: var(--texto); line-height: 1.6; white-space: pre-wrap; }
    .empty-state { text-align: center; padding: 40px 20px; color: var(--suave); }
    .btn-volver-inicio { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: .85rem; font-weight: 600; color: #fff; background: var(--suave); border: none; border-radius: 8px; cursor: pointer; text-decoration: none; transition: background .15s; margin-bottom: 24px; }
    .btn-volver-inicio:hover { background: #475569; }
    .btn-volver-inicio svg { width: 16px; height: 16px; }
  </style>
</head>
<body>

<header class="topbar">
  <div class="container">
    <div class="brand">
      <img src="<?= e(BASE_URL) ?>/assets/img/logo-casza.jpeg" alt="<?= e(APP_NAME) ?>" class="logo-img">
      <div>
        <h1><?= e(APP_NAME) ?></h1>
        <p>Comentarios y Sugerencias</p>
      </div>
    </div>
    <nav>
      <a href="#" data-nav="mapa">Mapa de riesgo</a>
      <a href="#" data-nav="reportes">Reportes</a>
      <a href="#" data-nav="nuevo">+ Reportar</a>
      <a href="#" data-nav="prevencion">Prevención</a>
      <a href="#" data-nav="quiz">Quiz</a>
      <a href="<?= e(BASE_URL) ?>/test-sintomas.php">Test de Síntomas</a>
      <a href="<?= e(BASE_URL) ?>/comentarios.php" class="active">Comentarios</a>
    </nav>
  </div>
</header>

<main class="container comentarios-container">

  <a href="<?= e(BASE_URL) ?>/" class="btn-volver-inicio">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Volver Al Inicio
  </a>

  <section class="comentario-form">
    <h3>💬 Dejanos tu comentario o sugerencia</h3>
    <form id="form-comentario">
      <div class="grid-2">
        <label>
          Tu barrio <span class="req">*</span>
          <select name="barrio_id" id="select-barrio" required>
            <option value="">— Elegí tu barrio —</option>
            <?php foreach ($barrios as $b): ?>
              <option value="<?= (int)$b['id'] ?>"><?= e($b['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          Tipo <span class="req">*</span>
          <select name="tipo" required>
            <option value="sugerencia">💡 Sugerencia</option>
            <option value="problema">⚠️ Problema</option>
            <option value="felicitacion">👏 Felicitación</option>
            <option value="otro">📝 Otro</option>
          </select>
        </label>
      </div>
      <label>
        Comentario <span class="req">*</span>
        <textarea name="texto" id="textarea-comentario" required placeholder="Contanos qué pensás, qué sugerís o qué problema ves en tu barrio..."></textarea>
      </label>
      <button type="submit" class="btn" id="btn-enviar">Enviar comentario</button>
      <p class="aviso" id="aviso-diario"></p>
    </form>
  </section>

  <section>
    <h3 style="margin-bottom: 16px;">📋 Comentarios de la comunidad</h3>
    <div class="comentarios-lista" id="lista-comentarios">
      <p class="loading">Cargando comentarios…</p>
    </div>
  </section>

</main>

<footer>
  <div class="container">
    <p>🦟 <strong>CaszaMosqui</strong> · Equipo FormosaHack 2026 · Comentarios por barrio</p>
  </div>
</footer>

<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= e(BASE_URL) ?>/assets/js/comentarios.js?v=20240924"></script>
</body>
</html>