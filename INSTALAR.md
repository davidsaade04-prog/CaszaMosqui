# CaszaMosqui — Instalación rápida (XAMPP)

1. Copiar esta carpeta a `C:\xampp\htdocs\formosahack-2026`
2. Copiar `inc/config.example.php` como `inc/config.php` (ahí se ajusta la base de datos y, opcionalmente, la clave de IA del chatbot).
3. Con Apache y MySQL encendidos en XAMPP: `C:\xampp\php\php.exe scripts\setup.php`
4. Abrir `http://localhost/formosahack-2026/`  ·  Modo demo sin límite diario: `http://localhost/formosahack-2026/?demo=1`

`inc/config.php` no viene en el zip a propósito: es la configuración local de cada compu y puede tener la clave de la API.

## Acceso a Reportes (login)
- Usuario `admin` · contraseña `admin123` (cambiala: ver README → "Acceso a Reportes").
- Si ya tenés un `inc/config.php` propio, **agregale** estas dos líneas (están en `config.example.php`):
  `define('AUTH_USERNAME', 'admin');` y `define('AUTH_PASSWORD_HASH', '...');`
  Sin ellas nadie puede iniciar sesión.
- Crear reportes sigue siendo público (sin usuario).
