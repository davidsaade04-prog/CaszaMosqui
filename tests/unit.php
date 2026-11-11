<?php
/**
 * Pruebas unitarias (no necesitan base de datos ni servidor).
 *   php tests/unit.php
 */
require __DIR__ . '/lib.php';
require __DIR__ . '/../inc/riesgo.php';
require __DIR__ . '/../inc/xlsx.php';

$casos = json_decode((string) file_get_contents(__DIR__ . '/casos-riesgo.json'), true);

seccion('Algoritmo de riesgo · semáforo por barrio');
foreach ($casos['nivel_por_indice'] as [$indice, $nivel]) {
    prueba("$indice criadero(s) activo(s) → $nivel", fn () => igual($nivel, nivel_por_indice($indice)));
}

seccion('Algoritmo de riesgo · clima');
foreach ($casos['puntaje_clima'] as $c) {
    prueba("lluvia {$c['lluvia14']} mm, {$c['temp']} °C, prevista {$c['prevista']} mm → {$c['puntaje']} pts ({$c['nivel']})", function () use ($c) {
        $p = puntaje_clima((float) $c['lluvia14'], (float) $c['temp'], (float) $c['prevista']);
        igual($c['puntaje'], $p, 'puntaje');
        igual($c['nivel'], nivel_clima($p), 'nivel');
        igual($c['bonus'], bonus_clima(nivel_clima($p)), 'bonus');
    });
}

seccion('Algoritmo de riesgo · barrio + clima');
foreach ($casos['riesgo_con_clima'] as $c) {
    prueba("{$c['activos']} activos + bonus {$c['bonus']} → {$c['total']} ({$c['nivel']})", function () use ($c) {
        igual(['total' => $c['total'], 'nivel' => $c['nivel']], riesgo_con_clima($c['activos'], $c['bonus']));
    });
}
prueba('el clima no crea riesgo donde no hay criaderos', fn () => igual(0, riesgo_con_clima(0, 2)['total']));

seccion('Exportación a Excel');
prueba('genera un .xlsx válido (zip con las partes de Excel)', function () {
    $x = new XlsxSimple();
    $x->hoja('Reportes', ['ID', 'Texto', 'Link'], [
        [1, 'Tacho <con> agua & "larvas" ñ', 'https://www.google.com/maps?q=-26.3,-59.36'],
        [2, null, null],
    ], ['links' => [2 => true]]);
    $x->hoja('Hoja/con:símbolos?', ['A'], [['b']]);
    $bin = $x->generar();
    igual('PK', substr($bin, 0, 2), 'firma zip');
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($tmp, $bin);
    if (class_exists('ZipArchive')) {
        $z = new ZipArchive();
        verdadero($z->open($tmp) === true, 'el zip no abre');
        foreach (['[Content_Types].xml', 'xl/workbook.xml', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml'] as $parte) {
            verdadero($z->locateName($parte) !== false, "falta $parte");
        }
        $hoja = $z->getFromName('xl/worksheets/sheet1.xml');
        verdadero(@simplexml_load_string($hoja) !== false, 'la hoja no es XML válido');
        verdadero(str_contains($hoja, '&lt;con&gt; agua &amp;'), 'el texto no se escapó');
        verdadero(str_contains($z->getFromName('xl/workbook.xml'), 'Hoja con símbolos'), 'no limpió el nombre de la hoja');
        $z->close();
    }
    unlink($tmp);
});

seccion('Centros de salud (inc/centros-salud.php)');
$centros = require __DIR__ . '/../inc/centros-salud.php';
prueba('cada centro tiene los datos que usa la página', function () use ($centros) {
    $ids = [];
    foreach ($centros as $c) {
        foreach (['id', 'nombre', 'tipo', 'direccion', 'horario_txt', 'telefonos'] as $k) verdadero(!empty($c[$k]) || $k === 'telefonos', "{$c['nombre']}: falta $k");
        verdadero(!isset($ids[$c['id']]), "id repetido {$c['id']}");
        $ids[$c['id']] = true;
    }
});
prueba('ubicaciones dentro de El Colorado', function () use ($centros) {
    foreach ($centros as $c) {
        if ($c['lat'] === null) { verdadero($c['lng'] === null, "{$c['nombre']}: lat sin lng"); continue; }
        verdadero($c['lat'] > -26.37 && $c['lat'] < -26.26 && $c['lng'] > -59.43 && $c['lng'] < -59.30, "{$c['nombre']} fuera de la zona");
    }
});
prueba('teléfonos marcables (+54…) y horarios bien escritos', function () use ($centros) {
    foreach ($centros as $c) {
        foreach ($c['telefonos'] as $t) verdadero(preg_match('/^\+54\d{10,11}$/', $t[2]) === 1, "{$c['nombre']}: número {$t[2]}");
        if (is_array($c['horario'])) foreach ($c['horario'] as $f) {
            verdadero(preg_match('/^\d\d:\d\d$/', $f['desde']) && preg_match('/^\d\d:\d\d$/', $f['hasta']) && $f['desde'] < $f['hasta'], "{$c['nombre']}: franja");
            verdadero(!array_diff($f['dias'], range(1, 7)), "{$c['nombre']}: días 1–7");
        } else verdadero($c['horario'] === '24h' || $c['horario'] === null, "{$c['nombre']}: horario");
    }
});

exit(resumen_pruebas());
