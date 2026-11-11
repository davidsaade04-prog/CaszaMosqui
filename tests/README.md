# Pruebas automáticas

| Archivo | Qué prueba | Necesita |
|---|---|---|
| `unit.php` | Algoritmo de riesgo (semáforo + clima) y el generador de Excel | Solo PHP |
| `riesgo.test.mjs` | Que el navegador (`assets/js/clima.js`) calcule el riesgo igual que el servidor | Node |
| `api.php` | La API completa: reportar, GPS, fotos, duplicados sin señal, accesos protegidos, login, panel, Excel, mapa de calor | Servidor en marcha con una **base de prueba** |

`casos-riesgo.json` tiene los casos del riesgo que usan las dos versiones (PHP y navegador).

## Correrlas en la PC

```bash
php tests/unit.php
node tests/riesgo.test.mjs
php tests/api.php http://localhost/formosahack-2026 admin admin123   # ⚠️ crea y borra reportes de prueba
```

`api.php` crea reportes marcados `PRUEBA-AUTO-…` y los borra al terminar. **No usarlo contra la base de producción.**

## En GitHub

`.github/workflows/pruebas.yml` las corre solas en cada cambio que se sube, con una base PostgreSQL
vacía. El resultado se ve en la pestaña **Actions** del repositorio (✓ verde o ✗ roja).
