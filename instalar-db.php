<?php
/**
 * Instalador web TEMPORAL de la base (equivale a scripts/setup.php).
 * Uso: http://localhost/formosahack-2026/instalar-db.php?ok=1
 * Borrar o desactivar después de usar.
 */
header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/inc/config.php';

// Instalador destructivo: solo puede ejecutarse desde la máquina local.
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$local = in_array($remote, ['127.0.0.1', '::1'], true);
if (PHP_SAPI !== 'cli' && !$local) {
    http_response_code(403);
    echo "Instalador bloqueado: solo se permite desde localhost.\n";
    exit;
}
if (($_GET['ok'] ?? '') !== '1' && PHP_SAPI !== 'cli') {
    echo "Agregá ?ok=1 para ejecutar. Se borran y recrean las tablas.\n";
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', (int) DB_PORT);
if ($conn->connect_error) { echo "[ERROR] Conexión: " . $conn->connect_error . "\n"; exit; }
echo "[OK] Conectado a MySQL " . $conn->server_info . "\n";
$conn->set_charset('utf8mb4');

foreach (['database/schema.sql', 'database/seed.sql'] as $f) {
    $sql = file_get_contents(__DIR__ . '/' . $f);
    if (!$conn->multi_query($sql)) { echo "[ERROR] $f: " . $conn->error . "\n"; exit; }
    $n = 0;
    do { $n++; if ($r = $conn->store_result()) $r->free(); } while ($conn->more_results() && $conn->next_result());
    if ($conn->errno) { echo "[ERROR] $f (sentencia $n): " . $conn->error . "\n"; exit; }
    echo "[OK] $f ($n sentencias)\n";
}
$conn->select_db(DB_NAME);
foreach (['tipos_criadero', 'barrios', 'reportes', 'comentarios'] as $t) {
    $r = $conn->query("SELECT COUNT(*) FROM $t");
    echo "[OK] $t: " . ($r ? $r->fetch_row()[0] : 'ERROR ' . $conn->error) . " filas\n";
}
echo "\nListo. Abrí: " . BASE_URL . "/\n";
