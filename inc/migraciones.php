<?php
/**
 * Migraciones del esquema (se aplican solas, sin borrar datos).
 *
 * v2 · GPS y fotos de reportes:
 *   - reportes.lat / reportes.lng  → ubicación exacta (opcional) del criadero
 *   - fotos_reporte                → una foto (opcional) por reporte, guardada en la base
 *     (el disco del servidor gratuito de Render se borra al reiniciar, la base no)
 *
 * v3 · App sin internet:
 *   - reportes.cliente_id → identificador que genera el celular para cada reporte.
 *     Si un reporte hecho sin señal se reenvía dos veces, el servidor lo guarda una sola vez.
 *
 * v4 · Rendición de cuentas y datos personales (Ley 25.326 / UNESCO 2021):
 *   - reportes.actualizado_en → fecha del último cambio de estado
 *   - historial               → quién cambió o borró qué y cuándo
 *   - reclamos                → pedidos de acceso, rectificación o supresión, y reclamos
 *
 * Funciona con MySQL/MariaDB (XAMPP) y PostgreSQL (Render).
 * db() la ejecuta una vez y deja una marca en cache/ para no repetirla en cada pedido.
 */

const ESQUEMA_VERSION = 4;

/** true si la tabla reportes ya existe (en una base recién creada todavía no hay tablas). */
function existe_tabla_reportes(PDO $pdo, string $driver): bool
{
    $sql = $driver === 'pgsql'
        ? "SELECT to_regclass('public.reportes') IS NOT NULL"
        : "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reportes'";
    return (bool) $pdo->query($sql)->fetchColumn();
}

function migrar_esquema(PDO $pdo, string $driver): void
{
    if ($driver === 'pgsql') {
        $pdo->exec('ALTER TABLE reportes ADD COLUMN IF NOT EXISTS lat DOUBLE PRECISION NULL');
        $pdo->exec('ALTER TABLE reportes ADD COLUMN IF NOT EXISTS lng DOUBLE PRECISION NULL');
        $pdo->exec('ALTER TABLE reportes ADD COLUMN IF NOT EXISTS cliente_id VARCHAR(40) NULL');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_reportes_cliente ON reportes (cliente_id)');
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
        // v4: se reutiliza el mismo SQL del esquema (todo es "IF NOT EXISTS")
        $sql = (string) file_get_contents(__DIR__ . '/../database/schema.pgsql.sql');
        $v4  = substr($sql, (int) strpos($sql, '-- v4:'));
        if ($v4 !== '') $pdo->exec($v4);
        return;
    }

    // MySQL / MariaDB: se consulta information_schema para no depender de "ADD COLUMN IF NOT EXISTS"
    $existe = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reportes' AND COLUMN_NAME = ?"
    );
    $columnas = ['lat' => 'DOUBLE NULL', 'lng' => 'DOUBLE NULL', 'cliente_id' => 'VARCHAR(40) NULL',
                 'actualizado_en' => 'TIMESTAMP NULL DEFAULT NULL'];
    foreach ($columnas as $col => $tipo) {
        $existe->execute([$col]);
        if ((int) $existe->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE reportes ADD COLUMN $col $tipo");
        }
    }
    $indice = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reportes' AND INDEX_NAME = 'ux_reportes_cliente'"
    )->fetchColumn();
    if ((int) $indice === 0) {
        $pdo->exec('CREATE UNIQUE INDEX ux_reportes_cliente ON reportes (cliente_id)');
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
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS historial (
           id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           entidad    VARCHAR(20)  NOT NULL,
           entidad_id INT UNSIGNED NULL,
           accion     VARCHAR(30)  NOT NULL,
           detalle    VARCHAR(255) NOT NULL DEFAULT '',
           usuario    VARCHAR(60)  NOT NULL DEFAULT '',
           creado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
           INDEX idx_historial_entidad (entidad, entidad_id),
           INDEX idx_historial_fecha (creado_en)
         ) ENGINE = InnoDB"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS reclamos (
           id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
           tipo           ENUM('acceso','rectificacion','supresion','error','comentario','otro') NOT NULL,
           referencia     VARCHAR(60)  NOT NULL DEFAULT '',
           detalle        TEXT         NOT NULL,
           contacto       VARCHAR(120) NOT NULL DEFAULT '',
           estado         ENUM('nuevo','en_curso','resuelto') NOT NULL DEFAULT 'nuevo',
           respuesta      TEXT         NULL,
           creado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
           actualizado_en TIMESTAMP    NULL DEFAULT NULL,
           INDEX idx_reclamo_estado (estado)
         ) ENGINE = InnoDB"
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
        if (!existe_tabla_reportes($pdo, $driver)) {
            return;   // base vacía: la crea scripts/setup.php o init-db-render.php
        }
        migrar_esquema($pdo, $driver);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($marca, date('c'));
    } catch (Throwable $e) {
        error_log('[CaszaMosqui] No se pudo migrar el esquema: ' . $e->getMessage());
    }
}
