<?php

return [
    'name' => 'Konsultatsioonid',
    'edit' => 'Vaata konsultatsiooni',
    'statuses' => [
        'read' => 'Loetud',
        'unread' => 'Lugemata',
    ],
    'phone' => 'Telefon',
    'ip_address' => 'IP-aadress',
    'content' => 'Üksikasjad',
    'consult_information' => 'Konsultatsiooni informatsioon',
    'email' => [
        'header' => 'E-post',
        'title' => 'Uus konsultatsioon teie veebilehelt',
        'success' => 'Konsultatsioon edukalt saadetud!',
        'failed' => 'Praegu ei saa päringut saata, palun proovige hiljem uuesti!',
    ],
    'form' => [
        'name' => [
            'required' => 'Nimi on kohustuslik',
        ],
        'email' => [
            'required' => 'E-post on kohustuslik',
            'email' => 'E-posti aadress ei ole kehtiv',
        ],
        'content' => [
            'required' => 'Sõnum on kohustuslik',
        ],
    ],
    'consult_sent_from' => 'See konsultatsiooni informatsioon saadeti aadressilt',
    'time' => 'Aeg',
    'consult_id' => 'Konsultatsiooni ID',
    'form_name' => 'Nimi',
    'form_email' => 'E-post',
    'form_phone' => 'Telefon',
    'mark_as_read' => 'Märgi loetuks',
    'mark_as_unread' => 'Märgi lugemata',
    'new_consult_notice' => 'Teil on <span class="bold">:count</span> uut konsultatsiooni',
    'view_all' => 'Vaata kõiki',
    'project' => 'Projekt',
    'property' => 'Kinnisvara',
    'custom_field' => [
        'name' => 'Kohandatud väljad',
        'create' => 'Loo kohandatud väli',
        'type' => 'Tüüp',
        'required' => 'Kohustuslik',
        'placeholder' => 'Kohatäide',
        'order' => 'Järjekord',
        'options' => 'Valikud',
        'option' => [
            'label' => 'Silt',
            'value' => 'Väärtus',
            'add' => 'Lisa uus valik',
        ],
        'enums' => [
            'types' => [
                'text' => 'Tekst',
                'number' => 'Number',
                'dropdown' => 'Rippmenüü',
                'checkbox' => 'Märkeruut',
                'radio' => 'Raadionupp',
                'textarea' => 'Tekstiala',
                'date' => 'Kuupäev',
                'datetime' => 'Kuupäev ja kellaaeg',
                'time' => 'Kellaaeg',
            ],
        ],
    ],
];
