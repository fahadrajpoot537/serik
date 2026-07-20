<?php

return [
    'name' => 'Real Estate',
    'settings' => 'Asetukset',
    'login_form' => 'Kirjautumislomake',
    'register_form' => 'Rekisteröitymislomake',
    'forgot_password_form' => 'Unohtunut salasana -lomake',
    'reset_password_form' => 'Salasanan nollauslomake',
    'consult_form' => 'Konsultaatiolomake',
    'review_form' => 'Arviointilomake',
    'property_translations' => 'Kiinteistökäännökset',
    'project_translations' => 'Projektikäännökset',
    'theme_options' => [
        'slug_name' => 'Real Estate URL-osoitteet',
        'slug_description' => 'Mukauta kiinteistösivuilla käytettäviä polkuja. Ole varovainen muokattaessa, sillä se voi vaikuttaa hakukoneoptimointiin ja käyttäjäkokemukseen. Jos jokin menee pieleen, voit palauttaa oletusarvon kirjoittamalla oletusarvon tai jättämällä sen tyhjäksi.',
        'page_slug_name' => ':page sivun polku',
        'page_slug_description' => 'Se näyttää tältä :slug kun käyt sivulla. Oletusarvo on :default.',
        'page_slug_already_exists' => 'Sivupolku :slug on jo käytössä. Valitse toinen.',
        'page_slugs' => [
            'projects_city' => 'Projektit kaupungin mukaan',
            'projects_state' => 'Projektit osavaltion mukaan',
            'properties_city' => 'Kiinteistöt kaupungin mukaan',
            'properties_state' => 'Kiinteistöt osavaltion mukaan',
            'login' => 'Kirjaudu sisään',
            'register' => 'Rekisteröidy',
            'reset_password' => 'Nollaa salasana',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nimi:',
        'field_email' => 'Sähköposti:',
        'field_phone' => 'Puhelin:',
        'field_subject' => 'Aihe:',
        'field_content' => 'Sisältö:',
        'field_address' => 'Osoite:',
        'field_package' => 'Paketti:',
        'field_price' => 'Hinta:',
        'field_total' => 'Yhteensä:',
        'field_account' => 'Tili:',

        // Common greetings and closings
        'greeting_admin' => 'Hei järjestelmänvalvoja!',
        'greeting_hello' => 'Hei',
        'greeting_dear' => 'Hyvä',
        'greeting_dear_admin' => 'Hyvä järjestelmänvalvoja,',
        'closing_regards' => 'Ystävällisin terveisin,',
        'closing_thank_you' => 'Kiitos',
        'closing_best_regards' => 'Parhain terveisin,',

        // Common actions
        'action_view_property' => 'Näytä kiinteistö',
        'action_verify_now' => 'Vahvista nyt',
        'action_reset_password' => 'Nollaa salasana',

        // Common terms
        'credits' => 'krediittiä',
        'per_credit' => '/krediitti',
        'save_amount' => '(Säästä :amount)',
        'for_credits' => ':count krediitille',

        // New pending property email
        'new_pending_property_message' => 'Käyttäjän :author luoma uusi kiinteistö ":name" odottaa hyväksyntää.',

        // Property approved email
        'property_approved_greeting' => 'Hei :name,',
        'property_approved_message' => 'Kiinteistösi ":property_name" on hyväksytty sivustolla :site_name.',
        'property_approved_access' => 'Voit nyt käyttää sivustoa ja hallita kiinteistöäsi.',
        'property_approved_view_edit' => 'Nähdäksesi tai muokataksesi kiinteistöäsi, napsauta tätä linkkiä:',

        // Property rejected email
        'property_rejected_greeting' => 'Hei :name,',
        'property_rejected_message' => 'Kiinteistösi ":property_name" on hylätty sivustolla :site_name.',
        'property_rejected_reason' => 'Hylkäämisen syy on seuraava:',
        'property_rejected_contact' => 'Jos uskot tämän päätöksen olevan virheellinen, ota yhteyttä tukitiimiimme osoitteessa :email.',
        'property_rejected_view_edit' => 'Nähdäksesi tai muokataksesi kiinteistöäsi, napsauta tätä linkkiä:',

        // Account registered email
        'account_registered_message' => 'Uusi tili rekisteröity:',

        // Account approved email
        'account_approved_title' => 'Tili hyväksytty',
        'account_approved_greeting' => 'Hyvä :name,',
        'account_approved_congratulations' => 'Onnittelut! Tilisi sivustolla :site_name on hyväksytty.',
        'account_approved_login' => 'Voit nyt kirjautua sisään ja aloittaa palveluidemme käytön. Jos sinulla on kysyttävää, ota rohkeasti yhteyttä tukitiimiimme.',
        'account_approved_thanks' => 'Kiitos liittymisestäsi sivustolle :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Tili hylätty',
        'account_rejected_greeting' => 'Hyvä :name,',
        'account_rejected_regret' => 'Pahoittelemme ilmoittaessamme, että tilisi sivustolla :site_name on hylätty.',
        'account_rejected_reason' => 'Hylkäämisen syy:',
        'account_rejected_contact' => 'Jos sinulla on kysyttävää tai uskot tämän olevan virhe, ota yhteyttä tukitiimiimme.',
        'account_rejected_thanks' => 'Kiitos ymmärryksestäsi.',

        // Confirm email
        'confirm_email_greeting' => 'Hei!',
        'confirm_email_verify' => 'Vahvista sähköpostiosoitteesi päästäksesi tälle sivustolle. Napsauta alla olevaa painiketta vahvistaaksesi sähköpostisi.',
        'confirm_email_trouble' => 'Jos sinulla on vaikeuksia napsauttaa "Vahvista nyt" -painiketta, kopioi ja liitä alla oleva URL-osoite selaimeen:',

        // Password reminder email
        'password_reminder_greeting' => 'Hei!',
        'password_reminder_request' => 'Saat tämän sähköpostin, koska olemme vastaanottaneet salasanan nollauspyynnön tilillesi.',
        'password_reminder_no_action' => 'Jos et pyytänyt salasanan nollausta, toimenpiteitä ei tarvita.',
        'password_reminder_trouble' => 'Jos sinulla on vaikeuksia napsauttaa "Nollaa salasana" -painiketta, kopioi ja liitä alla oleva URL-osoite selaimeen:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hei :name,',
        'payment_receipt_title' => 'Maksukuitti ostoksellesi:',

        // Payment received email (admin)
        'payment_received_message' => 'Maksu vastaanotettu käyttäjältä :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name on lunastanut ilmaisia krediittejä.',

        // Notice email (consult form)
        'notice_title' => 'Uusi konsultaatiopyyntö',
        'notice_message' => 'Uusi konsultaatio kiinteistöstä :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Kiitos konsultaatiopyynn��stäsi!',
        'sender_confirmation_greeting' => 'Hyvä :name,',
        'sender_confirmation_thank_you' => 'Kiitos yhteydenotostasi! Olemme vastaanottaneet konsultaatiopyyntösi ja tiimimme tarkistaa sen pian. Yksi edustajistamme ottaa sinuun yhteyttä mahdollisimman pian.',
        'sender_confirmation_submission_details' => 'Tässä ovat lähetyksesi tiedot:',
        'sender_confirmation_additional_info' => 'Jos sinulla on lisätietoja tai kysyttävää, vastaa tähän sähköpostiin.',
        'sender_confirmation_appreciate' => 'Arvostamme kiinnostustasi ja olemme yhteydessä pian!',
    ],
];
