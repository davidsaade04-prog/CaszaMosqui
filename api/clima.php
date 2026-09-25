<?php
/**
 * CaszaMosqui — Alerta climática (FormosaHack 2026)
 * GET api/clima.php → JSON con lluvia, temperatura y nivel de riesgo climático.
 *
 * Fuente: Open-Meteo (gratis, sin API key).
 * Caché de 1 hora en /cache/clima.json. Si no hay internet, usa la última caché
 * y, si no existe, datos de ejemplo marcados como "sin conexión" (la demo nunca se cae).
 *
 * Modelo simplificado: el Aedes aegypti eclosiona tras lluvias y completa su ciclo
 * en ~7-10 días con temperaturas templadas/cálidas (óptimo aprox. 22-32 °C).
 */
declare(strict_types=1);
date_default_timezone_set('America/Argentina/Cordoba');
header('Content-Type: application/json; charset=utf-8');

const LAT        = -26.308;   // El Colorado, Formosa (verificar coordenadas)
const LON        = -59.372;
const UBICACION  = 'El Colorado, Formosa';
const CACHE_TTL  = 3600;      // 1 hora

$cacheDir  = __DIR__ . '/../cache';
$cacheFile = $cacheDir . '/clima.json';

function descargar(string $url): ?string
{
    $ctx = stream_context_create(['http' => ['timeout' => 6]]);
    $r = @file_get_contents($url, false, $ctx);
    if (is_string($r) && $r !== '') {
        return $r;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            // XAMPP en Windows suele no traer certificados CA; son datos públicos de clima.
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $r = curl_exec($ch);
        curl_close($ch);
        if (is_string($r) && $r !== '') {
            return $r;
        }
    }
    return null;
}

function suma(array $v): float
{
    return array_sum(array_map(fn($x) => (float)($x ?? 0), $v));
}

function evaluar(array $d, string $fuente): array
{
    $fechas = $d['daily']['time'];
    $lluvia = $d['daily']['precipitation_sum'];
    $tmax   = $d['daily']['temperature_2m_max'];
    $tmin   = $d['daily']['temperature_2m_min'];

    $hoy = array_search(date('Y-m-d'), $fechas, true);
    if ($hoy === false) {
        $hoy = 14; // past_days=14 → hoy es el índice 14
    }

    // Últimos 14 días (sin hoy), últimos 7 para temperatura, hoy + 3 días de pronóstico
    $lluvia14  = suma(array_slice($lluvia, max(0, $hoy - 14), min(14, $hoy)));
    $medias7   = [];
    for ($i = max(0, $hoy - 7); $i < $hoy; $i++) {
        if ($tmax[$i] !== null && $tmin[$i] !== null) {
            $medias7[] = ($tmax[$i] + $tmin[$i]) / 2;
        }
    }
    $tmedia7   = $medias7 ? array_sum($medias7) / count($medias7) : 0.0;
    $prevista3 = suma(array_slice($lluvia, $hoy, 4));

    // Puntaje climático 0-5 (independiente del semáforo de criaderos por barrio)
    $p = 0;
    if ($lluvia14 >= 50)      $p += 2;
    elseif ($lluvia14 >= 20)  $p += 1;
    if ($tmedia7 >= 22 && $tmedia7 <= 32)                              $p += 2;
    elseif (($tmedia7 >= 18 && $tmedia7 < 22) || ($tmedia7 > 32 && $tmedia7 <= 35)) $p += 1;
    if ($prevista3 >= 10)     $p += 1;

    $nivel = $p >= 4 ? 'alto' : ($p >= 2 ? 'medio' : 'bajo');
    $bonus = ['alto' => 2, 'medio' => 1, 'bajo' => 0][$nivel];

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
        'open-meteo' => 'Open-Meteo (datos en vivo)',
        'cache'      => 'Open-Meteo (última actualización guardada)',
        'offline'    => 'sin conexión · datos de ejemplo',
    ];

    return [
        'ok'                 => true,
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

// 1) Caché fresca
if (is_file($cacheFile) && time() - filemtime($cacheFile) < CACHE_TTL) {
    $d = json_decode((string)file_get_contents($cacheFile), true);
    if (isset($d['daily']['time'])) {
        echo json_encode(evaluar($d, 'open-meteo'), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2) Datos en vivo
$url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
    'latitude'      => LAT,
    'longitude'     => LON,
    'daily'         => 'precipitation_sum,temperature_2m_max,temperature_2m_min',
    'past_days'     => 14,
    'forecast_days' => 4,
    'timezone'      => 'America/Argentina/Cordoba',
]);
$raw = descargar($url);
$d   = $raw ? json_decode($raw, true) : null;

if (isset($d['daily']['time'])) {
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    @file_put_contents($cacheFile, $raw);
    echo json_encode(evaluar($d, 'open-meteo'), JSON_UNESCAPED_UNICODE);
    exit;
}

// 3) Sin internet: última caché aunque esté vieja, o datos de ejemplo
if (is_file($cacheFile)) {
    $d = json_decode((string)file_get_contents($cacheFile), true);
    if (isset($d['daily']['time'])) {
        echo json_encode(evaluar($d, 'cache'), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
echo json_encode(evaluar(datosEjemplo(), 'offline'), JSON_UNESCAPED_UNICODE);
