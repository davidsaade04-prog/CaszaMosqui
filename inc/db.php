<?php
/**
 * Conexión PDO (una sola conexión reutilizable).
 * Consultas preparadas = protección base anti inyección SQL.
 *
 * Motores soportados (se elige con DB_DRIVER en inc/config.php):
 *   - 'mysql' (por defecto): MySQL / MariaDB en XAMPP.
 *   - 'pgsql': PostgreSQL (versión online en Render).
 */

require_once __DIR__ . '/config.php';

function db_driver(): string
{
    return defined('DB_DRIVER') && DB_DRIVER === 'pgsql' ? 'pgsql' : 'mysql';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (db_driver() === 'pgsql') {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
            if (defined('DB_SSLMODE') && DB_SSLMODE !== '') {
                $dsn .= ';sslmode=' . DB_SSLMODE;
            }
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        }
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            if (db_driver() === 'pgsql') {
                // Fechas en hora de Argentina (igual que en XAMPP)
                $pdo->exec("SET TIME ZONE 'America/Argentina/Buenos_Aires'");
                $pdo->exec("SET client_encoding TO 'UTF8'");
            }
        } catch (PDOException $e) {
            if (PHP_SAPI === 'cli') {
                throw $e;
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error' => 'No se pudo conectar a la base de datos. ' .
                           '¿Está MySQL corriendo y se ejecutó scripts/setup.php?',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    return $pdo;
}
