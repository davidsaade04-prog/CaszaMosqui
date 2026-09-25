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
