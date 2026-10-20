<?php
/**
 * Migraciones del esquema (se aplican solas, sin borrar datos).
 *
 * v2 · GPS y fotos de reportes:
 *   - reportes.lat / reportes.lng  → ubicación exacta (opcional) del criadero
 *   - fotos_reporte                → una foto (opcional) por reporte, guardada en la base
 *     (el disco del servidor gratuito de Render se borra al reiniciar, la base no)
 *
 * Funciona con MySQL/MariaDB (XAMPP) y PostgreSQL (Render).
 * db() la ejecuta una vez y deja una marca en cache/ para no repetirla en cada pedido.
 */

const ESQUEMA_VERSION = 2;

function migrar_esquema(PDO $pdo, string $driver): void
{
    if ($driver === 'pgsql') {
        $pdo->exec('ALTER TABLE reportes ADD COLUMN IF NOT EXISTS lat DOUBLE PRECISION NULL');
        $pdo->exec('ALTER TABLE reportes ADD COLUMN IF NOT EXISTS lng DOUBLE PRECISION NULL');
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS fotos_reporte (
               reporte_id INTEGER PRIMARY KEY REFERENCES reportes (id) ON DELETE CASCADE,
               mime       VARCHAR(20)  NOT NULL,
               datos      BYTEA        NOT NULL,
               ancho      INTEGER      NOT NULL DEFAULT 0,
               alto       INTEGER      NOT NULL DEFAULT 0,
               bytes      INTEGER      NOT NULL DEFAULT 0,
               creado_en  TIMESTAMP(0) NOT NULL DEFAULT LOCALTIMESTAMP(0)
             )'
        );
        return;
    }

    // MySQL / MariaDB: se consulta information_schema para no depender de "ADD COLUMN IF NOT EXISTS"
    $existe = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reportes' AND COLUMN_NAME = ?"
    );
    foreach (['lat', 'lng'] as $col) {
        $existe->execute([$col]);
        if ((int) $existe->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE reportes ADD COLUMN $col DOUBLE NULL");
        }
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS fotos_reporte (
           reporte_id INT UNSIGNED NOT NULL PRIMARY KEY,
           mime       VARCHAR(20)  NOT NULL,
           datos      MEDIUMBLOB   NOT NULL,
           ancho      INT UNSIGNED NOT NULL DEFAULT 0,
           alto       INT UNSIGNED NOT NULL DEFAULT 0,
           bytes      INT UNSIGNED NOT NULL DEFAULT 0,
           creado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
           CONSTRAINT fk_foto_reporte FOREIGN KEY (reporte_id) REFERENCES reportes (id) ON DELETE CASCADE
         ) ENGINE = InnoDB'
    );
}

/** Aplica la migración una sola vez por versión (marca en cache/). Nunca corta la app si falla. */
function asegurar_esquema(PDO $pdo, string $driver): void
{
    $dir   = __DIR__ . '/../cache';
    $marca = $dir . '/esquema-v' . ESQUEMA_VERSION . '-' . $driver . '.ok';
    if (is_file($marca)) {
        return;
    }
    try {
        migrar_esquema($pdo, $driver);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($marca, date('c'));
    } catch (Throwable $e) {
        error_log('[CaszaMosqui] No se pudo migrar el esquema: ' . $e->getMessage());
    }
}
