<?php
/**
 * CaszaMosqui — Centros de salud de El Colorado (lista única para toda la página).
 * La usan: index.php (sección "Centros de salud" y mapa), test-sintomas.php y el chatbot.
 *
 * Datos verificados por el equipo (noviembre 2026), a partir de Google Maps, el Ministerio
 * de Desarrollo Humano de Formosa y las redes de cada institución.
 *
 * Campos:
 *   lat/lng   → null si todavía no tenemos el punto exacto (no se muestra pin; "Cómo llegar" usa la dirección)
 *   horario   → '24h' | lista de franjas ['dias' => [1..7] (1 = lunes), 'desde' => 'HH:MM', 'hasta' => 'HH:MM'] | null (sin confirmar)
 *   telefonos → ['etiqueta', 'número para mostrar', 'número para marcar (+54…)', whatsapp?]
 *
 * Para sumar un centro (por ejemplo, las salas de primeros auxilios de los barrios):
 * copiar un bloque, completar y subir. Lo que no esté confirmado, dejarlo en null.
 */

return [
    [
        'id'        => 'hospital',
        'nombre'    => 'Hospital El Colorado "Opizzo Traverso"',
        'tipo'      => 'Hospital público · Guardia',
        'publico'   => true,
        'guardia'   => true,
        'direccion' => 'Santa Fe s/n',
        'barrio'    => 'San Martín',
        'lat'       => -26.3087578,
        'lng'       => -59.3679557,
        'horario'   => '24h',
        'horario_txt' => 'Abierto las 24 horas, todos los días (atención permanente)',
        'telefonos' => [['Guardia', '(0370) 448-0024', '+543704480024', false]],
        'nota'      => 'Cabecera del Distrito Sanitario V de la provincia.',
    ],
    [
        'id'        => 'clinica-del-sol',
        'nombre'    => 'Clínica del Sol',
        'tipo'      => 'Clínica privada · 24 h',
        'publico'   => false,
        'guardia'   => true,
        'direccion' => 'Buenos Aires 552',
        'barrio'    => 'San Martín',
        'lat'       => null,
        'lng'       => null,
        'horario'   => '24h',
        'horario_txt' => 'Atención las 24 horas',
        'telefonos' => [['Teléfono', '(0370) 448-0009', '+543704480009', false]],
        'nota'      => null,
    ],
    [
        'id'        => 'sagrada-familia',
        'nombre'    => 'Consultorio La Sagrada Familia',
        'tipo'      => 'Consultorio médico',
        'publico'   => false,
        'guardia'   => false,
        'direccion' => 'Bonifacia Méndez y España',
        'barrio'    => null,
        'lat'       => null,
        'lng'       => null,
        'horario'   => [['dias' => [1, 2, 4, 5], 'desde' => '15:00', 'hasta' => '20:00']],
        'horario_txt' => 'Lunes, martes, jueves y viernes de 15 a 20 h',
        'telefonos' => [['Teléfono', '(0370) 458-8329', '+543704588329', false]],
        'nota'      => null,
    ],
    [
        'id'        => 'sagrado-corazon',
        'nombre'    => 'Instituto del Sagrado Corazón',
        'tipo'      => 'Instituto médico · con turno',
        'publico'   => false,
        'guardia'   => false,
        'direccion' => 'Av. San Martín 832',
        'barrio'    => null,
        'lat'       => null,
        'lng'       => null,
        'horario'   => [
            ['dias' => [1, 2, 3, 4, 5], 'desde' => '10:00', 'hasta' => '12:00'],
            ['dias' => [1, 2, 3, 4, 5], 'desde' => '17:30', 'hasta' => '20:00'],
        ],
        'horario_txt' => 'Lunes a viernes de 10 a 12 h y de 17:30 a 20 h (con turno)',
        'telefonos' => [
            ['Fijo', '(0370) 448-2703', '+543704482703', false],
            ['Celular / WhatsApp', '3704-694110', '+5493704694110', true],
        ],
        'nota'      => 'Atiende con turno: pedilo por teléfono o WhatsApp.',
    ],
    [
        'id'        => 'santa-rita',
        'nombre'    => 'Clínica Santa Rita',
        'tipo'      => 'Clínica privada',
        'publico'   => false,
        'guardia'   => false,
        'direccion' => 'San Juan y Santa Fe',
        'barrio'    => null,
        'lat'       => -26.3088421,
        'lng'       => -59.3691005,
        'horario'   => null,
        'horario_txt' => 'Horario a confirmar',
        'telefonos' => [],
        'nota'      => null,
    ],
];
