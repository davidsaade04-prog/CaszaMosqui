# 🧠 SOLUCION — CaszaMosqui

> **Propuesta técnica — FormosaHack 2026 · Desafío Socioambiental (Salud).**

## La propuesta

- **Nombre del producto:** CaszaMosqui 🦟
- **Qué hace (value proposition):** transforma los reportes de la comunidad en
  un **mapa de riesgo por barrio** y en acciones de control, con información
  simple y comprensible para prevenir el dengue, zika y chikungunya.
- **Cómo lo usa la comunidad:**
  1. El vecino reporta un criadero (tipo, barrio, referencia, descripción).
  2. El reporte aparece al instante en el **mapa de riesgo** del barrio.
  3. El equipo de salud/municipio **verifica** y luego **controla** el criadero.
  4. El barrio ve la mejora y aprende con la guía y el quiz.

## Arquitectura

- **Frontend:** HTML5 + CSS3 + JavaScript vanilla (fetch a la API REST).
- **Backend:** PHP 8 — API REST JSON (`api/index.php`, front controller).
- **Base de datos:** MySQL/MariaDB con **PDO** (consultas preparadas).
- **Despliegue:** Local con XAMPP (Apache) + repositorio GitHub.

```
Navegador (index.php)
   │  fetch() → JSON
   ▼
api/index.php  (router ?route=tipos|barrios|reportes|stats)
   │   PDO preparado
   ▼
MySQL (formosahack: tipos_criadero, barrios, reportes)
```

## Base de datos (3 tablas)

| Tabla | Campos | Uso |
|---|---|---|
| `tipos_criadero` | id, nombre, color, icono | Clasificación de criaderos (recipientes, neumáticos, piletas, zanjas, basurales, botellas) |
| `barrios` | id, nombre, localidad, x, y, poblacion | Barrios con posición esquemática para el mapa |
| `reportes` | id, tipo_id, barrio_id, titulo, descripcion, referencia, estado, votos, creado_en | Criaderos reportados por la comunidad |

## Mapa: plano oficial de El Colorado

- Fondo: **plano municipal de barrios actualizado** (28 barrios), guardado como imagen local → funciona sin internet.
- Cada barrio tiene su posición `x, y` (en %) sobre el plano, en la tabla `barrios`.
- **147 calles reales** por barrio (`assets/js/calles.js`, `docs/CALLES.md`): el campo "referencia" del formulario sugiere las calles del barrio elegido.

## Semáforo de riesgo por barrio

- **Activos** = criaderos en estado `pendiente` o `verificado` (sin controlar).
- El número del barrio muestra únicamente la cantidad de criaderos activos.
- **Nivel (semáforo):** 🔴 rojo = 5 o más activos · 🟠 amarillo = 3-4 activos · 🟢 verde = 0-2 activos.
- Al cambiar un reporte a `controlado`, deja de contar como activo y el número y color se actualizan al refrescar las estadísticas.
- La alerta climática (`api/clima.php`) se muestra como información independiente y no modifica el color del barrio.
- Los activos se calculan en `/api/?route=stats` y se actualizan en el mapa mediante `cargarStats()`.

## API REST

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/?route=tipos` | Tipos de criadero |
| GET | `/api/?route=barrios` | Barrios y posiciones del mapa |
| GET | `/api/?route=reportes` | Criaderos paginados (filtros: `tipo`, `barrio`, `estado`, `q`; `pagina`, `por_pagina`) |
| GET | `/api/?route=reportes/{id}` | Detalle |
| POST | `/api/?route=reportes` | Reportar criadero |
| PATCH | `/api/?route=reportes/{id}` | Cambiar estado o sumar voto |
| DELETE | `/api/?route=reportes/{id}` | Eliminar |
| GET | `/api/?route=stats` | KPIs + riesgo por barrio + tipos |

## Decisiones clave

| Decisión | Elegida | Por qué |
|---|---|---|
| Mapas | Plano oficial de barrios como imagen + posiciones x/y en % | Mapa real de El Colorado, 100% offline, demo garantizada |
| Clima | Open-Meteo (gratis, sin clave) con caché | Alerta anticipada según lluvia y temperatura reales |
| Seguridad | PDO preparado + `e()` anti-XSS | Protección básica demostrable |
| Participación | Reportes + votos + quiz | Involucra a la comunidad (requisito del desafío) |
| Despliegue | XAMPP + GitHub | Entregables pedidos por la organización |

## Riesgos y mitigación

| Riesgo | Mitigación |
|---|---|
| Sin internet en el evento | Única API externa (clima) con caché + datos de respaldo; el resto 100% local |
| MySQL no arranca | `scripts/setup.php` reconstruye la BD en 1 comando |
| Demo en vivo falla | Capturas + gif de respaldo en `docs/DEMO.md` |

---

*Equipo CaszaMosqui — FormosaHack 2026*