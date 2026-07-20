<?php

return [
    'name' => 'Nehnuteľnosť',
    'settings' => 'Settings',
    'login_form' => 'Prihlasovacia forma',
    'register_form' => 'Formulár',
    'forgot_password_form' => 'Zabudnutý formulár hesla',
    'reset_password_form' => 'Resetovať heslo',
    'consult_form' => 'Zorganizovaný formulár',
    'review_form' => 'Formulár',
    'property_translations' => 'Preklady majetku',
    'project_translations' => 'Preklady projektov',
    'theme_options' => [
        'slug_name' => 'URL nehnuteľnosti',
        'slug_description' => 'Prispôsobte slimáky používané na stránky s nehnuteľnosťami. Pri úprave, pretože môže ovplyvniť SEO a používateľskú skúsenosť, buďte opatrní. Ak sa niečo pokazí, môžete resetovať predvolené zadanie predvolenej hodnoty alebo ju nechať prázdne.',
        'page_slug_name' => ':page Stránka Slug',
        'page_slug_description' => 'Pri prístupe k stránke to bude vyzerať ako __ph0__. Predvolená hodnota je __ph1__.',
        'page_slug_already_exists' => 'Slug __ph0__ Stránka sa už používa. Vyberte si inú.',
        'page_slugs' => [
            'projects_city' => 'Projekty od mesta',
            'projects_state' => 'Projekty štátu',
            'properties_city' => 'Nehnuteľnosti od mesta',
            'properties_state' => 'Nehnuteľnosti podľa štátu',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Obnovenie hesla',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Meno:',
        'field_email' => 'E -mail:',
        'field_phone' => 'Telefón:',
        'field_subject' => 'Predmet:',
        'field_content' => 'Obsah:',
        'field_address' => 'Adresa:',
        'field_package' => 'Balík:',
        'field_price' => 'Cena:',
        'field_total' => 'Celkom:',
        'field_account' => 'Účet:',

        // Common greetings and closings
        'greeting_admin' => 'Ahoj admin!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Drahý admin,',
        'closing_regards' => 'S pozdravom,',
        'closing_thank_you' => 'Ďakujem',
        'closing_best_regards' => 'S pozdravom,',

        // Common actions
        'action_view_property' => 'Pozerať sa',
        'action_verify_now' => 'Overte teraz',
        'action_reset_password' => 'Obnovenie hesla',

        // Common terms
        'credits' => 'credits',
        'per_credit' => 'úver',
        'save_amount' => '(Uložiť __ph0__)',
        'for_credits' => 'pre :count kredity',

        // New pending property email
        'new_pending_property_message' => 'Na schválenie čaká nová vlastnosť vytvorená __ph0__ „__ph1__“.',

        // Property approved email
        'property_approved_greeting' => 'Ahoj __ph0__,',
        'property_approved_message' => 'Vaša vlastnosť „:property_name“ bola úspešne schválená na :site_name.',
        'property_approved_access' => 'Teraz môžete získať prístup na webovú stránku a spravovať svoju vlastnosť.',
        'property_approved_view_edit' => 'Ak chcete zobraziť alebo upraviť svoju vlastnosť, kliknite na tento odkaz:',

        // Property rejected email
        'property_rejected_greeting' => 'Ahoj __ph0__,',
        'property_rejected_message' => 'Vaša vlastnosť „:property_name“ bola úspešne zamietnutá na :site_name.',
        'property_rejected_reason' => 'Dôvod odmietnutia je nasledujúci:',
        'property_rejected_contact' => 'Ak sa domnievate, že toto rozhodnutie bolo prijaté omylom, kontaktujte náš tím podpory na adrese :email.',
        'property_rejected_view_edit' => 'Ak chcete zobraziť alebo upraviť svoju vlastnosť, kliknite na tento odkaz:',

        // Account registered email
        'account_registered_message' => 'Zaregistrovaný nový účet:',

        // Account approved email
        'account_approved_title' => 'Schválený účet',
        'account_approved_greeting' => 'Drahý __ph0__,',
        'account_approved_congratulations' => 'Gratulujeme! Váš účet na :site_name bol schválený.',
        'account_approved_login' => 'Teraz sa môžete prihlásiť a začať používať naše služby. Ak máte akékoľvek otázky, neváhajte a oslovte náš podporný tím.',
        'account_approved_thanks' => 'Ďakujeme, že ste sa pripojili k :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Účet zamietnutý',
        'account_rejected_greeting' => 'Drahý __ph0__,',
        'account_rejected_regret' => 'Je nám ľúto, že vás informujeme, že váš účet na :site_name bol zamietnutý.',
        'account_rejected_reason' => 'Dôvod odmietnutia:',
        'account_rejected_contact' => 'Ak máte akékoľvek otázky alebo sa domnievate, že ide o chybu, kontaktujte náš tím podpory.',
        'account_rejected_thanks' => 'Ďakujem za pochopenie.',

        // Confirm email
        'confirm_email_greeting' => 'Ahoj!',
        'confirm_email_verify' => 'Ak chcete získať prístup na túto webovú stránku, overte svoju e -mailovú adresu. Kliknutím na tlačidlo nižšie overte svoj e -mail ..',
        'confirm_email_trouble' => 'Ak máte problémy s kliknutím na tlačidlo „Overiť teraz“, skopírujte a vložte adresu URL nižšie do svojho webového prehľadávača:',

        // Password reminder email
        'password_reminder_greeting' => 'Ahoj!',
        'password_reminder_request' => 'Dostávate tento e -mail, pretože sme dostali žiadosť o obnovenie hesla pre váš účet.',
        'password_reminder_no_action' => 'Ak ste nepožiadali o reset hesla, nie je potrebná žiadna ďalšia akcia.',
        'password_reminder_trouble' => 'Ak máte problémy s kliknutím na tlačidlo „Reset Password“, skopírujte a vložte adresu URL nižšie do svojho webového prehľadávača:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Ahoj __ph0__,',
        'payment_receipt_title' => 'Potvrdenie o platbe za váš nákup:',

        // Payment received email (admin)
        'payment_received_message' => 'Platba prijatá od :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name si vyžiadal bezplatné kredity.',

        // Notice email (consult form)
        'notice_title' => 'Nová požiadavka na konzultáciu',
        'notice_message' => 'Existuje nová konzultácia z __ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Ďakujeme za vašu žiadosť o konzultáciu!',
        'sender_confirmation_greeting' => 'Drahý __ph0__,',
        'sender_confirmation_thank_you' => 'Ďakujeme, že ste sa k nám oslovili! Dostali sme vašu žiadosť o konzultáciu a náš tím ju čoskoro skontroluje. Jeden z našich zástupcov sa vám čo najskôr vráti.',
        'sender_confirmation_submission_details' => 'Tu sú podrobnosti o vašom podaní:',
        'sender_confirmation_additional_info' => 'Ak máte akékoľvek ďalšie informácie alebo otázky, neváhajte odpovedať na tento e -mail.',
        'sender_confirmation_appreciate' => 'Ceníme si váš záujem a čoskoro budeme v kontakte!',
    ],
];
