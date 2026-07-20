<?php

return [
    'name' => 'Konzultacije',
    'edit' => 'Pregled konzultacije',
    'statuses' => [
        'read' => 'Pročitano',
        'unread' => 'Nepročitano',
    ],
    'phone' => 'Telefon',
    'ip_address' => 'IP adresa',
    'content' => 'Detalji',
    'consult_information' => 'Informacije o konzultaciji',
    'email' => [
        'header' => 'Email',
        'title' => 'Nova konzultacija s vaše web stranice',
        'success' => 'Konzultacija uspješno poslana!',
        'failed' => 'Trenutno se ne može poslati zahtjev, pokušajte ponovno kasnije!',
    ],
    'form' => [
        'name' => [
            'required' => 'Ime je obavezno',
        ],
        'email' => [
            'required' => 'Email je obavezan',
            'email' => 'Email adresa nije valjana',
        ],
        'content' => [
            'required' => 'Poruka je obavezna',
        ],
    ],
    'consult_sent_from' => 'Ova konzultacija poslana je iz',
    'time' => 'Vrijeme',
    'consult_id' => 'ID konzultacije',
    'form_name' => 'Ime',
    'form_email' => 'Email',
    'form_phone' => 'Telefon',
    'mark_as_read' => 'Označi kao pročitano',
    'mark_as_unread' => 'Označi kao nepročitano',
    'new_consult_notice' => 'Imate <span class="bold">:count</span> novih konzultacija',
    'view_all' => 'Pregledaj sve',
    'project' => 'Projekt',
    'property' => 'Nekretnina',
    'custom_field' => [
        'name' => 'Prilagođena polja',
        'create' => 'Stvori prilagođeno polje',
        'type' => 'Vrsta',
        'required' => 'Obavezno',
        'placeholder' => 'Zamjenski tekst',
        'order' => 'Redoslijed',
        'options' => 'Opcije',
        'option' => [
            'label' => 'Oznaka',
            'value' => 'Vrijednost',
            'add' => 'Dodaj novu opciju',
        ],
        'enums' => [
            'types' => [
                'text' => 'Tekst',
                'number' => 'Broj',
                'dropdown' => 'Padajući izbornik',
                'checkbox' => 'Potvrdni okvir',
                'radio' => 'Radio gumb',
                'textarea' => 'Tekstualno područje',
                'date' => 'Datum',
                'datetime' => 'Datum i vrijeme',
                'time' => 'Vrijeme',
            ],
        ],
    ],
];
