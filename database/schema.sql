-- ============================================================
-- CaszaMosqui — Esquema de base de datos
-- FormosaHack 2026 · Desafío Socioambiental: enfermedades por mosquitos
-- Motor: MySQL / MariaDB (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS formosahack
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE formosahack;

-- Re-ejecutable (idempotente): elimina y recrea tablas
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS reportes;
DROP TABLE IF EXISTS barrios;
DROP TABLE IF EXISTS tipos_criadero;

-- Tipos de situaciones que favorecen la proliferación de mosquitos
CREATE TABLE tipos_criadero (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80)  NOT NULL UNIQUE,
  color  VARCHAR(7)   NOT NULL DEFAULT '#2563eb',
  icono  VARCHAR(8)   NOT NULL DEFAULT '🦟'
) ENGINE = InnoDB;

-- Barrios de El Colorado ubicados sobre el plano oficial (assets/img/plano-el-colorado.jpg)
CREATE TABLE barrios (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(80) NOT NULL UNIQUE,
  localidad VARCHAR(80) NOT NULL DEFAULT 'El Colorado',
  x         DECIMAL(5,1) NULL,  -- posición % horizontal sobre el plano
  y         DECIMAL(5,1) NULL,  -- posición % vertical sobre el plano
  poblacion INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE = InnoDB;

-- Reportes de criaderos detectados por la comunidad
CREATE TABLE reportes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_id     INT UNSIGNED NOT NULL,
  barrio_id   INT UNSIGNED NOT NULL,
  titulo      VARCHAR(120) NOT NULL,
  descripcion TEXT         NOT NULL,
  referencia  VARCHAR(120) NOT NULL DEFAULT '', -- punto de referencia (calle, plaza…)
  estado      ENUM('pendiente','verificado','controlado') NOT NULL DEFAULT 'pendiente',
  votos       INT UNSIGNED NOT NULL DEFAULT 0,
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reporte_tipo   FOREIGN KEY (tipo_id)
    REFERENCES tipos_criadero (id) ON DELETE CASCADE,
  CONSTRAINT fk_reporte_barrio FOREIGN KEY (barrio_id)
    REFERENCES barrios (id) ON DELETE CASCADE,
  INDEX idx_reporte_tipo   (tipo_id),
  INDEX idx_reporte_barrio (barrio_id),
  INDEX idx_reporte_estado (estado)
) ENGINE = InnoDB;

-- Comentarios y sugerencias de la comunidad (1 por día por dispositivo controlado en cliente)
CREATE TABLE comentarios (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  barrio_id INT UNSIGNED NOT NULL,
  tipo      ENUM('sugerencia','problema','felicitacion','otro') NOT NULL DEFAULT 'sugerencia',
  texto     TEXT         NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comentario_barrio FOREIGN KEY (barrio_id)
    REFERENCES barrios (id) ON DELETE CASCADE,
  INDEX idx_comentario_barrio (barrio_id),
  INDEX idx_comentario_fecha (creado_en)
) ENGINE = InnoDB;