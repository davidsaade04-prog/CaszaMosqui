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
 *   DELETE /api/?route=comentarios/{id}    (requiere sesión · moderación)
 *   POST   /api/?route=reclamos            (PÚBLICO · pedidos de acceso, rectificación o supresión; reclamos)
 *   GET    /api/?route=reclamos            (requiere sesión)
 *   PATCH  /api/?route=reclamos/{id}       (requiere sesión · estado y respuesta)
 *   GET    /api/?route=historial           (requiere sesión · registro de cambios)
 *   POST reportes exige "acepta": true (consentimiento informado, Ley 25.326)
 *   POST reportes acepta además (opcionales): lat, lng y foto (data URL JPEG/PNG/WebP, máx. ~600 KB)
 *   PATCH reportes/{id} acepta además: {"ubicacion": {"lat":..,"lng":..}} o {"ubicacion": null}
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/riesgo.php';

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

/**
 * Filtros de la lista de reportes (los mismos del panel): tipo, barrio, estado y búsqueda.
 * Devuelve [where, parámetros]. Lo usan la lista paginada y la exportación a Excel.
 */
function filtros_reportes(array $get): array
{
    $tipo   = val($get['tipo'] ?? '');
    $barrio = val($get['barrio'] ?? '');
    $estado = val($get['estado'] ?? '');
    $q      = trim((string) ($get['q'] ?? ''));
    $where  = ' WHERE 1=1';
    $pars   = [];
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
    return [$where, $pars];
}

/** La consulta y la gestión de reportes requieren la sesión de Reportes. */
function exigir_acceso_reportes(): void
{
    if (!reportes_logueado()) {
        json_error('Iniciá sesión para acceder a los reportes.', 401);
    }
}

/* ---------- Rendición de cuentas y datos personales ---------- */

/** Deja constancia de quién hizo qué y cuándo (tabla historial). Nunca corta la operación si falla. */
function registrar(string $entidad, ?int $entidadId, string $accion, string $detalle = '', ?string $usuario = null): void
{
    try {
        $usuario = $usuario ?? (reportes_logueado() ? usuario_reportes() : 'público');
        db()->prepare('INSERT INTO historial (entidad, entidad_id, accion, detalle, usuario) VALUES (?, ?, ?, ?, ?)')
            ->execute([$entidad, $entidadId, $accion, mb_substr($detalle, 0, 255), mb_substr($usuario, 0, 60)]);
    } catch (Throwable $e) {
        error_log('[CaszaMosqui] No se pudo registrar en el historial: ' . $e->getMessage());
    }
}

const DIAS_CONSERVAR_FOTO_GPS = 90;   // días después de "controlado" (ver privacidad.php)

/**
 * Minimización de datos: a los 90 días de controlado un criadero, se borran su foto y su
 * ubicación exacta (queda el barrio, que alcanza para las estadísticas). Corre una vez por día.
 */
function limpiar_datos_vencidos(): void
{
    $marca = __DIR__ . '/../cache/limpieza-' . date('Y-m-d') . '.ok';
    if (is_file($marca)) return;
    try {
        $corte = date('Y-m-d H:i:s', strtotime('-' . DIAS_CONSERVAR_FOTO_GPS . ' days'));
        $cond  = "estado = 'controlado' AND COALESCE(actualizado_en, creado_en) < ?";
        $f = db()->prepare("DELETE FROM fotos_reporte WHERE reporte_id IN (SELECT id FROM reportes WHERE $cond)");
        $f->execute([$corte]);
        $g = db()->prepare("UPDATE reportes SET lat = NULL, lng = NULL WHERE lat IS NOT NULL AND $cond");
        $g->execute([$corte]);
        if ($f->rowCount() + $g->rowCount() > 0) {
            registrar('sistema', null, 'limpieza', $f->rowCount() . ' foto(s) y ' . $g->rowCount() . ' ubicación(es) borradas a los '
                . DIAS_CONSERVAR_FOTO_GPS . ' días de controlado', 'sistema (automático)');
        }
        if (!is_dir(dirname($marca))) @mkdir(dirname($marca), 0775, true);
        @file_put_contents($marca, date('c'));
    } catch (Throwable $e) {
        error_log('[CaszaMosqui] Limpieza de datos: ' . $e->getMessage());
    }
}

/** Texto público con datos personales de terceros (teléfonos o correos): no se publica. */
function tiene_datos_personales(string $texto): bool
{
    if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $texto)) return true;          // correo
    $soloNumeros = preg_replace('/(?<=\d)[\s.\-()]+(?=\d)/', '', $texto);
    return (bool) preg_match('/\d{7,}/', $soloNumeros);                                       // teléfono, DNI
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
        // Índice de riesgo activo = criaderos sin controlar en el barrio (reglas en inc/riesgo.php)
        $b['indice'] = $activos;
        $b['nivel']  = nivel_por_indice($activos);
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
    limpiar_datos_vencidos();

    [$where, $pars] = filtros_reportes($_GET);
    $paginaSolicitada = max(1, (int) ($_GET['pagina'] ?? $_GET['page'] ?? 1));
    $porPagina = min(200, max(1, (int) ($_GET['por_pagina'] ?? $_GET['limit'] ?? 4)));

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
            registrar('reporte', $id, 'ubicacion', $ubic ? 'Pin movido' : 'Pin quitado');
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
        $antes = db()->prepare('SELECT estado FROM reportes WHERE id = ?');
        $antes->execute([$id]);
        $anterior = $antes->fetchColumn();
        if ($anterior === false) json_error('Reporte no encontrado', 404);
        $stmt = db()->prepare('UPDATE reportes SET estado = ?, actualizado_en = ? WHERE id = ?');
        $stmt->execute([$estado, date('Y-m-d H:i:s'), $id]);
        if ($anterior !== $estado) registrar('reporte', $id, 'estado', "$anterior → $estado");
        json_response(['ok' => true, 'message' => 'Estado actualizado a ' . $estado]);
    }

    if ($method === 'DELETE') {
        $antes = db()->prepare('SELECT titulo FROM reportes WHERE id = ?');
        $antes->execute([$id]);
        $titulo = $antes->fetchColumn();
        $stmt = db()->prepare('DELETE FROM reportes WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_error('Reporte no encontrado', 404);
        $motivo = trim((string) ($_GET['motivo'] ?? ''));
        registrar('reporte', $id, 'borrado', '"' . mb_substr((string) $titulo, 0, 60) . '"' . ($motivo !== '' ? ' · motivo: ' . mb_substr($motivo, 0, 120) : ''));
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
        registrar('reporte', $id, 'foto_borrada', 'Foto borrada por el administrador');
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

// MAPA DE CALOR: criaderos activos (sin controlar + verificados) para el administrador.
// Los que tienen GPS van con su punto exacto; los demás, al centro de su barrio (lo ubica el navegador).
if ($route === 'reportes-calor' && $method === 'GET') {
    exigir_acceso_reportes();
    $stmt = db()->query(
        "SELECT r.id, r.estado, r.lat, r.lng, r.barrio_id
           FROM reportes r
          WHERE r.estado IN ('pendiente', 'verificado')"
    );
    $puntos = array_map(fn ($r) => [
        'id'        => (int) $r['id'],
        'estado'    => $r['estado'],
        'lat'       => $r['lat'] !== null ? (float) $r['lat'] : null,
        'lng'       => $r['lng'] !== null ? (float) $r['lng'] : null,
        'barrio_id' => (int) $r['barrio_id'],
    ], $stmt->fetchAll());
    json_response(['ok' => true, 'data' => $puntos]);
}

// RESPALDO completo (para mudar la base gratuita de Render a una nueva)
const RESPALDO_TABLAS = [
    'tipos_criadero' => ['id', 'nombre', 'color', 'icono'],
    'barrios'        => ['id', 'nombre', 'localidad', 'x', 'y', 'poblacion'],
    'reportes'       => ['id', 'tipo_id', 'barrio_id', 'titulo', 'descripcion', 'referencia', 'estado', 'votos', 'creado_en', 'lat', 'lng', 'cliente_id', 'actualizado_en'],
    'comentarios'    => ['id', 'barrio_id', 'tipo', 'texto', 'creado_en'],
    'fotos_reporte'  => ['reporte_id', 'mime', 'datos', 'ancho', 'alto', 'bytes', 'creado_en'],
    'reclamos'       => ['id', 'tipo', 'referencia', 'detalle', 'contacto', 'estado', 'respuesta', 'creado_en', 'actualizado_en'],
    'historial'      => ['id', 'entidad', 'entidad_id', 'accion', 'detalle', 'usuario', 'creado_en'],
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
    registrar('sistema', null, 'respaldo_descargado', 'Descarga completa de la base (incluye fotos y ubicaciones)');
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
        // El historial no se pisa al restaurar: es el registro de lo que pasó (solo se agrega)
        foreach (array_reverse(array_keys(RESPALDO_TABLAS)) as $tabla) {
            if ($tabla !== 'historial') $pdo->exec("DELETE FROM $tabla");
        }
        $totales = [];
        foreach (RESPALDO_TABLAS as $tabla => $cols) {
            if ($tabla === 'historial') continue;
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
            foreach (['tipos_criadero', 'barrios', 'reportes', 'comentarios', 'reclamos'] as $tabla) {
                $pdo->query("SELECT setval(pg_get_serial_sequence('$tabla', 'id'), COALESCE((SELECT MAX(id) FROM $tabla), 1), (SELECT COUNT(*) FROM $tabla) > 0)");
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[CaszaMosqui] Error al restaurar respaldo: ' . $e->getMessage());
        json_error('No se pudo restaurar: ' . $e->getMessage() . ' (no se cambió nada).', 400);
    }
    registrar('sistema', null, 'respaldo_restaurado', 'Se restauró un respaldo completo');
    json_response(['ok' => true, 'message' => 'Respaldo restaurado', 'totales' => $totales]);
}

// EXPORTAR reportes a Excel (solo administrador). Usa los mismos filtros que la lista.
if ($route === 'reportes-excel' && $method === 'GET') {
    exigir_acceso_reportes();
    require_once __DIR__ . '/../inc/xlsx.php';

    [$where, $pars] = filtros_reportes($_GET);
    $stmt = db()->prepare(
        "SELECT r.id, r.creado_en, r.estado, t.nombre AS tipo, b.nombre AS barrio, r.titulo, r.descripcion,
                r.referencia, r.votos, r.lat, r.lng,
                CASE WHEN EXISTS (SELECT 1 FROM fotos_reporte f WHERE f.reporte_id = r.id) THEN 1 ELSE 0 END AS tiene_foto
           FROM reportes r
           JOIN tipos_criadero t ON t.id = r.tipo_id
           JOIN barrios b        ON b.id = r.barrio_id
         $where
          ORDER BY r.creado_en DESC, r.id DESC"
    );
    $stmt->execute($pars);
    $nombresEstado = ['pendiente' => 'Sin controlar', 'verificado' => 'Verificado', 'controlado' => 'Controlado'];

    $filas = [];
    foreach ($stmt->fetchAll() as $r) {
        $conGps = $r['lat'] !== null && $r['lng'] !== null;
        $filas[] = [
            (int) $r['id'],
            substr((string) $r['creado_en'], 0, 16),
            $nombresEstado[$r['estado']] ?? $r['estado'],
            $r['tipo'],
            $r['barrio'],
            $r['titulo'],
            $r['descripcion'],
            $r['referencia'],
            (int) $r['votos'],
            (int) $r['tiene_foto'] === 1 ? 'Sí' : 'No',
            $conGps ? round((float) $r['lat'], 6) : null,
            $conGps ? round((float) $r['lng'], 6) : null,
            $conGps ? 'https://www.google.com/maps?q=' . round((float) $r['lat'], 6) . ',' . round((float) $r['lng'], 6) : null,
        ];
    }

    // Resumen por barrio (todos los reportes, sin filtros, para ver el panorama completo)
    $resumen = db()->query(
        "SELECT b.nombre,
                SUM(CASE WHEN r.estado = 'pendiente'  THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN r.estado = 'verificado' THEN 1 ELSE 0 END) AS verificados,
                SUM(CASE WHEN r.estado = 'controlado' THEN 1 ELSE 0 END) AS controlados,
                COUNT(r.id) AS total
           FROM barrios b
           LEFT JOIN reportes r ON r.barrio_id = b.id
          GROUP BY b.id, b.nombre
          ORDER BY (SUM(CASE WHEN r.estado IN ('pendiente','verificado') THEN 1 ELSE 0 END)) DESC, b.nombre"
    )->fetchAll();
    $filasResumen = [];
    $niveles = ['alto' => '🔴 Alto', 'medio' => '🟠 Medio', 'bajo' => '🟢 Bajo'];
    foreach ($resumen as $b) {
        $activos = (int) $b['pendientes'] + (int) $b['verificados'];
        $filasResumen[] = [$b['nombre'], (int) $b['pendientes'], (int) $b['verificados'], (int) $b['controlados'],
                           (int) $b['total'], $activos, $niveles[nivel_por_indice($activos)]];
    }

    $filtros = [];
    foreach (['estado' => 'Estado', 'tipo' => 'Tipo (id)', 'barrio' => 'Barrio (id)', 'q' => 'Búsqueda'] as $k => $txt) {
        if (trim((string) ($_GET[$k] ?? '')) !== '') $filtros[] = [$txt, (string) $_GET[$k]];
    }

    $x = new XlsxSimple();
    $x->hoja('Reportes', ['ID', 'Fecha', 'Estado', 'Tipo de criadero', 'Barrio', 'Título', 'Descripción', 'Referencia',
                          'Votos', 'Foto', 'Latitud', 'Longitud', 'Ubicación'], $filas, [
        'anchos'  => [7, 17, 14, 22, 20, 30, 50, 28, 7, 7, 11, 11, 16],
        'ajustar' => [5 => true, 6 => true, 7 => true],
        'links'   => [12 => true],
    ]);
    $x->hoja('Resumen por barrio', ['Barrio', 'Sin controlar', 'Verificados', 'Controlados', 'Total', 'Activos', 'Semáforo'],
             $filasResumen, ['anchos' => [26, 14, 12, 12, 9, 9, 12]]);
    $x->hoja('Información', ['Dato', 'Valor'], array_merge([
        ['Generado', date('d/m/Y H:i')],
        ['Reportes en la hoja "Reportes"', count($filas)],
        ['Filtros aplicados', $filtros ? 'Sí' : 'Ninguno (todos los reportes)'],
    ], $filtros, [
        ['Semáforo', 'Activos = sin controlar + verificados. 0-1 bajo · 2-3 medio · 4 o más alto (sin contar el clima).'],
        ['Fuente', 'CaszaMosqui · El Colorado, Formosa'],
    ]), ['anchos' => [30, 90], 'ajustar' => [1 => true]]);
    $x->descargar('caszamosqui-reportes-' . date('Y-m-d') . '.xlsx');
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
    // Consentimiento informado (Ley 25.326, art. 5): la persona aceptó el aviso de privacidad
    if (($b['acepta'] ?? false) !== true) {
        json_error('Para enviar el reporte tenés que aceptar el aviso de privacidad.');
    }
    if (tiene_datos_personales($titulo . ' ' . $descripcion . ' ' . $referencia)) {
        json_error('No incluyas teléfonos, correos ni documentos en el reporte: no hacen falta para encontrar el criadero.');
    }

    // Opcionales: ubicación GPS y foto (el reporte funciona igual sin ellas)
    $ubic = leer_ubicacion($b['lat'] ?? null, $b['lng'] ?? null);
    $foto = leer_foto($b['foto'] ?? null);

    // Identificador del celular: si el mismo reporte llega dos veces (reenvío sin señal), se guarda una sola
    $clienteId = trim((string) ($b['cliente_id'] ?? ''));
    if ($clienteId !== '' && !preg_match('/^[A-Za-z0-9-]{8,40}$/', $clienteId)) {
        json_error('Identificador de envío inválido.');
    }
    $buscarDuplicado = function () use ($clienteId): ?int {
        if ($clienteId === '') return null;
        $st = db()->prepare('SELECT id FROM reportes WHERE cliente_id = ?');
        $st->execute([$clienteId]);
        $v = $st->fetchColumn();
        return $v === false ? null : (int) $v;
    };
    $responderDuplicado = function (int $id): void {
        $st = db()->prepare('SELECT id, estado, creado_en FROM reportes WHERE id = ?');
        $st->execute([$id]);
        json_response(['ok' => true, 'duplicado' => true, 'message' => 'Este reporte ya se había recibido', 'data' => $st->fetch()]);
    };
    if (($ya = $buscarDuplicado()) !== null) {
        $responderDuplicado($ya);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO reportes (tipo_id, barrio_id, titulo, descripcion, referencia, lat, lng, cliente_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tipo_id, $barrio_id, $titulo, $descripcion, $referencia,
                        $ubic[0] ?? null, $ubic[1] ?? null, $clienteId !== '' ? $clienteId : null]);
        $id = (int) $pdo->lastInsertId();
        if ($foto) {
            guardar_foto($id, $foto);
        }
        $pdo->commit();
        registrar('reporte', $id, 'creado', 'Reporte de un vecino' . ($ubic ? ' · con ubicación' : '') . ($foto ? ' · con foto' : ''));
    } catch (Throwable $e) {
        $pdo->rollBack();
        // Dos envíos simultáneos del mismo reporte: el segundo choca con el índice único
        if (($ya = $buscarDuplicado()) !== null) {
            $responderDuplicado($ya);
        }
        error_log('[CaszaMosqui] Error al guardar reporte: ' . $e->getMessage());
        json_error('No se pudo guardar el reporte. Probá de nuevo.', 500);
    }

    $stmt = db()->prepare('SELECT id, tipo_id, barrio_id, titulo, estado, votos, creado_en FROM reportes WHERE id = ?');
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
    // Los comentarios son públicos: no se publican teléfonos, correos ni documentos
    if (tiene_datos_personales($texto))
        json_error('Los comentarios son públicos: no escribas teléfonos, correos ni números de documento (tuyos ni de otras personas).');

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

// BORRAR comentario (moderación, solo administrador)
if (preg_match('#^comentarios/(\d+)$#', $route, $m) && $method === 'DELETE') {
    exigir_acceso_reportes();
    $id = (int) $m[1];
    $antes = db()->prepare('SELECT b.nombre FROM comentarios c JOIN barrios b ON b.id = c.barrio_id WHERE c.id = ?');
    $antes->execute([$id]);
    $barrio = $antes->fetchColumn();
    if ($barrio === false) json_error('Comentario no encontrado', 404);
    db()->prepare('DELETE FROM comentarios WHERE id = ?')->execute([$id]);
    $motivo = trim((string) ($_GET['motivo'] ?? ''));
    // No se guarda el texto borrado: si tenía datos personales, no deben quedar en el historial
    registrar('comentario', $id, 'borrado', 'Barrio ' . $barrio . ($motivo !== '' ? ' · motivo: ' . mb_substr($motivo, 0, 120) : ''));
    json_response(['ok' => true, 'message' => 'Comentario borrado']);
}

// ========== RECLAMOS (vía de reclamo · Ley 25.326 arts. 14 y 16) ==========
const TIPOS_RECLAMO = ['acceso', 'rectificacion', 'supresion', 'error', 'comentario', 'otro'];

if ($route === 'reclamos' && $method === 'POST') {
    $b = body_json();
    $tipo       = (string) ($b['tipo'] ?? '');
    $referencia = trim((string) ($b['referencia'] ?? ''));
    $detalle    = trim((string) ($b['detalle'] ?? ''));
    $contacto   = trim((string) ($b['contacto'] ?? ''));
    if (!in_array($tipo, TIPOS_RECLAMO, true)) json_error('Elegí qué querés pedir.');
    if ($detalle === '' || mb_strlen($detalle) > 2000) json_error('Contanos tu pedido (máx. 2000 caracteres).');
    if (mb_strlen($referencia) > 60 || mb_strlen($contacto) > 120) json_error('La referencia o el contacto son demasiado largos.');
    if (($b['acepta'] ?? false) !== true) json_error('Tenés que aceptar el aviso de privacidad para enviar el pedido.');
    $st = db()->prepare('INSERT INTO reclamos (tipo, referencia, detalle, contacto) VALUES (?, ?, ?, ?)');
    $st->execute([$tipo, $referencia, $detalle, $contacto]);
    $id = (int) db()->lastInsertId();
    registrar('reclamo', $id, 'creado', 'Tipo: ' . $tipo . ($referencia !== '' ? ' · ref. ' . $referencia : ''));
    json_response(['ok' => true, 'message' => 'Pedido recibido', 'data' => ['id' => $id]], 201);
}

if ($route === 'reclamos' && $method === 'GET') {
    exigir_acceso_reportes();
    $estado = (string) ($_GET['estado'] ?? '');
    $sql = 'SELECT id, tipo, referencia, detalle, contacto, estado, respuesta, creado_en, actualizado_en FROM reclamos';
    $pars = [];
    if (in_array($estado, ['nuevo', 'en_curso', 'resuelto'], true)) { $sql .= ' WHERE estado = ?'; $pars[] = $estado; }
    $st = db()->prepare($sql . ' ORDER BY id DESC LIMIT 300');
    $st->execute($pars);
    json_response(['ok' => true, 'data' => $st->fetchAll()]);
}

if (preg_match('#^reclamos/(\d+)$#', $route, $m) && ($method === 'PATCH' || $method === 'PUT')) {
    exigir_acceso_reportes();
    $id = (int) $m[1];
    $b = body_json();
    $estado = (string) ($b['estado'] ?? '');
    if (!in_array($estado, ['nuevo', 'en_curso', 'resuelto'], true)) json_error('Estado inválido.');
    $respuesta = trim((string) ($b['respuesta'] ?? ''));
    $st = db()->prepare('UPDATE reclamos SET estado = ?, respuesta = ?, actualizado_en = ? WHERE id = ?');
    $st->execute([$estado, $respuesta !== '' ? mb_substr($respuesta, 0, 2000) : null, date('Y-m-d H:i:s'), $id]);
    if ($st->rowCount() === 0) json_error('Pedido no encontrado', 404);
    registrar('reclamo', $id, 'estado', $estado . ($respuesta !== '' ? ' · con respuesta' : ''));
    json_response(['ok' => true, 'message' => 'Pedido actualizado']);
}

// HISTORIAL de cambios (solo administrador)
if ($route === 'historial' && $method === 'GET') {
    exigir_acceso_reportes();
    $entidad = (string) ($_GET['entidad'] ?? '');
    $sql = 'SELECT id, entidad, entidad_id, accion, detalle, usuario, creado_en FROM historial';
    $pars = [];
    if (in_array($entidad, ['reporte', 'comentario', 'reclamo', 'sistema'], true)) { $sql .= ' WHERE entidad = ?'; $pars[] = $entidad; }
    if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
        $sql .= ($pars ? ' AND' : ' WHERE') . ' entidad_id = ?'; $pars[] = (int) $_GET['id'];
    }
    $st = db()->prepare($sql . ' ORDER BY id DESC LIMIT 300');
    $st->execute($pars);
    json_response(['ok' => true, 'data' => $st->fetchAll()]);
}

// Ruta desconocida
json_error('Ruta no encontrada. Ver /api/?route=', 404);