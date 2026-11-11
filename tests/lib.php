<?php
/**
 * Mini framework de pruebas (sin dependencias): prueba(), igual(), verdadero().
 * Se ejecuta solo por línea de comandos.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo por línea de comandos.');
}

$GLOBALS['__pruebas'] = ['ok' => 0, 'fallas' => []];

final class FallaPrueba extends RuntimeException {}

function prueba(string $nombre, callable $fn): void
{
    try {
        $fn();
        $GLOBALS['__pruebas']['ok']++;
        echo "  \033[32m✓\033[0m $nombre\n";
    } catch (Throwable $e) {
        $GLOBALS['__pruebas']['fallas'][] = $nombre;
        echo "  \033[31m✗ $nombre\033[0m\n      → " . $e->getMessage() . "\n";
    }
}

function igual($esperado, $obtenido, string $msg = ''): void
{
    if ($esperado !== $obtenido) {
        throw new FallaPrueba(($msg ? "$msg: " : '') . 'se esperaba ' . var_export($esperado, true)
            . ' y se obtuvo ' . var_export($obtenido, true));
    }
}

function verdadero($cond, string $msg): void
{
    if (!$cond) throw new FallaPrueba($msg);
}

function seccion(string $titulo): void
{
    echo "\n\033[1m$titulo\033[0m\n";
}

function resumen_pruebas(): int
{
    $r = $GLOBALS['__pruebas'];
    $n = count($r['fallas']);
    echo "\n" . ($n === 0
        ? "\033[32m✔ {$r['ok']} pruebas OK\033[0m\n"
        : "\033[31m✘ $n de " . ($r['ok'] + $n) . " pruebas fallaron\033[0m\n");
    return $n === 0 ? 0 : 1;
}
