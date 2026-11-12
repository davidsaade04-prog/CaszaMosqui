<?php
/**
 * CaszaMosqui — Datos del equipo y de contacto (un solo lugar para editar).
 * Los usan acerca.php, contacto.php y el pie de página.
 *
 * Completar lo que falta: dejar '' (vacío) en lo que no se quiera mostrar.
 * Para las redes, poner el link completo (https://…).
 */
return [
    'equipo' => [
        [
            'nombre'   => 'Guillermo Ocampo',
            'rol'      => '',            // qué hizo en CaszaMosqui, ej.: 'Desarrollo y datos'
            'titulos'  => [],            // títulos / formación, ej.: ['Técnico en Informática', 'Auxiliar en IA']
            'foto'     => '',                               // ej.: assets/img/equipo/guillermo.jpg (opcional)
            'linkedin' => '',
        ],
        [
            'nombre'   => 'Lucas Ramírez',
            'rol'      => '',
            'titulos'  => [],
            'foto'     => '',
            'linkedin' => '',
        ],
        [
            'nombre'   => 'David Saade',
            'rol'      => '',
            'titulos'  => [],
            'foto'     => '',
            'linkedin' => '',
        ],
    ],

    'contacto' => [
        'email'     => '',           // ej.: caszamosqui@gmail.com
        'whatsapp'  => '',           // número para marcar, ej.: +5493704000000
        'whatsapp_txt' => '',        // cómo mostrarlo, ej.: 3704 00-0000
        'ciudad'    => 'El Colorado, Formosa, Argentina',
        'redes'     => [
            // 'Instagram' => 'https://instagram.com/…',
            // 'Facebook'  => 'https://facebook.com/…',
            // 'TikTok'    => 'https://tiktok.com/@…',
            // 'YouTube'   => 'https://youtube.com/@…',
            // 'LinkedIn'  => 'https://linkedin.com/company/…',
        ],
    ],
];
