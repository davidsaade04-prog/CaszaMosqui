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
            'titulos'  => ['Técnico Superior en Desarrollo de Software', 'Técnico Inicial en Inteligencia Artificial', 'Técnico en Informática'],
            'foto'     => 'assets/img/equipo/guillermo-ocampo.jpg',
            'email'    => 'guilllermo.oc@gmail.com',
            'whatsapp' => '+5493705067582',
            'whatsapp_txt' => '3705 06-7582',
            'instagram' => 'guilllermo_oc',
            'linkedin' => '',
        ],
        [
            'nombre'   => 'Lucas Ramírez',
            'rol'      => '',
            'email'    => '',
            'whatsapp' => '',
            'whatsapp_txt' => '',
            'instagram' => '',
            'titulos'  => [],
            'foto'     => '',
            'linkedin' => '',
        ],
        [
            'nombre'   => 'David Oscar Saade',
            'rol'      => '',
            'email'    => 'davidsaade04@gmail.com',
            'whatsapp' => '+5493704837277',
            'whatsapp_txt' => '3704 83-7277',
            'instagram' => '',
            'titulos'  => ['Técnico en Administración de Empresas', 'Técnico Profesional Inicial Auxiliar en Inteligencia Artificial'],
            'foto'     => 'assets/img/equipo/david-saade.jpg',
            'linkedin' => '',
        ],
    ],

    'contacto' => [
        'email'     => 'davidsaade04@gmail.com',
        'whatsapp'  => '+5493704837277',
        'whatsapp_txt' => '3704 83-7277',
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
