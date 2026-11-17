-- ============================================================
-- CaszaMosqui — Esquema de base de datos (versión PostgreSQL / Render)
-- Equivale a database/schema.sql (MySQL). No borra datos: solo crea lo que falta.
-- ============================================================

CREATE TABLE IF NOT EXISTS tipos_criadero (
  id     SERIAL PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  color  VARCHAR(7)  NOT NULL DEFAULT '#2563eb',
  icono  VARCHAR(8)  NOT NULL DEFAULT '🦟'
);

CREATE TABLE IF NOT EXISTS barrios (
  id        SERIAL PRIMARY KEY,
  nombre    VARCHAR(80)  NOT NULL UNIQUE,
  localidad VARCHAR(80)  NOT NULL DEFAULT 'El Colorado',
  x         DECIMAL(5,1) NULL,
  y         DECIMAL(5,1) NULL,
  poblacion INTEGER      NOT NULL DEFAULT 0 CHECK (poblacion >= 0)
);

CREATE TABLE IF NOT EXISTS reportes (
  id          SERIAL PRIMARY KEY,
  tipo_id     INTEGER      NOT NULL REFERENCES tipos_criadero (id) ON DELETE CASCADE,
  barrio_id   INTEGER      NOT NULL REFERENCES barrios (id) ON DELETE CASCADE,
  titulo      VARCHAR(120) NOT NULL,
  descripcion TEXT         NOT NULL,
  referencia  VARCHAR(120) NOT NULL DEFAULT '',
  estado      VARCHAR(12)  NOT NULL DEFAULT 'pendiente'
              CHECK (estado IN ('pendiente','verificado','controlado')),
  votos       INTEGER      NOT NULL DEFAULT 0 CHECK (votos >= 0),
  creado_en   TIMESTAMP(0) NOT NULL DEFAULT LOCALTIMESTAMP(0)
);
-- v2: ubicación GPS opcional (para bases creadas antes de esta versión)
ALTER TABLE reportes ADD COLUMN IF NOT EXISTS lat DOUBLE PRECISION NULL;
ALTER TABLE reportes ADD COLUMN IF NOT EXISTS lng DOUBLE PRECISION NULL;
-- v3: identificador del celular (evita duplicados al reenviar reportes hechos sin señal)
ALTER TABLE reportes ADD COLUMN IF NOT EXISTS cliente_id VARCHAR(40) NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_reportes_cliente ON reportes (cliente_id);
CREATE INDEX IF NOT EXISTS idx_reporte_tipo   ON reportes (tipo_id);
CREATE INDEX IF NOT EXISTS idx_reporte_barrio ON reportes (barrio_id);
CREATE INDEX IF NOT EXISTS idx_reporte_estado ON reportes (estado);

CREATE TABLE IF NOT EXISTS comentarios (
  id        SERIAL PRIMARY KEY,
  barrio_id INTEGER      NOT NULL REFERENCES barrios (id) ON DELETE CASCADE,
  tipo      VARCHAR(12)  NOT NULL DEFAULT 'sugerencia'
            CHECK (tipo IN ('sugerencia','problema','felicitacion','otro')),
  texto     TEXT         NOT NULL,
  creado_en TIMESTAMP(0) NOT NULL DEFAULT LOCALTIMESTAMP(0)
);
CREATE INDEX IF NOT EXISTS idx_comentario_barrio ON comentarios (barrio_id);
CREATE INDEX IF NOT EXISTS idx_comentario_fecha  ON comentarios (creado_en);

-- v2: una foto opcional por reporte (guardada en la base: el disco del servidor gratuito se borra)
CREATE TABLE IF NOT EXISTS fotos_reporte (
  reporte_id INTEGER PRIMARY KEY REFERENCES reportes (id) ON DELETE CASCADE,
  mime       VARCHAR(20)  NOT NULL,
  datos      BYTEA        NOT NULL,
  ancho      INTEGER      NOT NULL DEFAULT 0,
  alto       INTEGER      NOT NULL DEFAULT 0,
  bytes      INTEGER      NOT NULL DEFAULT 0,
  creado_en  TIMESTAMP(0) NOT NULL DEFAULT LOCALTIMESTAMP(0)
);

-- v4: rendición de cuentas (Ley 25.326 / UNESCO 2021)
-- Fecha del último cambio de estado: se usa para borrar foto y ubicación exacta 90 días después de controlado
ALTER TABLE reportes ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP(0) NULL;

-- Registro de cambios: quién hizo qué y cuándo (no se borra al borrar el reporte o comentario)
CREATE TABLE IF NOT EXISTS historial (
  id         SERIAL PRIMARY KEY,
  entidad    VARCHAR(20)  NOT NULL,             -- reporte | comentario | reclamo | sistema
  entidad_id INTEGER      NULL,
  accion     VARCHAR(30)  NOT NULL,             -- creado | estado | ubicacion | foto_borrada | borrado | ...
  detalle    VARCHAR(255) NOT NULL DEFAULT '',
  usuario    VARCHAR(60)  NOT NULL DEFAULT '',  -- usuario del panel; "público" o "sistema" si no hay sesión
  creado_en  TIMESTAMP(0) NOT NULL DEFAULT LOCALTIMESTAMP(0)
);
CREATE INDEX IF NOT EXISTS idx_historial_entidad ON historial (entidad, entidad_id);
CREATE INDEX IF NOT EXISTS idx_historial_fecha   ON historial (creado_en);

-- Reclamos y pedidos sobre datos personales (acceso, rectificación, supresión: arts. 14 y 16)
CREATE TABLE IF NOT EXISTS reclamos (
  id             SERIAL PRIMARY KEY,
  tipo           VARCHAR(20)  NOT NULL
                 CHECK (tipo IN ('acceso','rectificacion','supresion','error','comentario','otro')),
  referencia     VARCHAR(60)  NOT NULL DEFAULT '',
  detalle        TEXT         NOT NULL,
  contacto       VARCHAR(120) NOT NULL DEFAULT '',
  estado         VARCHAR(12)  NOT NULL DEFAULT 'nuevo' CHECK (estado IN ('nuevo','en_curso','resuelto')),
  respuesta      TEXT         NULL,
  creado_en      TIMESTAMP(0) NOT NULL DEFAULT LOCALTIMESTAMP(0),
  actualizado_en TIMESTAMP(0) NULL
);
CREATE INDEX IF NOT EXISTS idx_reclamo_estado ON reclamos (estado);
