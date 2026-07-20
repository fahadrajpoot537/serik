<?php

return [
    'name' => 'Konsultationer',
    'edit' => 'Se konsultation',
    'statuses' => [
        'read' => 'Læst',
        'unread' => 'Ulæst',
    ],
    'phone' => 'Telefon',
    'ip_address' => 'IP-adresse',
    'content' => 'Detaljer',
    'consult_information' => 'Konsultationsinformation',
    'email' => [
        'header' => 'E-mail',
        'title' => 'Ny konsultation fra din hjemmeside',
        'success' => 'Konsultation sendt!',
        'failed' => 'Kan ikke sende anmodning på dette tidspunkt, prøv venligst igen senere!',
    ],
    'form' => [
        'name' => [
            'required' => 'Navn er påkrævet',
        ],
        'email' => [
            'required' => 'E-mail er påkrævet',
            'email' => 'E-mailadressen er ikke gyldig',
        ],
        'content' => [
            'required' => 'Besked er påkrævet',
        ],
    ],
    'consult_sent_from' => 'Denne konsultationsinformation sendt fra',
    'time' => 'Tid',
    'consult_id' => 'Konsultations-ID',
    'form_name' => 'Navn',
    'form_email' => 'E-mail',
    'form_phone' => 'Telefon',
    'mark_as_read' => 'Marker som læst',
    'mark_as_unread' => 'Marker som ulæst',
    'new_consult_notice' => 'Du har <span class="bold">:count</span> nye konsultationer',
    'view_all' => 'Se alle',
    'project' => 'Projekt',
    'property' => 'Ejendom',
    'custom_field' => [
        'name' => 'Brugerdefinerede felter',
        'create' => 'Opret brugerdefineret felt',
        'type' => 'Type',
        'required' => 'Påkrævet',
        'placeholder' => 'Pladsholder',
        'order' => 'Rækkefølge',
        'options' => 'Valgmuligheder',
        'option' => [
            'label' => 'Etiket',
            'value' => 'Værdi',
            'add' => 'Tilføj ny valgmulighed',
        ],
        'enums' => [
            'types' => [
                'text' => 'Tekst',
                'number' => 'Nummer',
                'dropdown' => 'Rullemenu',
                'checkbox' => 'Afkrydsningsfelt',
                'radio' => 'Radio',
                'textarea' => 'Tekstområde',
                'date' => 'Dato',
                'datetime' => 'Dato og tid',
                'time' => 'Tid',
            ],
        ],
    ],
];
