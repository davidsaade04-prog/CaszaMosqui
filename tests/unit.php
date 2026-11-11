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

exit(resumen_pruebas());
