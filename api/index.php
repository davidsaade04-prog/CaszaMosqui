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
 *   GET    /api/?route=reportes/{id}/foto  (requiere sesión · devuelve la imagen)
 *   DELETE /api/?route=reportes/{id}/foto  (requiere sesión)
 *   GET    /api/?route=reportes-mapa       (requiere sesión · reportes con ubicación GPS)
 *   GET    /api/?route=respaldo            (requiere sesión · descarga todo en JSON)
 *   POST   /api/?route=respaldo            (requiere sesión · restaura un respaldo)
 *   POST reportes acepta además (opcionales): lat, lng y foto (data URL JPEG/PNG/WebP, máx. ~600 KB)
 *   PATCH reportes/{id} acepta además: {"ubicacion": {"lat":..,"lng":..}} o {"ubicacion": null}
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

/* ---------- GPS y fotos ---------- */

// Zona válida: El Colorado y alrededores (evita puntos de otro lugar o datos basura)
const GEO_LAT_MIN = -26.37, GEO_LAT_MAX = -26.26;
const GEO_LNG_MIN = -59.43, GEO_LNG_MAX = -59.30;
const FOTO_MAX_BYTES = 600 * 1024;   // el celular la achica antes (~150 KB); esto es el tope de seguridad

/** Devuelve [lat, lng] válidos, null si no vino ubicación, o corta con error si es inválida. */
function leer_ubicacion($lat, $lng): ?array
{
    if (($lat === null || $lat === '') && ($lng === null || $lng === '')) {
        return null;
    }
    if (!is_numeric($lat) || !is_numeric($lng)) {
        json_error('La ubicación no es válida.');
    }
    $lat = round((float) $lat, 6);
    $lng = round((float) $lng, 6);
    if ($lat < GEO_LAT_MIN || $lat > GEO_LAT_MAX || $lng < GEO_LNG_MIN || $lng > GEO_LNG_MAX) {
        json_error('La ubicación marcada está fuera de El Colorado. Revisá el punto en el mapa.');
    }
    return [$lat, $lng];
}

/** Valida una foto enviada como data URL y devuelve [binario, mime, ancho, alto], o null si no vino. */
function leer_foto($dataUrl): ?array
{
    if ($dataUrl === null || $dataUrl === '') {
        return null;
    }
    if (!is_string($dataUrl) || !preg_match('#^data:(image/(?:jpeg|png|webp));base64,([A-Za-z0-9+/=\s]+)$#', $dataUrl, $m)) {
        json_error('La foto no tiene un formato válido (usá JPG, PNG o WebP).');
    }
    if (strlen($m[2]) > FOTO_MAX_BYTES * 1.4) {
        json_error('La foto es demasiado pesada.');
    }
    $bin = base64_decode($m[2], true);
    if ($bin === false || strlen($bin) > FOTO_MAX_BYTES) {
        json_error('La foto es demasiado pesada o está dañada.');
    }
    $info = @getimagesizefromstring($bin);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        json_error('El archivo enviado no es una imagen válida.');
    }
    if ($info[0] > 4000 || $info[1] > 4000) {
        json_error('La foto es demasiado grande.');
    }
    return [$bin, $info['mime'], (int) $info[0], (int) $info[1]];
}

function guardar_foto(int $reporteId, array $foto): void
{
    [$bin, $mime, $ancho, $alto] = $foto;
    $stmt = db()->prepare(
        'INSERT INTO fotos_reporte (reporte_id, mime, datos, ancho, alto, bytes) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bindValue(1, $reporteId, PDO::PARAM_INT);
    $stmt->bindValue(2, $mime);
    $stmt->bindValue(3, $bin, PDO::PARAM_LOB);
    $stmt->bindValue(4, $ancho, PDO::PARAM_INT);
    $stmt->bindValue(5, $alto, PDO::PARAM_INT);
    $stmt->bindValue(6, strlen($bin), PDO::PARAM_INT);
    $stmt->execute();
}

/** En PostgreSQL los BYTEA llegan como stream; en MySQL como texto. */
function blob_a_texto($v): string
{
    return is_resource($v) ? (string) stream_get_contents($v) : (string) $v;
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
                   r.creado_en, r.lat, r.lng,
                   CASE WHEN EXISTS (SELECT 1 FROM fotos_reporte f WHERE f.reporte_id = r.id) THEN 1 ELSE 0 END AS tiene_foto,
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
        // El administrador puede mover o quitar el pin del criadero
        if (array_key_exists('ubicacion', $body)) {
            $u = $body['ubicacion'];
            $ubic = is_array($u) ? leer_ubicacion($u['lat'] ?? null, $u['lng'] ?? null) : null;
            $stmt = db()->prepare('UPDATE reportes SET lat = ?, lng = ? WHERE id = ?');
            $stmt->execute([$ubic[0] ?? null, $ubic[1] ?? null, $id]);
            if ($stmt->rowCount() === 0) json_error('Reporte no encontrado', 404);
            json_response(['ok' => true, 'message' => $ubic ? 'Ubicación actualizada' : 'Ubicación quitada']);
        }
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

// FOTO de un reporte (solo administrador)
if (preg_match('#^reportes/(\d+)/foto$#', $route, $m)) {
    exigir_acceso_reportes();
    $id = (int) $m[1];

    if ($method === 'GET') {
        $stmt = db()->prepare('SELECT mime, datos FROM fotos_reporte WHERE reporte_id = ?');
        $stmt->execute([$id]);
        $f = $stmt->fetch();
        if (!$f) json_error('Este reporte no tiene foto.', 404);
        $bin = blob_a_texto($f['datos']);
        header('Content-Type: ' . $f['mime']);
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        echo $bin;
        exit;
    }

    if ($method === 'DELETE') {
        $stmt = db()->prepare('DELETE FROM fotos_reporte WHERE reporte_id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_error('Este reporte no tenía foto.', 404);
        json_response(['ok' => true, 'message' => 'Foto eliminada']);
    }
}

// REPORTES con ubicación GPS para el mapa del administrador
if ($route === 'reportes-mapa' && $method === 'GET') {
    exigir_acceso_reportes();
    $stmt = db()->query(
        "SELECT r.id, r.titulo, r.referencia, r.estado, r.votos, r.creado_en, r.lat, r.lng, r.barrio_id,
                t.nombre AS tipo, t.icono, t.color, b.nombre AS barrio,
                CASE WHEN EXISTS (SELECT 1 FROM fotos_reporte f WHERE f.reporte_id = r.id) THEN 1 ELSE 0 END AS tiene_foto
           FROM reportes r
           JOIN tipos_criadero t ON t.id = r.tipo_id
           JOIN barrios b        ON b.id = r.barrio_id
          WHERE r.lat IS NOT NULL AND r.lng IS NOT NULL
          ORDER BY r.creado_en DESC"
    );
    json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
}

// RESPALDO completo (para mudar la base gratuita de Render a una nueva)
const RESPALDO_TABLAS = [
    'tipos_criadero' => ['id', 'nombre', 'color', 'icono'],
    'barrios'        => ['id', 'nombre', 'localidad', 'x', 'y', 'poblacion'],
    'reportes'       => ['id', 'tipo_id', 'barrio_id', 'titulo', 'descripcion', 'referencia', 'estado', 'votos', 'creado_en', 'lat', 'lng'],
    'comentarios'    => ['id', 'barrio_id', 'tipo', 'texto', 'creado_en'],
    'fotos_reporte'  => ['reporte_id', 'mime', 'datos', 'ancho', 'alto', 'bytes', 'creado_en'],
];

if ($route === 'respaldo' && $method === 'GET') {
    exigir_acceso_reportes();
    $tablas = [];
    foreach (RESPALDO_TABLAS as $tabla => $cols) {
        $orden = $tabla === 'fotos_reporte' ? 'reporte_id' : 'id';
        $filas = db()->query('SELECT ' . implode(', ', $cols) . " FROM $tabla ORDER BY $orden")->fetchAll();
        if ($tabla === 'fotos_reporte') {
            foreach ($filas as &$f) {
                $f['datos'] = base64_encode(blob_a_texto($f['datos']));
            }
            unset($f);
        }
        $tablas[$tabla] = $filas;
    }
    $nombre = 'caszamosqui-respaldo-' . date('Y-m-d-Hi') . '.json';
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Cache-Control: no-store');
    json_response([
        'app'      => 'CaszaMosqui',
        'formato'  => 2,
        'generado' => date('Y-m-d H:i:s'),
        'motor'    => db_driver(),
        'totales'  => array_map('count', $tablas),
        'tablas'   => $tablas,
    ]);
}

if ($route === 'respaldo' && $method === 'POST') {
    exigir_acceso_reportes();
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
        json_error('Formato de envío inválido.', 415);
    }
    $b = body_json();
    if (($b['confirmar'] ?? '') !== 'RESTAURAR') {
        json_error('Falta la confirmación para restaurar.');
    }
    $r = $b['respaldo'] ?? null;
    if (!is_array($r) || ($r['app'] ?? '') !== 'CaszaMosqui' || !is_array($r['tablas'] ?? null)) {
        json_error('El archivo no es un respaldo de CaszaMosqui.');
    }
    foreach (['tipos_criadero', 'barrios', 'reportes'] as $obligatoria) {
        if (!isset($r['tablas'][$obligatoria]) || !is_array($r['tablas'][$obligatoria]) || !$r['tablas'][$obligatoria]) {
            if ($obligatoria !== 'reportes') json_error("Al respaldo le falta la tabla $obligatoria.");
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach (array_reverse(array_keys(RESPALDO_TABLAS)) as $tabla) {
            $pdo->exec("DELETE FROM $tabla");
        }
        $totales = [];
        foreach (RESPALDO_TABLAS as $tabla => $cols) {
            $filas = is_array($r['tablas'][$tabla] ?? null) ? $r['tablas'][$tabla] : [];
            $stmt = $pdo->prepare(
                "INSERT INTO $tabla (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')'
            );
            foreach ($filas as $fila) {
                if (!is_array($fila)) continue;
                foreach ($cols as $i => $col) {
                    $v = $fila[$col] ?? null;
                    if ($tabla === 'fotos_reporte' && $col === 'datos') {
                        $bin = base64_decode((string) $v, true);
                        if ($bin === false) throw new RuntimeException('Foto dañada en el respaldo.');
                        $stmt->bindValue($i + 1, $bin, PDO::PARAM_LOB);
                    } elseif ($v === null) {
                        $stmt->bindValue($i + 1, null, PDO::PARAM_NULL);
                    } else {
                        $stmt->bindValue($i + 1, is_scalar($v) ? (string) $v : json_encode($v));
                    }
                }
                $stmt->execute();
            }
            $totales[$tabla] = count($filas);
        }
        if (db_driver() === 'pgsql') {
            foreach (['tipos_criadero', 'barrios', 'reportes', 'comentarios'] as $tabla) {
                $pdo->query("SELECT setval(pg_get_serial_sequence('$tabla', 'id'), COALESCE((SELECT MAX(id) FROM $tabla), 1), (SELECT COUNT(*) FROM $tabla) > 0)");
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[CaszaMosqui] Error al restaurar respaldo: ' . $e->getMessage());
        json_error('No se pudo restaurar: ' . $e->getMessage() . ' (no se cambió nada).', 400);
    }
    json_response(['ok' => true, 'message' => 'Respaldo restaurado', 'totales' => $totales]);
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

    // Opcionales: ubicación GPS y foto (el reporte funciona igual sin ellas)
    $ubic = leer_ubicacion($b['lat'] ?? null, $b['lng'] ?? null);
    $foto = leer_foto($b['foto'] ?? null);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO reportes (tipo_id, barrio_id, titulo, descripcion, referencia, lat, lng)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tipo_id, $barrio_id, $titulo, $descripcion, $referencia, $ubic[0] ?? null, $ubic[1] ?? null]);
        $id = (int) $pdo->lastInsertId();
        if ($foto) {
            guardar_foto($id, $foto);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[CaszaMosqui] Error al guardar reporte: ' . $e->getMessage());
        json_error('No se pudo guardar el reporte. Probá de nuevo.', 500);
    }

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