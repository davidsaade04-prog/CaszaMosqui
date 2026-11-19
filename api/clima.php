<?php
/**
 * CaszaMosqui — Alerta climática
 * GET api/clima.php → JSON con lluvia, temperatura y nivel de riesgo climático.
 *
 * Fuente: Open-Meteo (gratis, sin API key). Respaldo: MET Noruega (api.met.no), para cuando
 * Open-Meteo rechaza al servidor por el límite diario (en Render la dirección se comparte).
 * Condiciones actuales (temperatura, humedad, lluvia, viento) + historial de 14 días.
 * Caché de 10 minutos en /cache/clima.json. Si no hay internet, usa la última caché
 * y, si no existe, datos de ejemplo marcados como "sin conexión" (la demo nunca se cae).
 *
 * Modelo simplificado: el Aedes aegypti eclosiona tras lluvias y completa su ciclo
 * en ~7-10 días con temperaturas templadas/cálidas (óptimo aprox. 22-32 °C).
 */
declare(strict_types=1);
date_default_timezone_set('America/Argentina/Cordoba');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../inc/riesgo.php';

const LAT        = -26.308;   // El Colorado, Formosa (verificar coordenadas)
const LON        = -59.372;
const UBICACION  = 'El Colorado, Formosa';
const CACHE_TTL  = 600;       // 10 minutos (Open-Meteo actualiza las condiciones actuales cada 15 min)

$cacheDir  = __DIR__ . '/../cache';
$cacheFile = $cacheDir . '/clima.json';

function descargar(string $url): ?string
{
    // cURL primero: permite ver el código HTTP y el motivo cuando el servicio rechaza el pedido
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT      => 'CaszaMosqui/1.0 github.com/davidsaade04-prog/CaszaMosqui',
            // XAMPP en Windows suele no traer certificados CA; son datos públicos de clima.
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $r = curl_exec($ch);
        $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if (is_string($r) && $r !== '' && $codigo === 200) {
            return $r;
        }
        error_log(sprintf('clima: fallo al consultar %s · HTTP %d · %s · %s',
            parse_url($url, PHP_URL_HOST), $codigo, $error ?: 'sin error de red', substr(is_string($r) ? $r : '', 0, 200)));
    }
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'CaszaMosqui/1.0', 'ignore_errors' => false]]);
    $r = @file_get_contents($url, false, $ctx);
    if (is_string($r) && $r !== '') {
        return $r;
    }
    return null;
}

function suma(array $v): float
{
    return array_sum(array_map(fn($x) => (float)($x ?? 0), $v));
}

/** Descripción e ícono según el código meteorológico WMO que usa Open-Meteo. */
function describirTiempo(int $codigo, bool $dia): array
{
    $tabla = [
        0  => ['Despejado', $dia ? '☀️' : '🌙'],
        1  => ['Mayormente despejado', $dia ? '🌤️' : '🌙'],
        2  => ['Parcialmente nublado', '⛅'],
        3  => ['Nublado', '☁️'],
        45 => ['Niebla', '🌫️'], 48 => ['Niebla', '🌫️'],
        51 => ['Llovizna débil', '🌦️'], 53 => ['Llovizna', '🌦️'], 55 => ['Llovizna intensa', '🌧️'],
        56 => ['Llovizna helada', '🌧️'], 57 => ['Llovizna helada', '🌧️'],
        61 => ['Lluvia débil', '🌦️'], 63 => ['Lluvia', '🌧️'], 65 => ['Lluvia intensa', '🌧️'],
        66 => ['Lluvia helada', '🌧️'], 67 => ['Lluvia helada', '🌧️'],
        71 => ['Nieve', '🌨️'], 73 => ['Nieve', '🌨️'], 75 => ['Nieve', '🌨️'], 77 => ['Nieve', '🌨️'],
        80 => ['Chaparrones', '🌦️'], 81 => ['Chaparrones', '🌧️'], 82 => ['Chaparrones fuertes', '⛈️'],
        85 => ['Nieve', '🌨️'], 86 => ['Nieve', '🌨️'],
        95 => ['Tormenta', '⛈️'], 96 => ['Tormenta con granizo', '⛈️'], 99 => ['Tormenta con granizo', '⛈️'],
    ];
    return $tabla[$codigo] ?? ['Sin datos', '🌡️'];
}

/**
 * Sin datos reales del clima: no se suma riesgo con datos inventados (Jonas: anticipar el daño;
 * UNESCO 2021: decisiones con datos de calidad). Se muestra el aviso y bonus = 0.
 */
function sinDatosClima(array $r): array
{
    return array_merge($r, [
        'datos_reales'       => false,
        'actual'             => null,
        'nivel'              => 'sin-datos',
        'puntaje'            => 0,
        'bonus'              => 0,
        'lluvia_14d'         => null,
        'temp_media_7d'      => null,
        'lluvia_prevista_3d' => null,
        'serie'              => [],
        'mensaje'            => 'No pudimos obtener el clima real en este momento. El semáforo de los barrios se calcula solo con los reportes de los vecinos.',
        'recomendacion'      => 'Revisá tu patio una vez por semana: tapá, vaciá y dá vuelta todo lo que junte agua.',
        'fuente_texto'       => 'sin conexión con el servicio del clima',
    ]);
}

function evaluar(array $d, string $fuente): array
{
    $fechas = $d['daily']['time'];
    $lluvia = $d['daily']['precipitation_sum'];
    $tmax   = $d['daily']['temperature_2m_max'];
    $tmin   = $d['daily']['temperature_2m_min'];

    $hoy = array_search(date('Y-m-d'), $fechas, true);
    if ($hoy === false) {
        $hoy = min(14, count($fechas)); // past_days=14 → hoy es el índice 14
    }

    // Últimos 14 días (sin hoy), últimos 7 para temperatura, hoy + 3 días de pronóstico
    $lluvia14  = suma(array_slice($lluvia, max(0, $hoy - 14), min(14, $hoy)));
    $medias7   = [];
    for ($i = max(0, $hoy - 7); $i < $hoy; $i++) {
        if ($tmax[$i] !== null && $tmin[$i] !== null) {
            $medias7[] = ($tmax[$i] + $tmin[$i]) / 2;
        }
    }
    if (!$medias7) {
        // Sin días pasados (respaldo sin historial): se usa la temperatura prevista para hoy y los 2 días siguientes
        for ($i = $hoy; $i < min(count($fechas), $hoy + 3); $i++) {
            if ($tmax[$i] !== null && $tmin[$i] !== null) {
                $medias7[] = ($tmax[$i] + $tmin[$i]) / 2;
            }
        }
    }
    $tmedia7   = $medias7 ? array_sum($medias7) / count($medias7) : 0.0;
    $prevista3 = suma(array_slice($lluvia, $hoy, 4));

    // Puntaje climático 0-5 (reglas en inc/riesgo.php)
    $p     = puntaje_clima((float) $lluvia14, (float) $tmedia7, (float) $prevista3);
    $nivel = nivel_clima($p);
    $bonus = bonus_clima($nivel);

    $mensajes = [
        'alto'  => 'La lluvia y la temperatura de los últimos días favorecen la eclosión de huevos y el desarrollo de larvas del Aedes aegypti. Esta semana cada criadero reportado es más peligroso.',
        'medio' => 'Las condiciones climáticas son moderadamente favorables para el mosquito. Conviene no dejar pasar los criaderos pendientes.',
        'bajo'  => 'El clima de estos días es poco favorable para el mosquito, pero los huevos sobreviven meses secos: la prevención sigue siendo clave.',
    ];

    if ($prevista3 >= 10) {
        $reco = 'Se esperan lluvias: después de cada lluvia, vaciá y dá vuelta los recipientes que queden al aire libre.';
    } elseif ($nivel === 'alto') {
        $reco = 'Priorizar la descacharrización en los barrios en rojo durante esta semana.';
    } else {
        $reco = 'Revisá tu patio una vez por semana: tapá, vaciá y dá vuelta todo lo que junte agua.';
    }

    $serie = [];
    for ($i = max(0, $hoy - 14); $i < $hoy; $i++) {
        $serie[] = ['fecha' => $fechas[$i], 'lluvia' => round((float)($lluvia[$i] ?? 0), 1)];
    }

    $textos = [
        'open-meteo'  => 'Open-Meteo (datos en vivo, se actualiza cada 10 min)',
        'cache'       => 'Open-Meteo (última actualización guardada)',
        'met'         => 'MET Noruega (en vivo) + lluvia de los últimos días guardada',
        'met-parcial' => 'MET Noruega (en vivo, sin registro de la lluvia de los últimos días)',
        'offline'     => 'sin conexión · datos de ejemplo',
    ];

    // Condiciones actuales (solo con datos reales de Open-Meteo)
    $actual = null;
    if (isset($d['current']['temperature_2m'])) {
        $c = $d['current'];
        [$desc, $icono] = describirTiempo((int)($c['weather_code'] ?? -1), (bool)($c['is_day'] ?? 1));
        $actual = [
            'hora'        => substr((string)($c['time'] ?? ''), 11, 5),
            'temperatura' => round((float)$c['temperature_2m'], 1),
            'sensacion'   => round((float)($c['apparent_temperature'] ?? $c['temperature_2m']), 1),
            'humedad'     => (int)round((float)($c['relative_humidity_2m'] ?? 0)),
            'lluvia'      => round((float)($c['precipitation'] ?? 0), 1),
            'viento'      => (int)round((float)($c['wind_speed_10m'] ?? 0)),
            'descripcion' => $desc,
            'icono'       => $icono,
            'lloviendo'   => (float)($c['precipitation'] ?? 0) > 0,
        ];
    }

    return [
        'ok'                 => true,
        'datos_reales'       => $fuente !== 'offline',
        'actual'             => $actual,
        'consultado'         => date('H:i'),
        'fuente'             => $fuente,
        'fuente_texto'       => $textos[$fuente],
        'ubicacion'          => UBICACION,
        'lluvia_14d'         => round($lluvia14, 1),
        'temp_media_7d'      => round($tmedia7, 1),
        'lluvia_prevista_3d' => round($prevista3, 1),
        'puntaje'            => $p,
        'nivel'              => $nivel,
        'bonus'              => $bonus,
        'mensaje'            => $mensajes[$nivel],
        'recomendacion'      => $reco,
        'serie'              => $serie,
    ];
}

function datosEjemplo(): array
{
    $time = $lluvia = $tmax = $tmin = [];
    $ll = [0, 0, 12, 25, 3, 0, 0, 0, 8, 18, 0, 0, 0, 2, 0, 6, 14, 0];
    for ($i = 0; $i < 18; $i++) {
        $time[]   = date('Y-m-d', strtotime(($i - 14) . ' days'));
        $lluvia[] = $ll[$i];
        $tmax[]   = 30;
        $tmin[]   = 19;
    }
    return ['daily' => [
        'time' => $time, 'precipitation_sum' => $lluvia,
        'temperature_2m_max' => $tmax, 'temperature_2m_min' => $tmin,
    ]];
}

/* =========================================================
   Caché persistente: archivo + base de datos. En Render el disco se borra cada vez
   que el servidor se reinicia, así que la base guarda la última consulta buena.
   ========================================================= */
function cache_db(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    $pdo = null;
    try {
        require_once __DIR__ . '/../inc/db.php';
        $c = db();
        $c->exec('CREATE TABLE IF NOT EXISTS clima_cache (clave VARCHAR(30) PRIMARY KEY, datos TEXT NOT NULL, guardado BIGINT NOT NULL)');
        $pdo = $c;
    } catch (Throwable $e) {
        error_log('clima: sin base para la caché · ' . $e->getMessage());
    }
    return $pdo;
}
/** @return array{0: array, 1: int}|null  [datos, antigüedad en segundos] */
function leer_cache(string $clave): ?array
{
    global $cacheDir;
    $archivo = "$cacheDir/clima-$clave.json";
    if (is_file($archivo)) {
        $d = json_decode((string)file_get_contents($archivo), true);
        if (is_array($d)) {
            return [$d, time() - filemtime($archivo)];
        }
    }
    if ($db = cache_db()) {
        try {
            $st = $db->prepare('SELECT datos, guardado FROM clima_cache WHERE clave = ?');
            $st->execute([$clave]);
            if ($f = $st->fetch()) {
                $d = json_decode((string)$f['datos'], true);
                if (is_array($d)) {
                    return [$d, time() - (int)$f['guardado']];
                }
            }
        } catch (Throwable $e) { /* sin caché */ }
    }
    return null;
}
function guardar_cache(string $clave, array $d): void
{
    global $cacheDir;
    $json = json_encode($d, JSON_UNESCAPED_UNICODE);
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    @file_put_contents("$cacheDir/clima-$clave.json", $json);
    if ($db = cache_db()) {
        try {
            $db->beginTransaction();
            $db->prepare('DELETE FROM clima_cache WHERE clave = ?')->execute([$clave]);
            $db->prepare('INSERT INTO clima_cache (clave, datos, guardado) VALUES (?, ?, ?)')->execute([$clave, $json, time()]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        }
    }
}
function responder(array $r): void
{
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================================================
   Respaldo: MET Noruega (api.met.no, gratis, sin clave). Da las condiciones actuales y el
   pronóstico, pero no la lluvia de los días pasados: esa se toma de la última consulta
   guardada de Open-Meteo si la hay.
   ========================================================= */
function codigo_desde_simbolo(string $s): int
{
    $s = preg_replace('/_(day|night|polartwilight)$/', '', $s);
    if (str_contains($s, 'thunder')) return 95;
    if (str_contains($s, 'sleet')) return 67;
    if (str_contains($s, 'snow')) return 73;
    if (str_starts_with($s, 'heavyrainshowers')) return 82;
    if (str_starts_with($s, 'lightrainshowers')) return 80;
    if (str_starts_with($s, 'rainshowers')) return 81;
    if (str_starts_with($s, 'heavyrain')) return 65;
    if (str_starts_with($s, 'lightrain')) return 61;
    if (str_starts_with($s, 'rain')) return 63;
    return ['clearsky' => 0, 'fair' => 1, 'partlycloudy' => 2, 'cloudy' => 3, 'fog' => 45][$s] ?? 3;
}
function datos_met(?array $historial): ?array
{
    $raw = descargar('https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=' . round(LAT, 3) . '&lon=' . round(LON, 3));
    $m = $raw ? json_decode($raw, true) : null;
    $serie = $m['properties']['timeseries'] ?? null;
    if (!$serie) {
        return null;
    }
    $zona = new DateTimeZone('America/Argentina/Cordoba');
    $dias = [];
    foreach ($serie as $p) {
        $f = (new DateTime($p['time']))->setTimezone($zona)->format('Y-m-d');
        $det = $p['data']['instant']['details'] ?? [];
        $ll = $p['data']['next_1_hours']['details']['precipitation_amount']
            ?? $p['data']['next_6_hours']['details']['precipitation_amount'] ?? 0;
        $dias[$f] ??= ['lluvia' => 0.0, 'max' => null, 'min' => null];
        $dias[$f]['lluvia'] += (float)$ll;
        if (isset($det['air_temperature'])) {
            $t = (float)$det['air_temperature'];
            $dias[$f]['max'] = $dias[$f]['max'] === null ? $t : max($dias[$f]['max'], $t);
            $dias[$f]['min'] = $dias[$f]['min'] === null ? $t : min($dias[$f]['min'], $t);
        }
    }
    $hoy = date('Y-m-d');
    $time = $lluvia = $tmax = $tmin = [];
    // días pasados: de la última consulta de Open-Meteo guardada (si existe)
    if ($historial && isset($historial['daily']['time'])) {
        foreach ($historial['daily']['time'] as $k => $f) {
            if ($f < $hoy && $f >= date('Y-m-d', strtotime('-14 days'))) {
                $time[] = $f; $lluvia[] = $historial['daily']['precipitation_sum'][$k];
                $tmax[] = $historial['daily']['temperature_2m_max'][$k]; $tmin[] = $historial['daily']['temperature_2m_min'][$k];
            }
        }
    }
    $pasados = count($time);
    foreach ($dias as $f => $v) {
        if ($f < $hoy || count($time) >= $pasados + 4) {
            continue;
        }
        $time[] = $f; $lluvia[] = round($v['lluvia'], 1); $tmax[] = $v['max']; $tmin[] = $v['min'];
    }
    $a = $serie[0]['data'];
    $simbolo = $a['next_1_hours']['summary']['symbol_code'] ?? $a['next_6_hours']['summary']['symbol_code'] ?? 'cloudy';
    $temp = (float)($a['instant']['details']['air_temperature'] ?? 0);
    return [
        'daily' => ['time' => $time, 'precipitation_sum' => $lluvia, 'temperature_2m_max' => $tmax, 'temperature_2m_min' => $tmin],
        'current' => [
            'time' => (new DateTime($serie[0]['time']))->setTimezone($zona)->format('Y-m-d\TH:i'),
            'temperature_2m' => $temp, 'apparent_temperature' => $temp,
            'relative_humidity_2m' => $a['instant']['details']['relative_humidity'] ?? 0,
            'precipitation' => $a['next_1_hours']['details']['precipitation_amount'] ?? 0,
            'weather_code' => codigo_desde_simbolo($simbolo),
            'wind_speed_10m' => (float)($a['instant']['details']['wind_speed'] ?? 0) * 3.6,
            'is_day' => str_ends_with($simbolo, '_night') ? 0 : 1,
        ],
        'sin_historial' => $pasados === 0,
    ];
}

// 1) Caché fresca (de la fuente que haya respondido último)
if ($c = leer_cache('ultimo')) {
    [$d, $edad] = $c;
    if ($edad < CACHE_TTL && isset($d['datos']['daily']['time'])) {
        responder(evaluar($d['datos'], $d['fuente']));
    }
}

// 2) Open-Meteo en vivo (si no nos rechazó por límite en la última media hora)
$bloqueo = leer_cache('bloqueo-open-meteo');
if (!$bloqueo || $bloqueo[1] > 1800) {
    $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude'      => LAT,
        'longitude'     => LON,
        'daily'         => 'precipitation_sum,temperature_2m_max,temperature_2m_min',
        'current'       => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m,is_day',
        'past_days'     => 14,
        'forecast_days' => 4,
        'timezone'      => 'America/Argentina/Cordoba',
    ]);
    $raw = descargar($url);
    $d   = $raw ? json_decode($raw, true) : null;
    if (isset($d['daily']['time'])) {
        guardar_cache('open-meteo', $d);
        guardar_cache('ultimo', ['fuente' => 'open-meteo', 'datos' => $d]);
        responder(evaluar($d, 'open-meteo'));
    }
    if (isset($d['error'])) {
        // límite diario del servicio (el servidor gratuito comparte dirección con otros sitios)
        guardar_cache('bloqueo-open-meteo', ['motivo' => $d['reason'] ?? 'error']);
    }
}

// 3) Última consulta de Open-Meteo de menos de 3 horas
$om = leer_cache('open-meteo');
if ($om && $om[1] < 3 * 3600 && isset($om[0]['daily']['time'])) {
    responder(evaluar($om[0], 'cache'));
}

// 4) Respaldo: MET Noruega (actual + pronóstico), con la lluvia pasada de la última consulta guardada
$historial = ($om && $om[1] < 14 * 86400) ? $om[0] : null;
$met = datos_met($historial);
if ($met && $met['daily']['time']) {
    $fuente = $met['sin_historial'] ? 'met-parcial' : 'met';
    guardar_cache('ultimo', ['fuente' => $fuente, 'datos' => $met]);
    responder(evaluar($met, $fuente));
}

// 5) Sin ninguna fuente: última consulta de Open-Meteo de menos de 48 h. Si no, NO se inventa
//    el clima: se avisa que no hay datos y el semáforo se calcula solo con los reportes (bonus 0).
if ($om && $om[1] < 48 * 3600 && isset($om[0]['daily']['time'])) {
    responder(evaluar($om[0], 'cache'));
}
responder(sinDatosClima(evaluar(datosEjemplo(), 'offline')));
