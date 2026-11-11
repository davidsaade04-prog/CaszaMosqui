<?php
/**
 * Algoritmo de riesgo de CaszaMosqui (un solo lugar, probado en tests/).
 *
 * Semáforo por barrio:
 *   índice = criaderos activos (pendientes + verificados) del barrio
 *   0–1 → bajo · 2–3 → medio · 4 o más → alto
 *
 * Clima (Open-Meteo, api/clima.php) → puntaje 0–5:
 *   lluvia acumulada 14 días: ≥ 50 mm +2 · ≥ 20 mm +1
 *   temperatura media 7 días: 22–32 °C +2 · 18–22 o 32–35 °C +1
 *   lluvia prevista 4 días: ≥ 10 mm +1
 *   puntaje 0–1 bajo · 2–3 medio · 4–5 alto → suma 0 / 1 / 2 al índice de los barrios
 *   que YA tienen criaderos (el clima potencia los criaderos, no los crea).
 * La misma regla está en assets/js/clima.js (riesgoConClima) para el navegador.
 */

const RIESGO_UMBRAL_MEDIO = 2;
const RIESGO_UMBRAL_ALTO  = 4;

function nivel_por_indice(int $indice): string
{
    if ($indice >= RIESGO_UMBRAL_ALTO)  return 'alto';
    if ($indice >= RIESGO_UMBRAL_MEDIO) return 'medio';
    return 'bajo';
}

function puntaje_clima(float $lluvia14, float $tempMedia7, float $lluviaPrevista): int
{
    $p = 0;
    if ($lluvia14 >= 50)     $p += 2;
    elseif ($lluvia14 >= 20) $p += 1;

    if ($tempMedia7 >= 22 && $tempMedia7 <= 32) {
        $p += 2;
    } elseif (($tempMedia7 >= 18 && $tempMedia7 < 22) || ($tempMedia7 > 32 && $tempMedia7 <= 35)) {
        $p += 1;
    }

    if ($lluviaPrevista >= 10) $p += 1;
    return $p;
}

function nivel_clima(int $puntaje): string
{
    return $puntaje >= 4 ? 'alto' : ($puntaje >= 2 ? 'medio' : 'bajo');
}

function bonus_clima(string $nivelClima): int
{
    return ['alto' => 2, 'medio' => 1, 'bajo' => 0][$nivelClima] ?? 0;
}

/** Riesgo final de un barrio con el clima: ['total' => int, 'nivel' => string]. */
function riesgo_con_clima(int $activos, int $bonus): array
{
    $total = $activos > 0 ? $activos + $bonus : 0;
    return ['total' => $total, 'nivel' => nivel_por_indice($total)];
}
