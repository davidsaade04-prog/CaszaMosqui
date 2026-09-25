<?php
/**
 * Setup de la base de datos — FormosaHack 2026
 * ============================================
 * Crea la base `formosahack`, las tablas y carga datos de ejemplo.
 *
 * Uso (desde la raíz del proyecto, con PHP de XAMPP):
 *   C:\xampp\php\php.exe scripts\setup.php
 *
 * Si MySQL tiene contraseña de root, ajústala abajo.
 */

error_reporting(E_ALL);

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$db   = 'formosahack';

echo "== Setup CaszaMosqui (FormosaHack 2026) ==\n";

$conn = @new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    fwrite(STDERR, "[ERROR] No se pudo conectar a MySQL/MariaDB\n");
    fwrite(STDERR, "        -> ¿Está MySQL corriendo en XAMPP Control Panel?\n");
    fwrite(STDERR, "        -> Detalle: " . $conn->connect_error . "\n");
    exit(1);
}
echo "[OK] Conexión a MySQL (servidor: " . $conn->server_info . ")\n";

// IMPORTANTE: forzar UTF-8 en la conexión para que los acentos se guarden bien
if (!$conn->set_charset('utf8mb4')) {
    fwrite(STDERR, "[ERROR] No se pudo establecer el charset utf8mb4: " . $conn->error . "\n");
    exit(1);
}
echo "[OK] Charset de conexión: utf8mb4\n";

$archivos = [
    'database/schema.sql',
    'database/seed.sql',
];

foreach ($archivos as $archivo) {
    $ruta = __DIR__ . '/../' . $archivo;
    if (!is_file($ruta)) {
        fwrite(STDERR, "[ERROR] No existe $ruta\n");
        exit(1);
    }
    $sql = file_get_contents($ruta);
    if ($conn->multi_query($sql)) {
        while ($conn->more_results() && $conn->next_result()) {
            // consumir resultados intermedios
        }
        echo "[OK] $archivo\n";
    } else {
        fwrite(STDERR, "[ERROR] Falló $archivo: " . $conn->error . "\n");
        exit(1);
    }
}

echo "[OK] Base '$db' creada con tablas y datos de ejemplo.\n";
echo "\nAhora abrí en el navegador: http://localhost/formosahack-2026/\n";
echo "o probá la API: http://localhost/formosahack-2026/api/?route=stats\n";

$conn->close();