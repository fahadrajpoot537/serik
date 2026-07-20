<?php

return [
    'name' => 'Vastgoed',
    'settings' => 'Instellingen',
    'login_form' => 'Inlogformulier',
    'register_form' => 'Registratieformulier',
    'forgot_password_form' => 'Wachtwoord vergeten formulier',
    'reset_password_form' => 'Wachtwoord resetten formulier',
    'consult_form' => 'Consultformulier',
    'review_form' => 'Reviewformulier',
    'property_translations' => 'Woonobject vertalingen',
    'project_translations' => 'Project vertalingen',
    'theme_options' => [
        'slug_name' => 'Vastgoed URL\'s',
        'slug_description' => 'Pas de slugs aan die gebruikt worden voor vastgoedpagina\'s. Wees voorzichtig bij het wijzigen, dit kan invloed hebben op SEO en gebruikerservaring. Als er iets misgaat, kunt u resetten naar standaard door de standaardwaarde te typen of leeg te laten.',
        'page_slug_name' => ':page pagina slug',
        'page_slug_description' => 'Het zal er uitzien als :slug wanneer u de pagina bezoekt. Standaardwaarde is :default.',
        'page_slug_already_exists' => 'De :slug pagina slug is al in gebruik. Kies een andere.',
        'page_slugs' => [
            'projects_city' => 'Projecten per plaats',
            'projects_state' => 'Projecten per provincie',
            'properties_city' => 'Woonobjecten per plaats',
            'properties_state' => 'Woonobjecten per provincie',
            'login' => 'Inloggen',
            'register' => 'Registreren',
            'reset_password' => 'Wachtwoord resetten',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Naam:',
        'field_email' => 'Email:',
        'field_phone' => 'Telefoon:',
        'field_subject' => 'Onderwerp:',
        'field_content' => 'Inhoud:',
        'field_address' => 'Adres:',
        'field_package' => 'Pakket:',
        'field_price' => 'Prijs:',
        'field_total' => 'Totaal:',
        'field_account' => 'Account:',

        // Common greetings and closings
        'greeting_admin' => 'Hallo beheerder!',
        'greeting_hello' => 'Hallo',
        'greeting_dear' => 'Beste',
        'greeting_dear_admin' => 'Beste beheerder,',
        'closing_regards' => 'Met vriendelijke groet,',
        'closing_thank_you' => 'Dank u',
        'closing_best_regards' => 'Met vriendelijke groet,',

        // Common actions
        'action_view_property' => 'Woonobject bekijken',
        'action_verify_now' => 'Nu verifiëren',
        'action_reset_password' => 'Wachtwoord resetten',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/credit',
        'save_amount' => '(Bespaar :amount)',
        'for_credits' => 'voor :count credits',

        // New pending property email
        'new_pending_property_message' => 'Een nieuw woonobject aangemaakt door :author ":name" wacht op goedkeuring.',

        // Property approved email
        'property_approved_greeting' => 'Hallo :name,',
        'property_approved_message' => 'Uw woonobject ":property_name" is succesvol goedgekeurd op :site_name.',
        'property_approved_access' => 'U kunt nu de website bezoeken en uw woonobject beheren.',
        'property_approved_view_edit' => 'Om uw woonobject te bekijken of te bewerken, klik op deze link:',

        // Property rejected email
        'property_rejected_greeting' => 'Hallo :name,',
        'property_rejected_message' => 'Uw woonobject ":property_name" is afgekeurd op :site_name.',
        'property_rejected_reason' => 'De reden voor afkeuring is als volgt:',
        'property_rejected_contact' => 'Als u denkt dat deze beslissing per vergissing is genomen, neem dan contact op met ons ondersteuningsteam via :email.',
        'property_rejected_view_edit' => 'Om uw woonobject te bekijken of te bewerken, klik op deze link:',

        // Account registered email
        'account_registered_message' => 'Een nieuw account is geregistreerd:',

        // Account approved email
        'account_approved_title' => 'Account goedgekeurd',
        'account_approved_greeting' => 'Beste :name,',
        'account_approved_congratulations' => 'Gefeliciteerd! Uw account op :site_name is goedgekeurd.',
        'account_approved_login' => 'U kunt nu inloggen en onze diensten gebruiken. Als u vragen heeft, neem dan gerust contact op met ons ondersteuningsteam.',
        'account_approved_thanks' => 'Bedankt voor het lid worden van :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Account afgekeurd',
        'account_rejected_greeting' => 'Beste :name,',
        'account_rejected_regret' => 'Het spijt ons u te moeten mededelen dat uw account op :site_name is afgekeurd.',
        'account_rejected_reason' => 'Reden voor afkeuring:',
        'account_rejected_contact' => 'Als u vragen heeft of denkt dat dit een vergissing is, neem dan contact op met ons ondersteuningsteam.',
        'account_rejected_thanks' => 'Bedankt voor uw begrip.',

        // Confirm email
        'confirm_email_greeting' => 'Hallo!',
        'confirm_email_verify' => 'Verifieer uw emailadres om toegang te krijgen tot deze website. Klik op de onderstaande knop om uw email te verifiëren.',
        'confirm_email_trouble' => 'Als u problemen heeft met het klikken op de "Nu verifiëren" knop, kopieer en plak dan de onderstaande URL in uw webbrowser:',

        // Password reminder email
        'password_reminder_greeting' => 'Hallo!',
        'password_reminder_request' => 'U ontvangt deze email omdat we een verzoek hebben ontvangen om uw wachtwoord te resetten.',
        'password_reminder_no_action' => 'Als u geen wachtwoordreset heeft aangevraagd, hoeft u niets te doen.',
        'password_reminder_trouble' => 'Als u problemen heeft met het klikken op de "Wachtwoord resetten" knop, kopieer en plak dan de onderstaande URL in uw webbrowser:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hallo :name,',
        'payment_receipt_title' => 'Betalingsbewijs voor uw aankoop:',

        // Payment received email (admin)
        'payment_received_message' => 'Betaling ontvangen van :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name heeft gratis credits geclaimd.',

        // Notice email (consult form)
        'notice_title' => 'Nieuwe consultaanvraag',
        'notice_message' => 'Er is een nieuw consult van :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Bedankt voor uw consultaanvraag!',
        'sender_confirmation_greeting' => 'Beste :name,',
        'sender_confirmation_thank_you' => 'Bedankt dat u contact met ons heeft opgenomen! We hebben uw consultaanvraag ontvangen en ons team zal deze binnenkort beoordelen. Een van onze medewerkers neemt zo snel mogelijk contact met u op.',
        'sender_confirmation_submission_details' => 'Hier zijn de details van uw inzending:',
        'sender_confirmation_additional_info' => 'Als u aanvullende informatie of vragen heeft, antwoord dan gerust op deze email.',
        'sender_confirmation_appreciate' => 'We waarderen uw interesse en nemen binnenkort contact met u op!',
    ],
    'contact_for_price' => 'Contact',
    'contact_for_price' => 'Contact',
    'yes' => 'Ja',
    'no' => 'Nee',
    'projects' => 'Projecten',
    'properties' => 'Eigendommen',
    'agents' => 'Makelaars',
    'projects_in_city' => 'Projecten in :city',
    'properties_in_city' => 'Eigendommen in :city',
    'projects_in_state' => 'Projecten in :state',
    'properties_in_state' => 'Eigendommen in :state',
    'sort_date_asc' => 'Oudste',
    'sort_date_desc' => 'Nieuwste',
    'sort_price_asc' => 'Prijs (laag naar hoog)',
    'sort_price_desc' => 'Prijs (hoog naar laag)',
    'sort_name_asc' => 'Naam (A-Z)',
    'sort_name_desc' => 'Naam (Z-A)',
    'area_unit_m2' => 'm²',
    'area_unit_ft2' => 'ft2',
    'area_unit_yd2' => 'yd2',
    'edit_property' => 'Bewerk deze eigendom',
    'edit_project' => 'Bewerk dit project',
    'edit_agent' => 'Bewerk deze makelaar',
    'property_no_longer_available' => 'Eigendom niet langer beschikbaar: :name',
    'property_listing_expired' => 'Deze eigendomsvermelding is verlopen',
    'property_listing_no_longer_available' => 'Deze eigendomsvermelding is niet langer beschikbaar',
    'property_expired_title' => 'Eigendom verlopen: :name',
];
