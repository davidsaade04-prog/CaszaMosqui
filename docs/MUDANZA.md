# Mudanza de la base de datos (Render gratuito)

La base PostgreSQL **gratuita** de Render vence **30 días después de creada**.
Después hay **14 días de gracia** y, si no se pasa a un plan pago, Render la borra con todos sus datos.
Esta guía permite "mudarse" a una base gratuita nueva **sin perder nada** (reportes, comentarios, fotos y ubicaciones).

> Base actual: `caszamosqui-db`, creada el 25/09/2026 → vence el **25/10/2026** (borrado: ~08/11/2026).
>
> ⚠️ **El respaldo hay que bajarlo ANTES del vencimiento**: una base gratuita vencida queda
> **inaccesible** (la web deja de mostrar datos y no se puede descargar nada) salvo que se pague.
> Lo ideal es hacer la mudanza 1 o 2 días antes de que venza.

## Paso a paso (10 minutos)

1. **Descargar el respaldo**
   Entrá al sitio → 🔐 Administrador → **Panel de reportes** → sección **🗄️ Respaldo de datos** →
   **⬇️ Descargar respaldo**. Se baja un archivo `caszamosqui-respaldo-AAAA-MM-DD-HHMM.json`. Guardalo.

2. **Crear la base nueva** en Render
   Dashboard → **New → Postgres** → nombre `caszamosqui-db-2` (o el mes), plan **Free**, región **Oregon**
   (la misma que la web). Esperá a que diga *Available*.
   Render permite **una sola base gratuita activa por cuenta**: si no deja crearla, primero hay que
   borrar la vieja (**¡solo después de tener el respaldo del paso 1 guardado!**). Entre el borrado y el
   paso 4 la web queda unos minutos sin datos.

3. **Conectar la web a la base nueva**
   En la base nueva → **Connections** → copiá la **Internal Database URL**.
   En el servicio web `caszamosqui` → **Environment** → editá `DATABASE_URL` → pegá la nueva → **Save, rebuild and deploy**.
   Al arrancar, la web crea las tablas solas (con datos de ejemplo).

4. **Restaurar el respaldo**
   Entrá de nuevo al Panel → **⬆️ Restaurar un respaldo…** → elegí el `.json` del paso 1 →
   escribí `RESTAURAR` para confirmar. Reemplaza los datos de ejemplo por los tuyos.

5. **Verificar** que estén los reportes, las fotos y el mapa. Recién ahí borrá la base vieja (si todavía existe).

## Notas

- El respaldo es un único archivo JSON con todas las tablas; las fotos van adentro (en base64).
- Restaurar es "todo o nada": si algo falla, no se cambia ningún dato.
- La contraseña del administrador **no** está en la base (es la variable `AUTH_PASSWORD_HASH` de Render), así que no cambia con la mudanza.
- Conviene descargar un respaldo de vez en cuando aunque no haya mudanza.
