<?php
/**
 * API REST — CaszaMosqui · FormosaHack 2026
 * ==========================================
 * Rutas (usar ?route=):
 *   GET    /api/?route=tipos
 *   GET    /api/?route=barrios
 *   GET    /api/?route=reportes            (requiere sesión; filtros: ?tipo=&barrio=&estado=&q=&pagina=&por_pagina=)
 *   GET    /api/?route=reportes/{id}       (requiere sesión)
 *   POST   /api/?route=reportes            (body JSON · PÚBLICO: cualquier vecino puede reportar)
 *   PATCH  /api/?route=reportes/{id}       (requiere sesión · estado: pendiente|verificado|controlado, o voto)
 *   DELETE /api/?route=reportes/{id}       (requiere sesión)
 *   GET    /api/?route=stats               (KPIs + índice de riesgo por barrio)
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';

/* ---------- Resolución de la ruta ---------- */
$route = $_GET['route'] ?? '';
if ($route === '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $base   = dirname($script);
    $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    if (str_starts_with($uri, $base . '/')) {
        $route = substr($uri, strlen($base) + 1);
    }
}
$route  = preg_replace('#^index\.php/?#', '', $route);
$route  = trim($route, '/');
$method = $_SERVER['REQUEST_METHOD'];

/* ---------- Helpers de recursos ---------- */

function listar_tipos(): array
{
    return db()->query('SELECT id, nombre, color, icono FROM tipos_criadero ORDER BY id')->fetchAll();
}

function listar_barrios(): array
{
    return db()->query('SELECT id, nombre, localidad, x, y, poblacion FROM barrios ORDER BY nombre')->fetchAll();
}

function validar_estado(string $estado): bool
{
    return in_array($estado, ['pendiente', 'verificado', 'controlado'], true);
}

function url_base(): string
{
    return BASE_URL . '/api';
}

/** La consulta y la gestión de reportes requieren la sesión de Reportes. */
function exigir_acceso_reportes(): void
{
    if (!reportes_logueado()) {
        json_error('Iniciá sesión para acceder a los reportes.', 401);
    }
}

/* ---------- Rutas ---------- */

if ($route === '' && $method === 'GET') {
    $b = url_base();
    json_response([
        'ok' => true,
        'app' => APP_NAME,
        'version' => APP_VERSION,
        'endpoints' => [
            "$b?route=tipos",
            "$b?route=barrios",
            "$b?route=reportes",
            "$b?route=reportes&tipo={id}&barrio={id}&estado={estado}&q={texto}&pagina={1}&por_pagina={4}",
            "$b?route=reportes/{id}",
            "$b?route=stats",
        ],
    ]);
}

// Tipos de criadero
if ($route === 'tipos' && $method === 'GET') {
    json_response(['ok' => true, 'data' => listar_tipos()]);
}

// Barrios
if ($route === 'barrios' && $method === 'GET') {
    json_response(['ok' => true, 'data' => listar_barrios()]);
}

// Estadísticas + índice de riesgo
if ($route === 'stats' && $method === 'GET') {
    $total   = (int) db()->query('SELECT COUNT(*) FROM reportes')->fetchColumn();

    $porEstado = db()->query(
        "SELECT estado, COUNT(*) AS total FROM reportes GROUP BY estado"
    )->fetchAll();

    $porTipo = db()->query(
        "SELECT t.id, t.nombre AS tipo, t.color, t.icono, COUNT(r.id) AS total
           FROM tipos_criadero t
           LEFT JOIN reportes r ON r.tipo_id = t.id
          GROUP BY t.id, t.nombre, t.color, t.icono
          ORDER BY total DESC"
    )->fetchAll();

    // Índice de riesgo por barrio (semáforo)
    //   activos = pendientes + verificados   |   controlados = controlado
    //   riesgo = activos / (controlados + 1)   (fórmula simple, documentada en docs)
    $riesgo = db()->query(
        "SELECT b.id, b.nombre, b.localidad, b.x, b.y,
                SUM(CASE WHEN r.estado IN ('pendiente','verificado') THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN r.estado = 'controlado' THEN 1 ELSE 0 END) AS controlados
           FROM barrios b
           LEFT JOIN reportes r ON r.barrio_id = b.id
          GROUP BY b.id, b.nombre, b.localidad, b.x, b.y
          ORDER BY activos DESC"
    )->fetchAll();

    foreach ($riesgo as &$b) {
        $activos     = (int) $b['activos'];
        $controlados = (int) $b['controlados'];
        // Índice de riesgo activo = criaderos sin controlar en el barrio
        $b['indice'] = $activos;
        $b['nivel']  = $activos >= 4 ? 'alto'
                     : ($activos >= 2 ? 'medio' : 'bajo');
        unset($b['activos'], $b['controlados']);
    }

    json_response([
        'ok' => true,
        'data' => [
            'total'           => $total,
            'por_estado'      => $porEstado,
            'por_tipo'        => $porTipo,
            'riesgo_barrios'  => $riesgo,
            'controlados_pct' => $total > 0
                ? round(array_sum(array_map(
                    fn ($s) => $s['estado'] === 'controlado' ? $s['total'] : 0,
                    $porEstado
                )) / $total * 100)
                : 0,
        ],
    ]);
}

// LISTA de reportes con filtros
if (preg_match('#^reportes$#', $route) && $method === 'GET') {
    exigir_acceso_reportes();

    $tipo   = val($_GET['tipo'] ?? '');
    $barrio = val($_GET['barrio'] ?? '');
    $estado = val($_GET['estado'] ?? '');
    $q      = trim($_GET['q'] ?? '');
    $paginaSolicitada = max(1, (int) ($_GET['pagina'] ?? $_GET['page'] ?? 1));
    $porPagina = min(200, max(1, (int) ($_GET['por_pagina'] ?? $_GET['limit'] ?? 4)));

    // Se construye una sola condición para contar y luego traer la página actual.
    $where = ' WHERE 1=1';
    $pars  = [];

    if ($tipo !== '')   { $where .= ' AND r.tipo_id = ?';   $pars[] = (int) $tipo; }
    if ($barrio !== '') { $where .= ' AND r.barrio_id = ?'; $pars[] = (int) $barrio; }
    if ($estado !== '' && validar_estado($estado)) {
        $where .= ' AND r.estado = ?';
        $pars[] = $estado;
    }
    if ($q !== '') {
        // LOWER() hace la búsqueda insensible a mayúsculas también en PostgreSQL
        $where .= ' AND (LOWER(r.titulo) LIKE LOWER(?) OR LOWER(r.descripcion) LIKE LOWER(?) OR LOWER(r.referencia) LIKE LOWER(?) OR LOWER(b.nombre) LIKE LOWER(?) OR LOWER(t.nombre) LIKE LOWER(?))';
        $like = "%{$q}%";
        array_push($pars, $like, $like, $like, $like, $like);
    }

    $countStmt = db()->prepare(
        "SELECT COUNT(*)
           FROM reportes r
           JOIN tipos_criadero t ON t.id = r.tipo_id
           JOIN barrios b        ON b.id = r.barrio_id"
        . $where
    );
    $countStmt->execute($pars);
    $total = (int) $countStmt->fetchColumn();

    $totalPaginas = $total > 0 ? (int) ceil($total / $porPagina) : 0;
    $pagina = $totalPaginas > 0
        ? min($paginaSolicitada, $totalPaginas)
        : 1;
    $offset = ($pagina - 1) * $porPagina;

    $sql = "SELECT r.id, r.titulo, r.descripcion, r.referencia, r.estado, r.votos,
                   r.creado_en,
                   t.nombre AS tipo, t.color, t.icono,
                   b.nombre AS barrio, b.localidad
              FROM reportes r
              JOIN tipos_criadero t ON t.id = r.tipo_id
              JOIN barrios b        ON b.id = r.barrio_id"
        . $where
        . ' ORDER BY r.creado_en DESC, r.id DESC LIMIT ' . $porPagina . ' OFFSET ' . $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($pars);

    json_response([
        'ok'   => true,
        'data' => $stmt->fetchAll(),
        'meta' => [
            'pagina'          => $pagina,
            'por_pagina'      => $porPagina,
            'total'           => $total,
            'total_paginas'   => $totalPaginas,
            'tiene_anterior'  => $pagina > 1,
            'tiene_siguiente' => $pagina < $totalPaginas,
            'desde'           => $total > 0 ? $offset + 1 : 0,
            'hasta'           => min($offset + $porPagina, $total),
        ],
    ]);
}

// UN reporte + operaciones por id
if (preg_match('#^reportes/(\d+)$#', $route, $m)) {
    exigir_acceso_reportes();
    $id = (int) $m[1];

    if ($method === 'GET') {
        $stmt = db()->prepare(
            "SELECT r.*, t.nombre AS tipo, t.color, t.icono, b.nombre AS barrio, b.localidad
               FROM reportes r
               JOIN tipos_criadero t ON t.id = r.tipo_id
               JOIN barrios b        ON b.id = r.barrio_id
              WHERE r.id = ?
              LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_error('Reporte no encontrado', 404);
        json_response(['ok' => true, 'data' => $row]);
    }

    if ($method === 'PATCH' || $method === 'PUT') {
        $body = body_json();
        // Si no viene estado, permitir sumar votos (participación comunitaria)
        if (isset($body['votos'])) {
            $stmt = db()->prepare('UPDATE reportes SET votos = votos + 1 WHERE id = ?');
            $stmt->execute([$id]);
            json_response(['ok' => true, 'message' => 'Voto registrado']);
        }
        $estado = $body['estado'] ?? '';
        if (!validar_estado($estado)) {
            json_error('Estado inválido. Use: pendiente, verificado o controlado.');
        }
        $stmt = db()->prepare('UPDATE reportes SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);
        if ($stmt->rowCount() === 0) json_error('Reporte no encontrado', 404);
        json_response(['ok' => true, 'message' => 'Estado actualizado a ' . $estado]);
    }

    if ($method === 'DELETE') {
        $stmt = db()->prepare('DELETE FROM reportes WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_error('Reporte no encontrado', 404);
        json_response(['ok' => true, 'message' => 'Reporte eliminado']);
    }
}

// CREAR reporte
if ($route === 'reportes' && $method === 'POST') {
    $b = body_json();

    $titulo      = trim($b['titulo'] ?? '');
    $descripcion = trim($b['descripcion'] ?? '');
    $referencia  = trim($b['referencia'] ?? '');
    $tipo_id     = (int) ($b['tipo_id'] ?? 0);
    $barrio_id   = (int) ($b['barrio_id'] ?? 0);

    if ($titulo === '' || mb_strlen($titulo) > 120) {
        json_error('El título es obligatorio (máx. 120 caracteres).');
    }
    if ($descripcion === '') {
        json_error('La descripción es obligatoria.');
    }
    if ($tipo_id < 1)   json_error('Debe elegir un tipo de criadero.');
    if ($barrio_id < 1) json_error('Debe elegir el barrio.');

    $stmt = db()->prepare(
        'INSERT INTO reportes (tipo_id, barrio_id, titulo, descripcion, referencia)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$tipo_id, $barrio_id, $titulo, $descripcion, $referencia]);
    $id = (int) db()->lastInsertId();

    $stmt = db()->prepare('SELECT * FROM reportes WHERE id = ?');
    $stmt->execute([$id]);

    json_response(['ok' => true, 'message' => 'Criadero reportado', 'data' => $stmt->fetch()], 201);
}

// ========== COMENTARIOS ==========

// LISTA de comentarios
if (preg_match('#^comentarios$#', $route) && $method === 'GET') {
    $barrio = val($_GET['barrio'] ?? '');
    $limit  = min(1000, max(1, (int) ($_GET['limit'] ?? 1000)));   // se muestran todos los comentarios

    $sql = "SELECT c.id, c.tipo, c.texto, c.creado_en, b.nombre AS barrio_nombre
            FROM comentarios c
            JOIN barrios b ON b.id = c.barrio_id
            WHERE 1=1";
    $pars = [];

    if ($barrio !== '') {
        $sql .= ' AND c.barrio_id = ?';
        $pars[] = (int) $barrio;
    }
    $sql .= ' ORDER BY c.creado_en DESC, c.id DESC LIMIT ' . $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute($pars);
    json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
}

// CREAR comentario
if ($route === 'comentarios' && $method === 'POST') {
    $b = body_json();

    $barrio_id = (int) ($b['barrio_id'] ?? 0);
    $tipo      = $b['tipo'] ?? 'sugerencia';
    $texto     = trim($b['texto'] ?? '');

    if ($barrio_id < 1) json_error('Debe elegir un barrio.');
    if (!in_array($tipo, ['sugerencia','problema','felicitacion','otro'], true))
        json_error('Tipo inválido.');
    if ($texto === '' || mb_strlen($texto) > 2000)
        json_error('El comentario es obligatorio (máx. 2000 caracteres).');

    $stmt = db()->prepare(
        'INSERT INTO comentarios (barrio_id, tipo, texto) VALUES (?, ?, ?)'
    );
    $stmt->execute([$barrio_id, $tipo, $texto]);
    $id = (int) db()->lastInsertId();

    $stmt = db()->prepare(
        'SELECT c.*, b.nombre AS barrio_nombre FROM comentarios c JOIN barrios b ON b.id = c.barrio_id WHERE c.id = ?'
    );
    $stmt->execute([$id]);

    json_response(['ok' => true, 'message' => 'Comentario enviado', 'data' => $stmt->fetch()], 201);
}

// Ruta desconocida
json_error('Ruta no encontrada. Ver /api/?route=', 404);