<?php
/**
 * Pruebas de la API contra un servidor en marcha (crea y borra reportes de prueba).
 * ⚠️ Usar una base de PRUEBA, no la de producción.
 *
 *   php tests/api.php http://127.0.0.1:8000 [usuario] [contraseña]
 *   (por defecto: admin / admin123, o las variables TEST_USUARIO / TEST_CLAVE)
 */
require __DIR__ . '/lib.php';
require __DIR__ . '/../inc/riesgo.php';

$BASE    = rtrim($argv[1] ?? getenv('TEST_URL') ?: 'http://127.0.0.1:8000', '/');
$USUARIO = $argv[2] ?? getenv('TEST_USUARIO') ?: 'admin';
$CLAVE   = $argv[3] ?? getenv('TEST_CLAVE') ?: 'admin123';
$COOKIES = [];
$creados = [];
$MARCA   = 'PRUEBA-AUTO-' . date('His');

/** Pedido HTTP mínimo (sin curl). Devuelve [status, headers, cuerpo, json]. */
function pedir(string $metodo, string $ruta, $cuerpo = null, array $extra = []): array
{
    global $BASE, $COOKIES;
    $hdr = $extra;
    if ($COOKIES) $hdr[] = 'Cookie: ' . implode('; ', array_map(fn ($k, $v) => "$k=$v", array_keys($COOKIES), $COOKIES));
    if (is_array($cuerpo)) { $hdr[] = 'Content-Type: application/json'; $cuerpo = json_encode($cuerpo); }
    $ctx = stream_context_create(['http' => [
        'method' => $metodo, 'header' => implode("\r\n", $hdr), 'content' => $cuerpo ?? '',
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 60,
    ]]);
    $resp = @file_get_contents($BASE . '/' . ltrim($ruta, '/'), false, $ctx);
    $headers = $http_response_header ?? [];
    if ($resp === false && !$headers) throw new FallaPrueba("No responde el servidor en $BASE");
    preg_match('#^HTTP/\S+\s+(\d+)#', $headers[0] ?? '', $m);
    foreach ($headers as $h) {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $h, $c)) $COOKIES[$c[1]] = $c[2];
    }
    return [(int) ($m[1] ?? 0), $headers, (string) $resp, json_decode((string) $resp, true)];
}
function api(string $metodo, string $ruta, $cuerpo = null): array
{
    return pedir($metodo, 'api/index.php?route=' . $ruta, $cuerpo);
}
function cabecera(array $headers, string $nombre): string
{
    foreach ($headers as $h) if (stripos($h, $nombre . ':') === 0) return trim(substr($h, strlen($nombre) + 1));
    return '';
}
function reporte_base(array $extra = []): array
{
    global $MARCA;
    return array_merge(['tipo_id' => 1, 'barrio_id' => 1, 'titulo' => "$MARCA reporte",
                        'descripcion' => 'Creado por las pruebas automáticas', 'referencia' => 'Esquina de prueba', 'acepta' => true], $extra);
}

echo "Servidor: $BASE\n";

seccion('Datos públicos');
prueba('tipos de criadero', function () {
    [$st, , , $j] = api('GET', 'tipos');
    igual(200, $st); verdadero(count($j['data'] ?? []) > 0, 'no hay tipos');
});
prueba('barrios con posición en el plano', function () {
    [$st, , , $j] = api('GET', 'barrios');
    igual(200, $st); verdadero(count($j['data']) >= 1, 'no hay barrios');
    verdadero(isset($j['data'][0]['x'], $j['data'][0]['y']), 'faltan x/y');
});
prueba('semáforo de /stats coincide con el algoritmo (inc/riesgo.php)', function () {
    [$st, , , $j] = api('GET', 'stats');
    igual(200, $st);
    foreach ($j['data']['riesgo_barrios'] as $b) {
        igual(nivel_por_indice((int) $b['indice']), $b['nivel'], "barrio {$b['nombre']}");
    }
});

prueba('modo inmersivo y bienvenida', function () {
    [$st, , $html] = pedir('GET', 'inmersivo.php');
    igual(200, $st); verdadero(str_contains($html, 'data-js-escena'), 'falta la escena 3D');
    [, , $inicio] = pedir('GET', '');
    verdadero(str_contains($inicio, 'data-js-bienvenida'), 'el inicio no ofrece elegir modo');
    [, , $normal] = pedir('GET', '?modo=normal');
    verdadero(!str_contains($normal, 'data-js-bienvenida'), '?modo=normal no debería preguntar');
});
prueba('secciones dentro del modo inmersivo (?embed=1) sin cabecera ni pie', function () {
    foreach (['reportar.php', 'centros-salud.php', 'comentarios.php', 'test-sintomas.php'] as $p) {
        [$st, , $html] = pedir('GET', $p . '?embed=1');
        igual(200, $st, $p);
        verdadero(!str_contains($html, 'class="topbar"') && str_contains($html, 'class="embebido"'), "$p embebido");
    }
});

seccion('Privacidad y límites (Ley 25.326 / UNESCO 2021)');
prueba('página de privacidad con derechos, límites y formulario de reclamo', function () {
    [$st, , $html] = pedir('GET', 'privacidad.php');
    igual(200, $st);
    foreach (['25.326', 'art. 14', 'art. 16', 'art. 20', 'id="limites"', 'id="reclamo"', 'Anthropic', '90 días'] as $t) {
        verdadero(str_contains($html, $t), "falta \"$t\"");
    }
});
prueba('el formulario de reporte pide consentimiento', function () {
    [, , $html] = pedir('GET', 'reportar.php');
    verdadero(str_contains($html, 'name="acepta" required') && str_contains($html, 'privacidad.php'), 'sin casilla de consentimiento');
});
prueba('el mapa avisa que refleja reportes (incluye sin verificar)', function () {
    [, , $html] = pedir('GET', '?modo=normal');
    verdadero(str_contains($html, 'mapa-limite'), 'falta el aviso de límites del mapa');
});
prueba('sin clima real no se suma riesgo con datos de ejemplo', function () {
    [$st, , , $j] = pedir('GET', 'api/clima.php');
    igual(200, $st);
    if (($j['fuente'] ?? '') === 'offline') {
        igual(0, $j['bonus'], 'bonus');
        igual('sin-datos', $j['nivel'], 'nivel');
        igual(false, $j['datos_reales'], 'datos_reales');
    } else {
        verdadero($j['datos_reales'] === true, 'con datos reales debe decirlo');
    }
});

seccion('Reportar (público)');
prueba('sin aceptar el aviso de privacidad → 400', function () {
    [$st, , , $j] = api('POST', 'reportes', reporte_base(['acepta' => false]));
    igual(400, $st); verdadero(str_contains($j['error'] ?? '', 'privacidad'), 'mensaje');
});
prueba('con un teléfono en la descripción → 400', function () {
    [$st] = api('POST', 'reportes', reporte_base(['descripcion' => 'Llamen al 3704 123-456 del vecino']));
    igual(400, $st);
});
prueba('crear un reporte válido → 201', function () use (&$creados) {
    [$st, , , $j] = api('POST', 'reportes', reporte_base());
    igual(201, $st); verdadero(!empty($j['data']['id']), 'sin id');
    $creados[] = (int) $j['data']['id'];
    verdadero(!array_key_exists('descripcion', $j['data']), 'la respuesta no debería devolver datos de más');
});
prueba('falta el tipo → 400', function () {
    [$st] = api('POST', 'reportes', reporte_base(['tipo_id' => 0]));
    igual(400, $st);
});
prueba('ubicación fuera de El Colorado → 400', function () {
    [$st, , , $j] = api('POST', 'reportes', reporte_base(['lat' => -34.6, 'lng' => -58.4]));
    igual(400, $st); verdadero(str_contains($j['error'] ?? '', 'fuera de El Colorado'), 'mensaje inesperado');
});
prueba('ubicación dentro de El Colorado → 201', function () use (&$creados) {
    [$st, , , $j] = api('POST', 'reportes', reporte_base(['lat' => -26.3245, 'lng' => -59.3599]));
    igual(201, $st); $creados[] = (int) $j['data']['id'];
});
prueba('foto inválida → 400', function () {
    [$st] = api('POST', 'reportes', reporte_base(['foto' => 'data:image/jpeg;base64,AAAA']));
    igual(400, $st);
});
prueba('foto válida → 201', function () use (&$creados) {
    // JPEG 1×1 válido
    $jpg = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    [$st, , , $j] = api('POST', 'reportes', reporte_base(['foto' => $jpg, 'titulo' => $GLOBALS['MARCA'] . ' con foto']));
    igual(201, $st, $j['error'] ?? ''); $creados[] = (int) $j['data']['id'];
});
prueba('reenvío sin señal (mismo cliente_id) no duplica', function () use (&$creados) {
    $cid = 'prueba-' . bin2hex(random_bytes(8));
    [$s1, , , $j1] = api('POST', 'reportes', reporte_base(['cliente_id' => $cid]));
    [$s2, , , $j2] = api('POST', 'reportes', reporte_base(['cliente_id' => $cid]));
    igual(201, $s1); igual(200, $s2);
    igual(true, $j2['duplicado'] ?? false, 'duplicado');
    igual($j1['data']['id'], $j2['data']['id'], 'mismo id');
    $creados[] = (int) $j1['data']['id'];
});
prueba('cliente_id con caracteres raros → 400', function () {
    [$st] = api('POST', 'reportes', reporte_base(['cliente_id' => "x'; DROP TABLE reportes;--"]));
    igual(400, $st);
});

seccion('Comentarios y reclamos (públicos)');
$GLOBALS['comentario_prueba'] = null;
prueba('comentario con datos personales → 400', function () {
    [$st, , , $j] = api('POST', 'comentarios', ['barrio_id' => 1, 'tipo' => 'problema', 'texto' => 'El vecino de al lado, mail vecino@correo.com']);
    igual(400, $st); verdadero(str_contains($j['error'] ?? '', 'públicos'), 'mensaje');
});
prueba('comentario válido → 201', function () {
    [$st, , , $j] = api('POST', 'comentarios', ['barrio_id' => 1, 'tipo' => 'sugerencia', 'texto' => $GLOBALS['MARCA'] . ' comentario de prueba']);
    igual(201, $st); $GLOBALS['comentario_prueba'] = (int) $j['data']['id'];
});
prueba('pedido sin aceptar el aviso → 400', function () {
    igual(400, api('POST', 'reclamos', ['tipo' => 'supresion', 'detalle' => 'x'])[0]);
});
prueba('pedido de supresión → 201 con número', function () {
    [$st, , , $j] = api('POST', 'reclamos', ['tipo' => 'supresion', 'referencia' => '#1', 'detalle' => $GLOBALS['MARCA'] . ' borrar mi foto', 'acepta' => true]);
    igual(201, $st); verdadero(($j['data']['id'] ?? 0) > 0, 'sin número de pedido');
    $GLOBALS['reclamo_prueba'] = (int) $j['data']['id'];
});

seccion('Protegido sin sesión → 401');
foreach (['reportes', 'reportes-mapa', 'reportes-calor', 'reportes-excel', 'respaldo', 'reportes/1', 'reportes/1/foto', 'reclamos', 'historial'] as $r) {
    prueba("GET $r", fn () => igual(401, api('GET', $r)[0]));
}
prueba('PATCH estado sin sesión', fn () => igual(401, api('PATCH', 'reportes/1', ['estado' => 'controlado'])[0]));
prueba('DELETE sin sesión', fn () => igual(401, api('DELETE', 'reportes/1')[0]));
prueba('borrar comentario sin sesión', fn () => igual(401, api('DELETE', 'comentarios/' . (int) $GLOBALS['comentario_prueba'])[0]));
prueba('responder pedido sin sesión', fn () => igual(401, api('PATCH', 'reclamos/1', ['estado' => 'resuelto'])[0]));

seccion('Administrador');
prueba('contraseña incorrecta no da acceso', function () use ($USUARIO) {
    [, , $html] = pedir('GET', '?acceso=1');
    preg_match('/name="csrf" value="([^"]+)"/', $html, $m);
    pedir('POST', 'reportes-auth.php', http_build_query(['accion' => 'login', 'csrf' => $m[1] ?? '', 'usuario' => $USUARIO, 'contrasena' => 'incorrecta']),
          ['Content-Type: application/x-www-form-urlencoded']);
    igual(401, api('GET', 'reportes')[0]);
});
prueba('iniciar sesión', function () use ($USUARIO, $CLAVE) {
    [, , $html] = pedir('GET', '?acceso=1');
    verdadero(preg_match('/name="csrf" value="([^"]+)"/', $html, $m) === 1, 'no aparece el formulario de acceso');
    [$st, $h] = pedir('POST', 'reportes-auth.php', http_build_query(['accion' => 'login', 'csrf' => $m[1], 'usuario' => $USUARIO, 'contrasena' => $CLAVE]),
                      ['Content-Type: application/x-www-form-urlencoded']);
    igual(302, $st);
    verdadero(str_contains(cabecera($h, 'Location'), 'admin.php'), 'no redirige al panel (¿usuario/contraseña de prueba?)');
});
prueba('lista paginada', function () {
    [$st, , , $j] = api('GET', 'reportes&pagina=1&por_pagina=2');
    igual(200, $st); igual(2, count($j['data'])); igual(1, $j['meta']['pagina']);
    verdadero($j['meta']['total'] >= 2, 'total');
});
prueba('búsqueda encuentra los reportes de prueba', function () use (&$creados) {
    [, , , $j] = api('GET', 'reportes&por_pagina=50&q=' . urlencode($GLOBALS['MARCA']));
    verdadero(count($j['data']) >= count(array_unique($creados)), 'no aparecen todos');
});
prueba('foto del reporte (solo admin)', function () use (&$creados) {
    [, , , $j] = api('GET', 'reportes&por_pagina=50&q=' . urlencode($GLOBALS['MARCA'] . ' con foto'));
    $id = $j['data'][0]['id'];
    [$st, $h] = api('GET', "reportes/$id/foto");
    igual(200, $st); igual('image/jpeg', cabecera($h, 'Content-Type'));
});
prueba('pines GPS y mapa de calor', function () {
    [$s1, , , $m] = api('GET', 'reportes-mapa');
    [$s2, , , $c] = api('GET', 'reportes-calor');
    igual(200, $s1); igual(200, $s2);
    verdadero(count($m['data']) >= 1, 'sin pines');
    foreach ($c['data'] as $p) verdadero(in_array($p['estado'], ['pendiente', 'verificado'], true), 'el calor incluye controlados');
});
prueba('exportar a Excel', function () {
    [$st, $h, $bin] = api('GET', 'reportes-excel&estado=pendiente');
    igual(200, $st);
    verdadero(str_contains(cabecera($h, 'Content-Type'), 'spreadsheetml'), 'no es Excel');
    igual('PK', substr($bin, 0, 2), 'firma zip');
});
prueba('cambiar estado actualiza el semáforo', function () use (&$creados) {
    $id = $creados[0];
    [, , , $antes] = api('GET', 'stats');
    [$st] = api('PATCH', "reportes/$id", ['estado' => 'controlado']);
    igual(200, $st);
    [, , , $despues] = api('GET', 'stats');
    $act = fn ($s) => array_sum(array_map(fn ($b) => (int) $b['indice'], $s['data']['riesgo_barrios']));
    igual($act($antes) - 1, $act($despues), 'criaderos activos');
});
prueba('estado inválido → 400', fn () => igual(400, api('PATCH', 'reportes/' . $creados[0], ['estado' => 'inventado'])[0]));

seccion('Rendición de cuentas');
prueba('el cambio de estado queda en el registro con el usuario', function () use (&$creados, $USUARIO) {
    [$st, , , $j] = api('GET', 'historial&entidad=reporte&id=' . $creados[0]);
    igual(200, $st);
    $acciones = array_column($j['data'], 'accion');
    verdadero(in_array('estado', $acciones, true) && in_array('creado', $acciones, true), 'faltan entradas');
    $cambio = array_values(array_filter($j['data'], fn ($h) => $h['accion'] === 'estado'))[0];
    igual($USUARIO, $cambio['usuario'], 'usuario');
    verdadero(str_contains($cambio['detalle'], '→ controlado'), 'detalle del cambio');
});
prueba('pedidos: el equipo los ve y los responde', function () {
    [$st, , , $j] = api('GET', 'reclamos');
    igual(200, $st);
    verdadero(in_array($GLOBALS['reclamo_prueba'], array_map('intval', array_column($j['data'], 'id')), true), 'no aparece el pedido');
    igual(200, api('PATCH', 'reclamos/' . $GLOBALS['reclamo_prueba'], ['estado' => 'resuelto', 'respuesta' => 'Prueba automática'])[0]);
});
prueba('moderación: borrar un comentario y dejar constancia sin el texto', function () {
    $id = (int) $GLOBALS['comentario_prueba'];
    igual(200, api('DELETE', 'comentarios/' . $id . '&motivo=' . urlencode('Prueba automática'))[0]);
    [, , , $j] = api('GET', 'historial&entidad=comentario&id=' . $id);
    igual('borrado', $j['data'][0]['accion'] ?? '', 'registro');
    verdadero(!str_contains($j['data'][0]['detalle'], 'comentario de prueba'), 'el registro no debe guardar el texto borrado');
});

seccion('Limpieza');
prueba('borrar los reportes de prueba (con motivo en el registro)', function () use (&$creados) {
    foreach (array_unique($creados) as $id) igual(200, api('DELETE', "reportes/$id&motivo=" . urlencode('Limpieza de pruebas'))[0], "borrar $id");
    [, , , $h] = api('GET', 'historial&entidad=reporte&id=' . $creados[0]);
    verdadero(str_contains($h['data'][0]['detalle'] ?? '', 'Limpieza de pruebas'), 'el borrado no quedó registrado con su motivo');
    [, , , $j] = api('GET', 'reportes&por_pagina=50&q=' . urlencode($GLOBALS['MARCA']));
    igual(0, count($j['data']), 'quedaron reportes de prueba');
});

exit(resumen_pruebas());
