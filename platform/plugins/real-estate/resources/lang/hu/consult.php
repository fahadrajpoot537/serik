<?php

return [
    'name' => 'Konzultációk',
    'edit' => 'Konzultáció megtekintése',
    'statuses' => [
        'read' => 'Olvasott',
        'unread' => 'Olvasatlan',
    ],
    'phone' => 'Telefon',
    'ip_address' => 'IP cím',
    'content' => 'Részletek',
    'consult_information' => 'Konzultációs információk',
    'email' => [
        'header' => 'E-mail',
        'title' => 'Új konzultáció az oldaláról',
        'success' => 'Konzultáció sikeresen elküldve!',
        'failed' => 'Nem lehet kérést küldeni most, próbálja újra később!',
    ],
    'form' => [
        'name' => [
            'required' => 'A név kötelező',
        ],
        'email' => [
            'required' => 'Az e-mail kötelező',
            'email' => 'Az e-mail cím nem érvényes',
        ],
        'content' => [
            'required' => 'Az üzenet kötelező',
        ],
    ],
    'consult_sent_from' => 'Ez a konzultációs információ innen lett elküldve',
    'time' => 'Idő',
    'consult_id' => 'Konzultációs azonosító',
    'form_name' => 'Név',
    'form_email' => 'E-mail',
    'form_phone' => 'Telefon',
    'mark_as_read' => 'Megjelölés olvasottként',
    'mark_as_unread' => 'Megjelölés olvasatlanként',
    'new_consult_notice' => '<span class="bold">:count</span> új konzultációja van',
    'view_all' => 'Összes megtekintése',
    'project' => 'Projekt',
    'property' => 'Ingatlan',
    'custom_field' => [
        'name' => 'Egyéni mezők',
        'create' => 'Egyéni mező létrehozása',
        'type' => 'Típus',
        'required' => 'Kötelező',
        'placeholder' => 'Helykitöltő',
        'order' => 'Sorrend',
        'options' => 'Opciók',
        'option' => [
            'label' => 'Címke',
            'value' => 'Érték',
            'add' => 'Új opció hozzáadása',
        ],
        'enums' => [
            'types' => [
                'text' => 'Szöveg',
                'number' => 'Szám',
                'dropdown' => 'Legördülő',
                'checkbox' => 'Jelölőnégyzet',
                'radio' => 'Rádió',
                'textarea' => 'Szövegterület',
                'date' => 'Dátum',
                'datetime' => 'Dátum idő',
                'time' => 'Idő',
            ],
        ],
    ],
];
