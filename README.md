# 🦟 CaszaMosqui — FormosaHack 2026

> **Repositorio GitHub:** `github.com/OcampoGuillermo/fsahackelcolorado1` (rama `main`)
> **Proyecto:** Vigilancia comunitaria contra los criaderos de mosquitos.
> **Desafío asignado:** dificultad para identificar situaciones que favorecen
> enfermedades transmitidas por mosquitos (área: **Salud**).
> **Stack:** HTML + CSS + JavaScript (vanilla) + **PHP 8** + **MySQL (MariaDB)** · XAMPP (Apache).

## 💡 Qué resuelve

La falta de información **accesible, organizada y comprensible** sobre los
criaderos de mosquitos dificulta la **prevención** del dengue/zika/chikungunya y
la **participación de la comunidad**. CaszaMosqui permite:

- 📝 **Reportar criaderos** (tipo, barrio, referencia y descripción).
- 🗺️ **Mapa de riesgo sobre el plano oficial de El Colorado** (28 barrios) con semáforo en vivo (100% offline).
- 🌧️ **Alerta climática:** lluvia y temperatura reales (Open-Meteo) que suben el riesgo de los barrios con criaderos.
- 🧭 **147 calles reales** sugeridas al reportar (según el barrio elegido).
- 📊 **KPIs y ranking** de criaderos más reportados.
- 🔎 **Gestión:** pendiente → verificado → controlado (con votos de respaldo).
- 📖 **Guía de prevención** + 🎯 **quiz de concientización** para la comunidad.

## 🚀 Puesta en marcha (XAMPP local)

```bash
# 1. Copiar a htdocs (o enlazar esta carpeta)
# 2. Con Apache y MySQL corriendo en XAMPP:
C:\xampp\php\php.exe scripts\setup.php
# 3. Abrir en el navegador:
http://localhost/formosahack-2026/
```

> Config: `inc/config.php` (XAMPP: usuario `root`, sin contraseña). La BD se llama `formosahack`.
> Plan B sin Apache: `php -S localhost:8000 -t .`

## 🌐 Versión online (Render)

La misma app corre en Render con **PostgreSQL**, sin tocar la versión de XAMPP:

- `Dockerfile` + `docker/`: PHP 8.2 + Apache; escucha en el puerto que asigna Render.
- `inc/config.render.php`: se copia como `inc/config.php` dentro del contenedor y lee todo de variables de entorno (`DATABASE_URL`, `AUTH_USERNAME`, `AUTH_PASSWORD_HASH`, `CLAUDE_API_KEY`).
- `database/schema.pgsql.sql` y `seed.pgsql.sql`: tablas y datos de ejemplo para PostgreSQL.
- `scripts/init-db-render.php`: al arrancar crea las tablas que falten y carga los datos de ejemplo solo si la base está vacía (nunca borra datos).
- `render.yaml`: blueprint opcional (base + web en un paso).

Cada `git push` a la rama `main` vuelve a publicar la web automáticamente.
Plan gratuito: la web "se duerme" tras 15 min sin visitas (tarda ~1 min en despertar) y la base gratuita vence a los 30 días.

## 🔐 Acceso a Reportes

Cualquier vecino puede **crear un reporte** sin usuario. La sección **Reportes** (listado, verificar, controlar, votar y eliminar) requiere iniciar sesión (sesión PHP con token CSRF; la API también rechaza estas operaciones sin sesión). Credenciales iniciales, definidas en `inc/config.php`:

- Usuario: `admin`
- Contraseña: `admin123`

Para cambiarla, generá un hash y reemplazá `AUTH_PASSWORD_HASH` en `inc/config.php`:

```bash
C:\xampp\php\php.exe -r "echo password_hash('TU_NUEVA_CONTRASENA', PASSWORD_DEFAULT), PHP_EOL;"
```

El listado muestra 4 criaderos por página (Anterior / Siguiente / números de página). La API devuelve los registros en `data` y la paginación en `meta` (`pagina`, `por_pagina`, `total`, `total_paginas`).

## 🔌 API REST

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/?route=tipos` | Tipos de criadero |
| GET | `/api/?route=barrios` | Barrios del mapa |
| GET | `/api/?route=reportes` | Criaderos paginados (filtros `tipo`, `barrio`, `estado`, `q`; `pagina`, `por_pagina`) · requiere sesión |
| POST | `/api/?route=reportes` | Reportar criadero |
| PATCH | `/api/?route=reportes/{id}` | Cambiar estado / sumar voto · requiere sesión |
| DELETE | `/api/?route=reportes/{id}` | Eliminar · requiere sesión |
| GET | `/api/?route=stats` | KPIs + índice de riesgo por barrio |
| GET | `/api/clima.php` | Lluvia, temperatura y nivel de riesgo climático |

## 📁 Estructura

```
formosahack-2026/
├── index.php              Dashboard (mapa, KPIs, reportes, formulario, quiz)
├── api/index.php          API REST (JSON, consultas preparadas)
├── api/clima.php          Alerta climática (Open-Meteo + caché)
├── inc/                   config.php · db.php (PDO) · helpers.php · auth.php
├── reportes-auth.php      Inicio/cierre de sesión de Reportes
├── assets/                css/ · js/app.js, clima.js, calles.js · img/plano-el-colorado.jpg
├── database/              schema.sql · seed.sql (datos de El Colorado)
├── scripts/setup.php      Reconstruye la BD en 1 comando
└── docs/                  DESAFIO.md · SOLUCION.md · DEMO.md · CALLES.md (para el jurado)
```

## 🛡️ Seguridad base
- PDO preparado (anti inyección SQL) en toda la API.
- Salida escapada con `e()` (anti XSS).
- `inc/config.php` no se sube a Git (se versiona `config.example.php`).

---

*Equipo CaszaMosqui — FormosaHack 2026*