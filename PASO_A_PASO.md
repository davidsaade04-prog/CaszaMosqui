# 🦟 CaszaMosqui — Cómo aplicar la actualización (≈10 min)

Este paquete trae **solo los 17 archivos que cambian o son nuevos**, con la misma estructura de carpetas
del proyecto. No trae `inc/config.php`, así que su configuración local no se toca.

## Paso 0 · Backup (30 seg)
En la carpeta del proyecto:
```bash
git add -A
git commit -m "Backup antes de integrar mapa + clima"
```
(o copiar la carpeta `formosahack-2026` a `formosahack-2026-backup`).

## Paso 1 · Copiar los archivos
1. Descomprimir el zip: aparece la carpeta `actualizacion-caszamosqui/`.
2. Entrar a esa carpeta, **seleccionar todo su contenido** (api, assets, database, docs, index.php, README.md, .gitignore, .htaccess).
3. Pegarlo dentro de `C:\xampp\htdocs\formosahack-2026\` → **"Reemplazar los archivos en el destino"**.

> Ojo: `.gitignore` y `.htaccess` son archivos ocultos. En el Explorador de Windows: Vista → "Elementos ocultos".

| Acción | Archivo |
|---|---|
| Reemplaza | `index.php` · `assets/js/app.js` · `database/schema.sql` · `database/seed.sql` · `README.md` · `.gitignore` · `.htaccess` · `docs/DEMO.md` · `docs/SOLUCION.md` · `docs/DESAFIO.md` |
| Nuevo | `api/clima.php` · `assets/js/clima.js` · `assets/js/calles.js` · `assets/css/clima.css` · `assets/css/mapa-plano.css` · `assets/img/plano-el-colorado.jpg` · `docs/CALLES.md` |
| No se toca | `api/index.php` · `assets/css/styles.css` · `inc/*` · `scripts/setup.php` |

## Paso 2 · Reconstruir la base de datos
Con Apache y MySQL en verde en el XAMPP Control Panel, desde la carpeta del proyecto:
```bash
C:\xampp\php\php.exe scripts\setup.php
```
Tiene que terminar con `[OK] Base 'formosahack' creada...`.
Esto **borra los reportes actuales** y carga: 6 tipos + 28 barrios del plano + 24 reportes demo con esquinas reales.

## Paso 3 · Verificar el clima
Abrir `http://localhost/formosahack-2026/api/clima.php`
- ✅ Dice `"fuente":"open-meteo"` → listo.
- ⚠️ Dice `"fuente":"offline"` → PHP no puede salir por HTTPS. Abrir `C:\xampp\php\php.ini`, quitar el `;` de
  `extension=openssl` y `extension=curl`, guardar y **reiniciar Apache**. Volver a probar.
  (Aun en offline la demo funciona, con datos de ejemplo y aclarándolo en pantalla.)

## Paso 4 · Probar en el navegador
Abrir `http://localhost/formosahack-2026/` y apretar **Ctrl + F5** (para que no use el JS/CSS viejo en caché).

Checklist:
- [ ] Arriba aparece el panel **🌧️ Alerta climática** con el gráfico de lluvia.
- [ ] El mapa muestra el **plano de El Colorado** con burbujas numeradas (El Arco y San Martín en rojo).
- [ ] Botones **Alto / Medio / Bajo** filtran los barrios del mapa.
- [ ] Clic en **El Arco** → la lista muestra sus 5 criaderos.
- [ ] **Verificar → Controlar** un reporte: baja el número del barrio en el mapa y suben los controlados.
- [ ] **👍** suma un voto · **✕** elimina (pide confirmación).
- [ ] En **+ Reportar**, al elegir un barrio, el campo Referencia sugiere sus calles.
- [ ] "Criaderos más reportados" muestra los nombres de los tipos.
- [ ] Quiz: al terminar, "Volver a intentar" reinicia.
- [ ] F12 → pestaña Consola: sin errores en rojo.

## Paso 5 · Subir a GitHub
```bash
git add -A
git commit -m "Mapa oficial de El Colorado, alerta climática, calles reales y fixes de botones"
git push
```

## Qué se arregló además (bugs que ya estaban)
1. **Verificar / Controlar / 👍 / ✕ no hacían nada**: los botones tienen `data-estado` sin valor y el código
   preguntaba por el valor (`""` = falso). Ahora usa `hasAttribute`.
2. **"Volver a intentar" del quiz** no funcionaba por lo mismo.
3. **"Criaderos más reportados" salía sin nombres**: la API devuelve `tipo` y el JS leía `nombre`.
4. **`renderMapaFiltros()` no existía** y tiraba un error en consola en cada carga.
5. **`.htaccess` no bloqueaba `inc/`, `database/` ni `scripts/`** porque la regla buscaba la ruta desde la raíz
   (`^/inc`) y el proyecto vive en `/formosahack-2026/`. Ahora sí (y también `cache/`).
6. **Barrios con nombres a corregir** en el seed anterior (M. M. Güemes → M. M. Giroldi, 82 → 382 Viviendas,
   El Surco → El Arco, Vialidad → Vial) y "Barrio Barrio X" en las tarjetas por el prefijo duplicado.
7. `x`/`y` de barrios pasan de `SMALLINT` a `DECIMAL(5,1)` para ubicar bien los puntos sobre el plano.

## Si algo falla
- **Mapa vacío o "Error en la API"** → ¿corrió el Paso 2? Probar `http://localhost/formosahack-2026/api/?route=stats`.
- **Se ve igual que antes** → Ctrl + F5, o revisar que `index.php` se haya reemplazado.
- **Botones siguen sin andar** → no se reemplazó `assets/js/app.js`.
- Cualquier otra cosa: F12 → Consola, copiar el error en rojo y pasármelo.
