<?php

return [
    'name' => 'Nekretnine',
    'settings' => 'Postavke',
    'login_form' => 'Obrazac za prijavu',
    'register_form' => 'Obrazac za registraciju',
    'forgot_password_form' => 'Obrazac za zaboravljenu lozinku',
    'reset_password_form' => 'Obrazac za resetiranje lozinke',
    'consult_form' => 'Obrazac za konzultacije',
    'review_form' => 'Obrazac za recenzije',
    'property_translations' => 'Prijevodi nekretnina',
    'project_translations' => 'Prijevodi projekata',
    'theme_options' => [
        'slug_name' => 'URL-ovi nekretnina',
        'slug_description' => 'Prilagodite nazive URL-ova korištene za stranice nekretnina. Budite oprezni pri izmjeni jer to može utjecati na SEO i korisničko iskustvo. Ako nešto pođe po zlu, možete resetirati na zadano upisivanjem zadane vrijednosti ili ostavljanjem praznim.',
        'page_slug_name' => 'Slug stranice :page',
        'page_slug_description' => 'Izgledat će kao :slug kada pristupite stranici. Zadana vrijednost je :default.',
        'page_slug_already_exists' => 'Slug stranice :slug već se koristi. Molimo odaberite drugi.',
        'page_slugs' => [
            'projects_city' => 'Projekti po gradu',
            'projects_state' => 'Projekti po regiji',
            'properties_city' => 'Nekretnine po gradu',
            'properties_state' => 'Nekretnine po regiji',
            'login' => 'Prijava',
            'register' => 'Registracija',
            'reset_password' => 'Resetiranje lozinke',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Ime:',
        'field_email' => 'Email:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Predmet:',
        'field_content' => 'Sadržaj:',
        'field_address' => 'Adresa:',
        'field_package' => 'Paket:',
        'field_price' => 'Cijena:',
        'field_total' => 'Ukupno:',
        'field_account' => 'Račun:',

        // Common greetings and closings
        'greeting_admin' => 'Pozdrav Admin!',
        'greeting_hello' => 'Pozdrav',
        'greeting_dear' => 'Poštovani',
        'greeting_dear_admin' => 'Poštovani Admin,',
        'closing_regards' => 'Srdačan pozdrav,',
        'closing_thank_you' => 'Hvala vam',
        'closing_best_regards' => 'Lijep pozdrav,',

        // Common actions
        'action_view_property' => 'Pregledaj nekretninu',
        'action_verify_now' => 'Potvrdi sada',
        'action_reset_password' => 'Resetiraj lozinku',

        // Common terms
        'credits' => 'krediti',
        'per_credit' => '/kredit',
        'save_amount' => '(Ušteda :amount)',
        'for_credits' => 'za :count kredita',

        // New pending property email
        'new_pending_property_message' => 'Nova nekretnina koju je kreirao :author ":name" čeka odobrenje.',

        // Property approved email
        'property_approved_greeting' => 'Pozdrav :name,',
        'property_approved_message' => 'Vaša nekretnina ":property_name" uspješno je odobrena na :site_name.',
        'property_approved_access' => 'Sada možete pristupiti web stranici i upravljati svojom nekretninom.',
        'property_approved_view_edit' => 'Za pregled ili uređivanje svoje nekretnine, molimo kliknite na ovaj link:',

        // Property rejected email
        'property_rejected_greeting' => 'Pozdrav :name,',
        'property_rejected_message' => 'Vaša nekretnina ":property_name" je odbijena na :site_name.',
        'property_rejected_reason' => 'Razlog za odbijanje je sljedeći:',
        'property_rejected_contact' => 'Ako smatrate da je ova odluka donesena greškom, molimo kontaktirajte naš tim za podršku na :email.',
        'property_rejected_view_edit' => 'Za pregled ili uređivanje svoje nekretnine, molimo kliknite na ovaj link:',

        // Account registered email
        'account_registered_message' => 'Registriran je novi račun:',

        // Account approved email
        'account_approved_title' => 'Račun odobren',
        'account_approved_greeting' => 'Poštovani :name,',
        'account_approved_congratulations' => 'Čestitamo! Vaš račun na :site_name je odobren.',
        'account_approved_login' => 'Sada se možete prijaviti i početi koristiti naše usluge. Ako imate bilo kakvih pitanja, slobodno kontaktirajte naš tim za podršku.',
        'account_approved_thanks' => 'Hvala vam što ste se pridružili :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Račun odbijen',
        'account_rejected_greeting' => 'Poštovani :name,',
        'account_rejected_regret' => 'Žao nam je obavijestiti vas da je vaš račun na :site_name odbijen.',
        'account_rejected_reason' => 'Razlog za odbijanje:',
        'account_rejected_contact' => 'Ako imate bilo kakvih pitanja ili smatrate da je ovo greška, molimo kontaktirajte naš tim za podršku.',
        'account_rejected_thanks' => 'Hvala vam na razumijevanju.',

        // Confirm email
        'confirm_email_greeting' => 'Pozdrav!',
        'confirm_email_verify' => 'Molimo potvrdite svoju email adresu kako biste pristupili ovoj web stranici. Kliknite na gumb ispod za potvrdu svog emaila.',
        'confirm_email_trouble' => 'Ako imate problema s klikom na gumb "Potvrdi sada", kopirajte i zalijepite URL ispod u svoj web preglednik:',

        // Password reminder email
        'password_reminder_greeting' => 'Pozdrav!',
        'password_reminder_request' => 'Primili ste ovaj email jer smo zaprimili zahtjev za resetiranje lozinke za vaš račun.',
        'password_reminder_no_action' => 'Ako niste zatražili resetiranje lozinke, nije potrebna daljnja radnja.',
        'password_reminder_trouble' => 'Ako imate problema s klikom na gumb "Resetiraj lozinku", kopirajte i zalijepite URL ispod u svoj web preglednik:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Pozdrav :name,',
        'payment_receipt_title' => 'Potvrda plaćanja za vašu kupnju:',

        // Payment received email (admin)
        'payment_received_message' => 'Primljeno plaćanje od :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name je preuzeo besplatne kredite.',

        // Notice email (consult form)
        'notice_title' => 'Novi zahtjev za konzultaciju',
        'notice_message' => 'Pristigla je nova konzultacija za :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Hvala vam na zahtjevu za konzultaciju!',
        'sender_confirmation_greeting' => 'Poštovani :name,',
        'sender_confirmation_thank_you' => 'Hvala vam što ste nas kontaktirali! Primili smo vaš zahtjev za konzultaciju i naš tim će ga uskoro pregledati. Jedan od naših predstavnika će vam se javiti što je prije moguće.',
        'sender_confirmation_submission_details' => 'Evo detalja vaše prijave:',
        'sender_confirmation_additional_info' => 'Ako imate bilo kakve dodatne informacije ili pitanja, slobodno odgovorite na ovaj email.',
        'sender_confirmation_appreciate' => 'Cijenimo vaše zanimanje i uskoro ćemo vam se javiti!',
    ],
];
