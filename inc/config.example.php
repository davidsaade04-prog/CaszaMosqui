<?php
/**
 * Configuración del proyecto FormosaHack 2026.
 * Copia este archivo como `config.php` y ajusta los valores locales.
 */

// MySQL / MariaDB (XAMPP: root sin contraseña por defecto)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'formosahack');
define('DB_USER', 'root');
define('DB_PASS', '');

// App
define('APP_NAME', 'CaszaMosqui');
define('APP_TAGLINE', 'Vigilancia comunitaria contra los criaderos de mosquitos');
define('APP_VERSION', '1.0.0');

// URL base del proyecto dentro de htdocs (sin barra final)
define('BASE_URL', '/formosahack-2026');

// Acceso temporal a la sección Reportes.
// Credenciales iniciales: usuario `admin`, contraseña `admin123`.
// Para cambiar la contraseña, generar un hash con:
// C:\xampp\php\php.exe -r "echo password_hash('TU_NUEVA_CONTRASENA', PASSWORD_DEFAULT), PHP_EOL;"
// y reemplazar AUTH_PASSWORD_HASH por el resultado.
define('AUTH_USERNAME', 'admin');
define('AUTH_PASSWORD_HASH', '$2y$10$fap8ykp3Caa7RpqDs0HBiOZa2sc89Rpd3haniU4CQM5z.L6tHnEyq');
