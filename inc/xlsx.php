<?php
/**
 * Generador mínimo de planillas Excel (.xlsx) sin librerías externas.
 * Funciona igual en XAMPP y en Render (no necesita la extensión zip de PHP).
 *
 * Uso:
 *   $x = new XlsxSimple();
 *   $x->hoja('Reportes', ['ID', 'Barrio', …], $filas, ['anchos' => [8, 20, …], 'links' => [col => true]]);
 *   $x->descargar('reportes.xlsx');      // o $x->generar() para obtener el binario
 *
 * Cada fila es una lista de valores: int/float → número; string → texto;
 * null → celda vacía. Las celdas de una columna marcada en 'links' se escriben
 * como fórmula HYPERLINK (abre en Excel, LibreOffice y Google Sheets).
 */
class XlsxSimple
{
    private array $hojas = [];

    public function hoja(string $nombre, array $encabezados, array $filas, array $opciones = []): void
    {
        // Excel no permite estos caracteres en el nombre de la hoja (máx. 31)
        $nombre = mb_substr(preg_replace('#[\\\\/?*\[\]:]#u', ' ', $nombre), 0, 31);
        $this->hojas[] = compact('nombre', 'encabezados', 'filas', 'opciones');
    }

    public function descargar(string $archivo): void
    {
        $bin = $this->generar();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: no-store');
        echo $bin;
        exit;
    }

    public function generar(): string
    {
        $archivos = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels'         => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml'            => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml'              => $this->estilos(),
        ];
        foreach ($this->hojas as $i => $h) {
            $archivos['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $this->hojaXml($h);
        }
        return self::zip($archivos);
    }

    /* ---------------- XML ---------------- */

    private static function esc(string $t): string
    {
        // Quita caracteres de control que invalidan el XML
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $t) ?? '';
        return htmlspecialchars($t, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function columna(int $n): string   // 0 → A, 26 → AA
    {
        $s = '';
        for ($n++; $n > 0; $n = intdiv($n - 1, 26)) {
            $s = chr(65 + ($n - 1) % 26) . $s;
        }
        return $s;
    }

    private function contentTypes(): string
    {
        $hojas = '';
        foreach ($this->hojas as $i => $_) {
            $hojas .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $hojas . '</Types>';
    }

    private function workbook(): string
    {
        $hojas = '';
        $nombres = '';
        foreach ($this->hojas as $i => $h) {
            $hojas .= '<sheet name="' . self::esc($h['nombre']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
            $n = count($h['filas']) + 1;
            $ult = self::columna(count($h['encabezados']) - 1);
            // Rango del autofiltro (Excel lo necesita como nombre definido)
            $nombres .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">\''
                . self::esc(str_replace("'", "''", $h['nombre'])) . "'!\$A\$1:\$$ult\$$n</definedName>";
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $hojas . '</sheets><definedNames>' . $nombres . '</definedNames></workbook>';
    }

    private function workbookRels(): string
    {
        $r = '';
        foreach ($this->hojas as $i => $_) {
            $r .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $r .= '<Relationship Id="rId' . (count($this->hojas) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $r . '</Relationships>';
    }

    // Estilos: 0 normal · 1 encabezado (negrita, fondo verde, texto blanco) · 2 texto ajustado · 3 link azul
    private function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><u/><sz val="11"/><color rgb="FF0563C1"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF134E4A"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment vertical="top"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="top"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function hojaXml(array $h): string
    {
        $anchos = $h['opciones']['anchos'] ?? [];
        $links  = $h['opciones']['links'] ?? [];
        $largo  = $h['opciones']['ajustar'] ?? [];   // columnas con texto largo (se ajusta)
        $nCols  = count($h['encabezados']);
        $ult    = self::columna($nCols - 1);

        $cols = '';
        for ($c = 0; $c < $nCols; $c++) {
            $w = (float) ($anchos[$c] ?? 14);
            $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $filasXml = '<row r="1" ht="22" customHeight="1">';
        foreach ($h['encabezados'] as $c => $titulo) {
            $filasXml .= '<c r="' . self::columna($c) . '1" t="inlineStr" s="1"><is><t>' . self::esc((string) $titulo) . '</t></is></c>';
        }
        $filasXml .= '</row>';

        foreach (array_values($h['filas']) as $i => $fila) {
            $r = $i + 2;
            $filasXml .= '<row r="' . $r . '">';
            foreach (array_values($fila) as $c => $v) {
                if ($v === null || $v === '') continue;
                $ref = self::columna($c) . $r;
                if (!empty($links[$c]) && is_string($v)) {
                    $f = 'HYPERLINK("' . str_replace('"', '""', $v) . '","Ver en el mapa")';
                    $filasXml .= '<c r="' . $ref . '" t="str" s="3"><f>' . self::esc($f) . '</f><v>Ver en el mapa</v></c>';
                } elseif (is_int($v) || is_float($v)) {
                    $filasXml .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
                } else {
                    $s = !empty($largo[$c]) ? ' s="2"' : '';
                    $filasXml .= '<c r="' . $ref . '" t="inlineStr"' . $s . '><is><t xml:space="preserve">' . self::esc((string) $v) . '</t></is></c>';
                }
            }
            $filasXml .= '</row>';
        }

        $n = count($h['filas']) + 1;
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $filasXml . '</sheetData>'
            . '<autoFilter ref="A1:' . $ult . $n . '"/>'
            . '</worksheet>';
    }

    /* ---------------- ZIP (sin compresión, formato estándar) ---------------- */

    private static function zip(array $archivos): string
    {
        $datos = '';
        $central = '';
        $hora = (int) ((date('H') << 11) | (date('i') << 5) | intdiv((int) date('s'), 2));
        $fecha = (int) (((date('Y') - 1980) << 9) | (date('n') << 5) | date('j'));
        foreach ($archivos as $nombre => $contenido) {
            $crc = crc32($contenido);
            $tam = strlen($contenido);
            $offset = strlen($datos);
            // 0x0800 = nombres en UTF-8
            $datos .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam, strlen($nombre), 0)
                . $nombre . $contenido;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam,
                    strlen($nombre), 0, 0, 0, 0, 0, $offset)
                . $nombre;
        }
        return $datos . $central
            . pack('VvvvvVVv', 0x06054b50, 0, 0, count($archivos), count($archivos), strlen($central), strlen($datos), 0);
    }
}
