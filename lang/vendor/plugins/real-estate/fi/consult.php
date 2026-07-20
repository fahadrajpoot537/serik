<?php

return [
    'name' => 'Konsultaatiot',
    'edit' => 'Näytä konsultaatio',
    'statuses' => [
        'read' => 'Luettu',
        'unread' => 'Lukematon',
    ],
    'phone' => 'Puhelin',
    'ip_address' => 'IP-osoite',
    'content' => 'Yksityiskohdat',
    'consult_information' => 'Konsultaatiotiedot',
    'email' => [
        'header' => 'Sähköposti',
        'title' => 'Uusi konsultaatio sivustoltasi',
        'success' => 'Konsultaation lähettäminen onnistui!',
        'failed' => 'Pyyntöä ei voi lähettää tällä hetkellä, yritä myöhemmin uudelleen!',
    ],
    'form' => [
        'name' => [
            'required' => 'Nimi vaaditaan',
        ],
        'email' => [
            'required' => 'Sähköposti vaaditaan',
            'email' => 'Sähköpostiosoite ei ole kelvollinen',
        ],
        'content' => [
            'required' => 'Viesti vaaditaan',
        ],
    ],
    'consult_sent_from' => 'Tämä konsultaatioviesti lähetettiin osoitteesta',
    'time' => 'Aika',
    'consult_id' => 'Konsultaatio-ID',
    'form_name' => 'Nimi',
    'form_email' => 'Sähköposti',
    'form_phone' => 'Puhelin',
    'mark_as_read' => 'Merkitse luetuksi',
    'mark_as_unread' => 'Merkitse lukemattomaksi',
    'new_consult_notice' => 'Sinulla on <span class="bold">:count</span> uutta konsultaatiota',
    'view_all' => 'Näytä kaikki',
    'project' => 'Projekti',
    'property' => 'Kiinteistö',
    'custom_field' => [
        'name' => 'Mukautetut kentät',
        'create' => 'Luo mukautettu kenttä',
        'type' => 'Tyyppi',
        'required' => 'Pakollinen',
        'placeholder' => 'Paikkamerkki',
        'order' => 'Järjestys',
        'options' => 'Vaihtoehdot',
        'option' => [
            'label' => 'Nimike',
            'value' => 'Arvo',
            'add' => 'Lisää uusi vaihtoehto',
        ],
        'enums' => [
            'types' => [
                'text' => 'Teksti',
                'number' => 'Numero',
                'dropdown' => 'Pudotusvalikko',
                'checkbox' => 'Valintaruutu',
                'radio' => 'Radio',
                'textarea' => 'Tekstialue',
                'date' => 'Päivämäärä',
                'datetime' => 'Päivämäärä ja aika',
                'time' => 'Aika',
            ],
        ],
    ],
];
