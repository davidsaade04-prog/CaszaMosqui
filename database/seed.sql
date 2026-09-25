-- ============================================================
-- CaszaMosqui — Datos de ejemplo (seed)
-- FormosaHack 2026 · El Colorado, Formosa
-- Barrios según el "Plano de barrios actualizados" del municipio.
-- x, y = posición en % sobre assets/img/plano-el-colorado.jpg
-- poblacion = 0 porque el plano no la informa.
-- Reportes demo con esquinas reales (ver docs/CALLES.md).
-- ============================================================
USE formosahack;
SET NAMES utf8mb4;

-- Tipos de criadero (clasificación oficial dengue)
INSERT INTO tipos_criadero (nombre, color, icono) VALUES
  ('Recipientes con agua',    '#dc2626', '🪣'),
  ('Neumáticos',              '#d97706', '🛞'),
  ('Piletas y tanques',       '#2563eb', '🛁'),
  ('Zanjas y agua estancada', '#0891b2', '💧'),
  ('Basurales y residuos',    '#16a34a', '🗑️'),
  ('Botellas y cacharros',    '#9333ea', '🍾');

-- Barrios de El Colorado (28, plano oficial)
INSERT INTO barrios (id, nombre, localidad, x, y, poblacion) VALUES
( 1, 'Mosconi',              'El Colorado', 16.5,  9.3, 0),
( 2, 'Eva Perón',            'El Colorado', 34.8,  8.0, 0),
( 3, 'Los Halcones',         'El Colorado', 51.0,  8.4, 0),
( 4, 'La Paz',               'El Colorado', 93.5, 18.1, 0),
( 5, 'Mitre',                'El Colorado', 11.0, 19.1, 0),
( 6, 'San Pantaleón',        'El Colorado', 30.2, 16.6, 0),
( 7, 'San Juan Norte',       'El Colorado', 41.2, 20.4, 0),
( 8, 'Diego F. Sevilla',     'El Colorado', 60.2, 22.8, 0),
( 9, '2 de Abril',           'El Colorado',  7.7, 29.7, 0),
(10, 'San Martín',           'El Colorado', 33.8, 29.9, 0),
(11, '103 Viviendas',        'El Colorado', 48.1, 35.1, 0),
(12, 'Independiente',        'El Colorado', 62.7, 32.9, 0),
(13, 'Terminal de Ómnibus',  'El Colorado', 81.9, 37.0, 0),
(14, 'La Pileta',            'El Colorado', 19.4, 39.3, 0),
(15, 'San Miguel',           'El Colorado', 49.8, 46.0, 0),
(16, '500 Viviendas I.P.V.', 'El Colorado', 64.8, 45.6, 0),
(17, 'Aeropuerto',           'El Colorado', 81.5, 47.1, 0),
(18, 'La Peña',              'El Colorado', 28.3, 51.2, 0),
(19, 'Itatí',                'El Colorado', 39.8, 50.3, 0),
(20, '140 Viviendas',        'El Colorado', 51.5, 52.1, 0),
(21, 'Vicentín',             'El Colorado', 24.0, 57.9, 0),
(22, '46 Viviendas',         'El Colorado', 32.1, 57.9, 0),
(23, 'INTA',                 'El Colorado', 35.6, 57.6, 0),
(24, '382 Viviendas I.P.V.', 'El Colorado', 64.8, 59.8, 0),
(25, 'M. M. Giroldi',        'El Colorado', 56.5, 60.2, 0),
(26, 'Pellegrini',           'El Colorado', 34.4, 65.2, 0),
(27, 'Vial',                 'El Colorado', 80.0, 65.8, 0),
(28, 'El Arco',              'El Colorado', 58.1, 77.8, 0);

-- Reportes demo: El Arco y San Martín quedan en rojo, varios en amarillo, el resto en verde.
INSERT INTO reportes (tipo_id, barrio_id, titulo, descripcion, referencia, estado, votos, creado_en) VALUES
(2, 28, 'Neumáticos acumulados junto al río',     'Más de 10 cubiertas con agua de lluvia en un baldío.',   'Río Bermejo y J. Hernández',            'pendiente',  7, NOW() - INTERVAL 2 DAY),
(4, 28, 'Zanja con agua estancada',               'Zanja tapada con basura, el agua no corre.',            'Ayacucho y Deán Funes',                 'verificado', 5, NOW() - INTERVAL 4 DAY),
(5, 28, 'Basural en terreno baldío',              'Latas, bidones y envases que juntan agua.',             'Maipú y San Lorenzo',                   'pendiente',  3, NOW() - INTERVAL 1 DAY),
(1, 28, 'Tachos destapados en el patio',          'Vivienda deshabitada con tachos llenos de agua.',       'Tacuarí y José Hernández',              'pendiente',  2, NOW() - INTERVAL 1 DAY),
(6, 28, 'Botellas en el cementerio',              'Floreros y botellas con agua en varias tumbas.',        'Cementerio Municipal, calle 8 de Abril','verificado', 9, NOW() - INTERVAL 3 DAY),
(1, 10, 'Floreros con agua en la plaza',          'Macetas sin arena en el sector de juegos.',             'Plaza, Av. San Martín y Av. 25 de Mayo','verificado', 6, NOW() - INTERVAL 3 DAY),
(5, 10, 'Microbasural cerca de la escuela',       'Envases y bolsas acumulados en la vereda.',             'Escuela N° 116, Ucrania',               'pendiente',  4, NOW() - INTERVAL 2 DAY),
(2, 10, 'Gomería con cubiertas a la intemperie',  'Cubiertas apiladas sin techo.',                         'Tucumán y Av. Larralde',                'pendiente',  3, NOW() - INTERVAL 1 DAY),
(4, 10, 'Cuneta tapada',                          'Agua quieta hace más de una semana.',                   'Mendoza y Salta',                       'pendiente',  1, NOW() - INTERVAL 5 DAY),
(3, 8,  'Pileta de lona abandonada',              'Pileta armada con agua verde.',                         'Pueyrredón y Cayo Novoa Gil',           'verificado', 4, NOW() - INTERVAL 2 DAY),
(1, 8,  'Baldes y tachos en obra',                'Obra parada con recipientes al aire libre.',            'J. M. de Rosas y Félix L. Navarro',     'pendiente',  2, NOW() - INTERVAL 1 DAY),
(5, 14, 'Basural en la costa',                    'Residuos acumulados cerca de la barranca.',             'Lapacho y Urunday',                     'pendiente',  5, NOW() - INTERVAL 3 DAY),
(2, 14, 'Cubiertas en terreno baldío',            'Neumáticos tirados entre los yuyos.',                   'Pioneros y Lapacho',                    'pendiente',  2, NOW() - INTERVAL 2 DAY),
(4, 24, 'Desagüe obstruido',                      'Canal con agua estancada frente a las viviendas.',      'Laishi y Dr. Julio C. Amicone',         'verificado', 3, NOW() - INTERVAL 4 DAY),
(1, 24, 'Recipientes en el patio comunitario',    'Tachos y bebederos sin vaciar.',                        'Bermejo y Papa Francisco',              'pendiente',  1, NOW() - INTERVAL 1 DAY),
(6, 16, 'Botellas en espacio verde',              'Botellas plásticas tiradas en el espacio verde.',       'Las Rosas y Dalias',                    'pendiente',  2, NOW() - INTERVAL 2 DAY),
(2, 17, 'Neumáticos en el predio',                'Cubiertas acumuladas al costado de la ruta.',           'Fontana y Ruta Prov. N° 1',             'verificado', 4, NOW() - INTERVAL 3 DAY),
(5, 3,  'Basural detrás del club',                'Residuos que juntan agua en cada lluvia.',              'Chaco y Av. Alicia Moreau de Justo',    'pendiente',  2, NOW() - INTERVAL 2 DAY),
(1, 1,  'Bebederos de animales sin recambio',     'Recipientes con agua verde en el fondo.',               'Julio A. Roca y Chubut',                'controlado', 6, NOW() - INTERVAL 6 DAY),
(3, 12, 'Pileta sin mantenimiento',               'Pileta con agua estancada en patio trasero.',           'Santa Fe y Juan Bacik',                 'controlado', 3, NOW() - INTERVAL 7 DAY),
(4, 5,  'Zanja limpiada',                         'El municipio desobstruyó la zanja.',                    'Hertelendi y Tomás Rojas',              'controlado', 5, NOW() - INTERVAL 8 DAY),
(2, 26, 'Cubiertas retiradas',                    'Se retiraron cubiertas del baldío.',                    'Sauce y Av. Carlos Pellegrini',         'controlado', 4, NOW() - INTERVAL 6 DAY),
(5, 9,  'Microbasural',                           'Residuos en la esquina, cerca del canal.',              'Corrientes y Los Inmigrantes',          'pendiente',  1, NOW() - INTERVAL 1 DAY),
(6, 19, 'Botellas en la vereda',                  'Botellas y envases en el cordón cuneta.',               'Güemes y J. J. Paso',                   'pendiente',  1, NOW() - INTERVAL 1 DAY);
