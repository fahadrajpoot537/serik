<?php

return [
    'name' => 'Konzultace',
    'edit' => 'Zobrazit konzultaci',
    'statuses' => [
        'read' => 'Přečteno',
        'unread' => 'Nepřečteno',
    ],
    'phone' => 'Telefon',
    'ip_address' => 'IP adresa',
    'content' => 'Podrobnosti',
    'consult_information' => 'Informace o konzultaci',
    'email' => [
        'header' => 'E-mail',
        'title' => 'Nová konzultace z vašich stránek',
        'success' => 'Konzultace úspěšně odeslána!',
        'failed' => 'Požadavek nelze odeslat, zkuste to prosím později!',
    ],
    'form' => [
        'name' => [
            'required' => 'Jméno je povinné',
        ],
        'email' => [
            'required' => 'E-mail je povinný',
            'email' => 'E-mailová adresa není platná',
        ],
        'content' => [
            'required' => 'Zpráva je povinná',
        ],
    ],
    'consult_sent_from' => 'Tato konzultační informace byla odeslána z',
    'time' => 'Čas',
    'consult_id' => 'ID konzultace',
    'form_name' => 'Jméno',
    'form_email' => 'E-mail',
    'form_phone' => 'Telefon',
    'mark_as_read' => 'Označit jako přečtené',
    'mark_as_unread' => 'Označit jako nepřečtené',
    'new_consult_notice' => 'Máte <span class="bold">:count</span> nových konzultací',
    'view_all' => 'Zobrazit vše',
    'project' => 'Projekt',
    'property' => 'Nemovitost',
    'custom_field' => [
        'name' => 'Vlastní pole',
        'create' => 'Vytvořit vlastní pole',
        'type' => 'Typ',
        'required' => 'Povinné',
        'placeholder' => 'Zástupný text',
        'order' => 'Pořadí',
        'options' => 'Možnosti',
        'option' => [
            'label' => 'Štítek',
            'value' => 'Hodnota',
            'add' => 'Přidat novou možnost',
        ],
        'enums' => [
            'types' => [
                'text' => 'Text',
                'number' => 'Číslo',
                'dropdown' => 'Rozbalovací seznam',
                'checkbox' => 'Zaškrtávací pole',
                'radio' => 'Přepínač',
                'textarea' => 'Textová oblast',
                'date' => 'Datum',
                'datetime' => 'Datum a čas',
                'time' => 'Čas',
            ],
        ],
    ],
];
