<?php
/**
 * Inicializa la base PostgreSQL en Render (se ejecuta solo al arrancar el contenedor).
 * - Espera a que la base responda (hasta ~60 s).
 * - Crea las tablas que falten (database/schema.pgsql.sql).
 * - Si todavía no hay barrios, carga los datos de ejemplo (database/seed.pgsql.sql).
 * Nunca borra datos: se puede ejecutar en cada deploy sin riesgo.
 */
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';

$pdo = null;
for ($i = 1; $i <= 12; $i++) {
    try {
        $pdo = db();
        break;
    } catch (Throwable $e) {
        fwrite(STDERR, "[init-db] Base no disponible (intento $i/12): " . $e->getMessage() . "\n");
        sleep(5);
    }
}
if (!$pdo) {
    fwrite(STDERR, "[init-db] No se pudo conectar. La app arranca igual y reintentará en cada pedido.\n");
    exit(0);
}

$pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.pgsql.sql'));
echo "[init-db] Tablas verificadas.\n";

$barrios = (int) $pdo->query('SELECT COUNT(*) FROM barrios')->fetchColumn();
if ($barrios === 0) {
    $pdo->beginTransaction();
    $pdo->exec((string) file_get_contents(__DIR__ . '/../database/seed.pgsql.sql'));
    $pdo->commit();
    echo "[init-db] Datos de ejemplo cargados.\n";
} else {
    echo "[init-db] Ya hay $barrios barrios: no se cargan datos de ejemplo.\n";
}
