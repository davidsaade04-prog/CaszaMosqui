<?php
/**
 * Configuración para la versión ONLINE (Render + PostgreSQL).
 * El Dockerfile copia este archivo como inc/config.php dentro del contenedor.
 * No contiene secretos: todo se lee de las variables de entorno del servicio en Render.
 *
 * Variables:
 *   DATABASE_URL        postgresql://usuario:clave@host:5432/base   (la da Render Postgres)
 *   AUTH_USERNAME       usuario del login de Reportes (por defecto: admin)
 *   AUTH_PASSWORD_HASH  hash generado con password_hash()
 *   CLAUDE_API_KEY      opcional: activa las respuestas con IA del chatbot
 *   BASE_URL            opcional: vacío si la app está en la raíz del dominio
 */

function env_casza(string $nombre, string $defecto = ''): string
{
    $v = getenv($nombre);
    return ($v === false || $v === '') ? $defecto : $v;
}

$url = parse_url(env_casza('DATABASE_URL', 'postgresql://postgres@127.0.0.1:5432/caszamosqui'));

define('DB_DRIVER', 'pgsql');
define('DB_HOST', $url['host'] ?? '127.0.0.1');
define('DB_PORT', (string) ($url['port'] ?? 5432));
define('DB_NAME', ltrim($url['path'] ?? '/caszamosqui', '/'));
define('DB_USER', rawurldecode($url['user'] ?? 'postgres'));
define('DB_PASS', rawurldecode($url['pass'] ?? ''));
// La URL interna de Render no usa SSL; la externa (host *.render.com) sí lo exige
define('DB_SSLMODE', env_casza('DB_SSLMODE', str_ends_with(DB_HOST, '.render.com') ? 'require' : 'prefer'));

// App
define('APP_NAME', 'CaszaMosqui');
define('APP_TAGLINE', 'Vigilancia comunitaria contra los criaderos de mosquitos');
define('APP_VERSION', '1.0.0');
define('BASE_URL', rtrim(env_casza('BASE_URL', ''), '/'));

// Chatbot "Mosqui"
define('CLAUDE_API_KEY', env_casza('CLAUDE_API_KEY'));
define('CLAUDE_MODEL', env_casza('CLAUDE_MODEL', 'claude-haiku-4-5-20251001'));

// Login de Reportes
define('AUTH_USERNAME', env_casza('AUTH_USERNAME', 'admin'));
define('AUTH_PASSWORD_HASH', env_casza('AUTH_PASSWORD_HASH', '$2y$10$fap8ykp3Caa7RpqDs0HBiOZa2sc89Rpd3haniU4CQM5z.L6tHnEyq'));

date_default_timezone_set('America/Argentina/Buenos_Aires');
