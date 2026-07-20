<?php

return [
    'name' => 'Fast eiendom',
    'settings' => 'Settings',
    'login_form' => 'Påloggingsskjema',
    'register_form' => 'Registreringsskjema',
    'forgot_password_form' => 'Glemt passordskjema',
    'reset_password_form' => 'Tilbakestill passordskjema',
    'consult_form' => 'Se skjema',
    'review_form' => 'Gjennomgangsskjema',
    'property_translations' => 'Eiendomsoversettelser',
    'project_translations' => 'Prosjektoversettelser',
    'theme_options' => [
        'slug_name' => 'Eiendomsadresser',
        'slug_description' => 'Tilpass sneglene som brukes til eiendomssider. Vær forsiktig når du modifiserer, da det kan påvirke SEO og brukeropplevelse. Hvis noe går galt, kan du tilbakestille til standard ved å skrive standardverdien eller la den være tom.',
        'page_slug_name' => ':page Sidesnugle',
        'page_slug_description' => 'Det vil se ut som :slug når du får tilgang til siden. Standardverdien er __Ph1__.',
        'page_slug_already_exists' => '__Ph0__ -sidesluggen er allerede i bruk. Velg en annen.',
        'page_slugs' => [
            'projects_city' => 'Prosjekter etter by',
            'projects_state' => 'Prosjekter etter stat',
            'properties_city' => 'Eiendommer etter by',
            'properties_state' => 'Eiendommer etter stat',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Tilbakestill passord',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Navn:',
        'field_email' => 'E -post:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Tema:',
        'field_content' => 'Innhold:',
        'field_address' => 'Adresse:',
        'field_package' => 'Pakke:',
        'field_price' => 'Pris:',
        'field_total' => 'Total:',
        'field_account' => 'Konto:',

        // Common greetings and closings
        'greeting_admin' => 'Hei admin!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Kjære admin,',
        'closing_regards' => 'Hilsen,',
        'closing_thank_you' => 'Takk',
        'closing_best_regards' => 'Vennlig hilsen,',

        // Common actions
        'action_view_property' => 'Se eiendom',
        'action_verify_now' => 'Bekreft nå',
        'action_reset_password' => 'Tilbakestill passord',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kreditt',
        'save_amount' => '(Lagre __Ph0__)',
        'for_credits' => 'for __Ph0__ studiepoeng',

        // New pending property email
        'new_pending_property_message' => 'En ny eiendom opprettet av __Ph0__ "__Ph1__" venter på godkjenning.',

        // Property approved email
        'property_approved_greeting' => 'Hei __Ph0__,',
        'property_approved_message' => 'Eiendommen din "__Ph0__" er godkjent på __Ph1__.',
        'property_approved_access' => 'Du kan nå få tilgang til nettstedet og administrere eiendommen din.',
        'property_approved_view_edit' => 'For å se eller redigere eiendommen din, vennligst klikk på denne lenken:',

        // Property rejected email
        'property_rejected_greeting' => 'Hei __Ph0__,',
        'property_rejected_message' => 'Eiendommen din "__Ph0__" er vellykket avvist på __Ph1__.',
        'property_rejected_reason' => 'Årsaken til avvisning er som følger:',
        'property_rejected_contact' => 'Hvis du tror denne avgjørelsen ble tatt feil, kan du kontakte vårt supportteam på __Ph0__.',
        'property_rejected_view_edit' => 'For å se eller redigere eiendommen din, vennligst klikk på denne lenken:',

        // Account registered email
        'account_registered_message' => 'En ny konto registrert:',

        // Account approved email
        'account_approved_title' => 'Konto godkjent',
        'account_approved_greeting' => 'Kjære __Ph0__,',
        'account_approved_congratulations' => 'Gratulerer! Kontoen din på __Ph0__ er godkjent.',
        'account_approved_login' => 'Du kan nå logge inn og begynne å bruke tjenestene våre. Hvis du har spørsmål, kan du gjerne nå vårt supportteam.',
        'account_approved_thanks' => 'Takk for at du ble med __Ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Konto avvist',
        'account_rejected_greeting' => 'Kjære __Ph0__,',
        'account_rejected_regret' => 'Vi angrer på å informere deg om at kontoen din på __Ph0__ er blitt avvist.',
        'account_rejected_reason' => 'Årsak til avvisning:',
        'account_rejected_contact' => 'Hvis du har spørsmål eller tror at dette er en feil, kan du kontakte vårt supportteam.',
        'account_rejected_thanks' => 'Takk for forståelsen.',

        // Confirm email
        'confirm_email_greeting' => 'Hallo!',
        'confirm_email_verify' => 'Bekreft e -postadressen din for å få tilgang til dette nettstedet. Klikk på knappen nedenfor for å bekrefte e -posten din ..',
        'confirm_email_trouble' => 'Hvis du har problemer med å klikke på "Bekreft nå" -knappen, kopier og lim inn URL -en nedenfor i nettleseren din:',

        // Password reminder email
        'password_reminder_greeting' => 'Hallo!',
        'password_reminder_request' => 'Du mottar denne e -posten fordi vi mottok en tilbakestilling av passord for kontoen din.',
        'password_reminder_no_action' => 'Hvis du ikke ba om tilbakestilling av passord, er det ikke nødvendig med ytterligere handlinger.',
        'password_reminder_trouble' => 'Hvis du har problemer med å klikke på "Tilbakestill passord" -knappen, kopier og lim inn URL -en nedenfor i nettleseren din:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hei __Ph0__,',
        'payment_receipt_title' => 'Betalingskvittering for kjøpet:',

        // Payment received email (admin)
        'payment_received_message' => 'Betaling mottatt fra __Ph0__',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name har krevd gratis studiepoeng.',

        // Notice email (consult form)
        'notice_title' => 'Ny konsulentforespørsel',
        'notice_message' => 'Det er en ny konsultasjon fra __Ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Takk for din konsulentforespørsel!',
        'sender_confirmation_greeting' => 'Kjære __Ph0__,',
        'sender_confirmation_thank_you' => 'Takk for at du nådde oss! Vi har mottatt din konsulentforespørsel, og teamet vårt vil gjennomgå den om kort tid. En av våre representanter vil komme tilbake til deg så snart som mulig.',
        'sender_confirmation_submission_details' => 'Her er detaljene i innsendingen din:',
        'sender_confirmation_additional_info' => 'Hvis du har ytterligere informasjon eller spørsmål, kan du gjerne svare på denne e -posten.',
        'sender_confirmation_appreciate' => 'Vi setter pris på interessen din og vil snart være i kontakt!',
    ],
];
