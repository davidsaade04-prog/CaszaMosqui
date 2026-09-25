<?php
/**
 * Funciones auxiliares para la API y las vistas.
 */

require_once __DIR__ . '/db.php';

/** Responder JSON y terminar. */
function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Responder un error en JSON. */
function json_error(string $message, int $code = 400): void
{
    json_response(['ok' => false, 'error' => $message], $code);
}

/** Leer el cuerpo JSON de una petición. */
function body_json(): array
{
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Escapar para HTML (anti XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Normalizar un valor (para filtros) o devolver default. */
function val(?string $value, string $default = ''): string
{
    $value = trim((string) $value);
    return $value === '' ? $default : $value;
}

/** Redirigir. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}