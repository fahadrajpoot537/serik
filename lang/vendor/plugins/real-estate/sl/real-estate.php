<?php

return [
    'name' => 'Nepremičnine',
    'settings' => 'Settings',
    'login_form' => 'Obrazec za prijavo',
    'register_form' => 'Register obrazec',
    'forgot_password_form' => 'Pozabljeni obrazec za geslo',
    'reset_password_form' => 'Ponastavi obrazec za geslo',
    'consult_form' => 'Posvetujte z obrazcem',
    'review_form' => 'Obrazec za pregled',
    'property_translations' => 'Prevodi lastnosti',
    'project_translations' => 'Prevodi projekta',
    'theme_options' => [
        'slug_name' => 'URL -ji nepremičnin',
        'slug_description' => 'Prilagodite polže, ki se uporabljajo za nepremičninske strani. Bodite previdni pri spreminjanju, saj lahko vpliva na SEO in uporabniško izkušnjo. Če gre kaj narobe, lahko ponastavite na privzeto, tako da vtipkate privzeto vrednost ali pustite prazno.',
        'page_slug_name' => '__Ph0__ Stran Slug',
        'page_slug_description' => 'Ko dostopate do strani, bo videti kot __ph0__. Privzeta vrednost je __ph1__.',
        'page_slug_already_exists' => 'Strani __ph0__ Stran je že v uporabi. Izberite drugega.',
        'page_slugs' => [
            'projects_city' => 'Projekti po mestu',
            'projects_state' => 'Projekti po državi',
            'properties_city' => 'Nepremičnine po mestu',
            'properties_state' => 'Lastnosti po državi',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Ponastavitev gesla',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Ime:',
        'field_email' => 'E -poštni:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Zadeva:',
        'field_content' => 'Zadovoljstvo:',
        'field_address' => 'Naslov:',
        'field_package' => 'Paket:',
        'field_price' => 'Cena:',
        'field_total' => 'Skupaj:',
        'field_account' => 'Račun:',

        // Common greetings and closings
        'greeting_admin' => 'Živjo admin!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Dragi skrbnik,',
        'closing_regards' => 'Pozdrav,',
        'closing_thank_you' => 'Hvala',
        'closing_best_regards' => 'Lep pozdrav,',

        // Common actions
        'action_view_property' => 'Ogled lastnosti',
        'action_verify_now' => 'Preverite zdaj',
        'action_reset_password' => 'Ponastavitev gesla',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kredit',
        'save_amount' => '(Shrani __ph0__)',
        'for_credits' => 'za __ph0__ krediti',

        // New pending property email
        'new_pending_property_message' => 'Na odobritev čaka nova lastnost, ki jo je ustvaril __ph0__ "__ph1__".',

        // Property approved email
        'property_approved_greeting' => 'Pozdravljeni __ph0__,',
        'property_approved_message' => 'Vaša lastnost "__ph0__" je bila uspešno odobrena na __ph1__.',
        'property_approved_access' => 'Zdaj lahko dostopate do spletnega mesta in upravljate svojo lastnost.',
        'property_approved_view_edit' => 'Če si želite ogledati ali urediti svojo lastnost, kliknite na to povezavo:',

        // Property rejected email
        'property_rejected_greeting' => 'Pozdravljeni __ph0__,',
        'property_rejected_message' => 'Vaša lastnost "__ph0__" je bila uspešno zavrnjena na __ph1__.',
        'property_rejected_reason' => 'Razlog za zavrnitev je naslednji:',
        'property_rejected_contact' => 'Če menite, da je bila ta odločitev sprejeta napako, se obrnite na našo podporno skupino na :email.',
        'property_rejected_view_edit' => 'Če si želite ogledati ali urediti svojo lastnost, kliknite na to povezavo:',

        // Account registered email
        'account_registered_message' => 'Registriran nov račun:',

        // Account approved email
        'account_approved_title' => 'Račun odobren',
        'account_approved_greeting' => 'Dragi __ph0__,',
        'account_approved_congratulations' => 'Čestitke! Vaš račun na __ph0__ je bil odobren.',
        'account_approved_login' => 'Zdaj se lahko prijavite in začnete uporabljati naše storitve. Če imate kakršna koli vprašanja, se obrnite na našo podporno ekipo.',
        'account_approved_thanks' => 'Hvala, ker ste se pridružili __ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Račun zavrnjen',
        'account_rejected_greeting' => 'Dragi __ph0__,',
        'account_rejected_regret' => 'Obžalujemo, da vas obvestimo, da je bil vaš račun na __ph0__ zavrnjen.',
        'account_rejected_reason' => 'Razlog za zavrnitev:',
        'account_rejected_contact' => 'Če imate kakršna koli vprašanja ali verjamete, da gre za napako, se obrnite na našo podporno skupino.',
        'account_rejected_thanks' => 'Hvala za razumevanje.',

        // Confirm email
        'confirm_email_greeting' => 'Pozdravljeni!',
        'confirm_email_verify' => 'Preverite svoj e -poštni naslov za dostop do tega spletnega mesta. Kliknite spodnji gumb, da preverite svoj e -poštni naslov ..',
        'confirm_email_trouble' => 'Če imate težave s klikom na gumb "Preveri zdaj", kopirajte in prilepite URL spodaj v svoj spletni brskalnik:',

        // Password reminder email
        'password_reminder_greeting' => 'Pozdravljeni!',
        'password_reminder_request' => 'Prejemate to e -pošto, ker smo za vaš račun prejeli zahtevo za ponastavitev gesla.',
        'password_reminder_no_action' => 'Če niste zahtevali ponastavitve gesla, nadaljnje ukrepanje ni potrebno.',
        'password_reminder_trouble' => 'Če imate težave s klikom na gumb "Ponastavi geslo", kopirajte in prilepite URL spodaj v svoj spletni brskalnik:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Živjo __ph0__,',
        'payment_receipt_title' => 'Potrdilo o plačilu za vaš nakup:',

        // Payment received email (admin)
        'payment_received_message' => 'Plačilo, prejeto od __ph0__',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name je zahteval brezplačne dobropise.',

        // Notice email (consult form)
        'notice_title' => 'Nova zahteva za posvetovanje',
        'notice_message' => 'Obstaja novo posvetovanje z __ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Hvala za vašo zahtevo!',
        'sender_confirmation_greeting' => 'Dragi __ph0__,',
        'sender_confirmation_thank_you' => 'Hvala, ker ste se obrnili na nas! Prejeli smo vašo zahtevo za posvetovanje in naša ekipa jo bo kmalu pregledala. Eden od naših predstavnikov se vam bo čim prej oglasil.',
        'sender_confirmation_submission_details' => 'Tu so podrobnosti o vaši oddaji:',
        'sender_confirmation_additional_info' => 'Če imate dodatne informacije ali vprašanja, lahko odgovorite na to e -poštno sporočilo.',
        'sender_confirmation_appreciate' => 'Cenimo vaše zanimanje in bomo kmalu v stiku!',
    ],
];
