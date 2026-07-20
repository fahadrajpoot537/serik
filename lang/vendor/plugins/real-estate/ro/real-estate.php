<?php

return [
    'name' => 'Imobiliare',
    'settings' => 'Settings',
    'login_form' => 'Formular de autentificare',
    'register_form' => 'Formular de înregistrare',
    'forgot_password_form' => 'Formularul de parolă a uitat',
    'reset_password_form' => 'Resetați formularul de parolă',
    'consult_form' => 'Consultați formularul',
    'review_form' => 'Formular de revizuire',
    'property_translations' => 'Traduceri de proprietăți',
    'project_translations' => 'Traduceri de proiect',
    'theme_options' => [
        'slug_name' => 'URL -uri imobiliare',
        'slug_description' => 'Personalizați slugurile utilizate pentru paginile imobiliare. Fii prudent atunci când modificați, deoarece poate afecta SEO și experiența utilizatorului. Dacă ceva nu merge bine, puteți reseta la implicit tastând valoarea implicită sau lăsați -o necompletată.',
        'page_slug_name' => '__Ph0__ page Slug',
        'page_slug_description' => 'Va arăta ca __ph0__ atunci când accesați pagina. Valoarea implicită este __ph1__.',
        'page_slug_already_exists' => 'Slug -ul de pagină __ph0__ este deja utilizat. Vă rugăm să alegeți altul.',
        'page_slugs' => [
            'projects_city' => 'Proiecte pe oraș',
            'projects_state' => 'Proiecte după stat',
            'properties_city' => 'Proprietăți după oraș',
            'properties_state' => 'Proprietăți după stat',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Resetați parola',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nume:',
        'field_email' => 'E-mail:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Subiect:',
        'field_content' => 'Conţinut:',
        'field_address' => 'Adresa:',
        'field_package' => 'Pachet:',
        'field_price' => 'Preţ:',
        'field_total' => 'Total:',
        'field_account' => 'Cont:',

        // Common greetings and closings
        'greeting_admin' => 'Bună administrator!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Dragă administrator,',
        'closing_regards' => 'Elimi,',
        'closing_thank_you' => 'Mulțumesc',
        'closing_best_regards' => 'Salutări,',

        // Common actions
        'action_view_property' => 'Vizualizați proprietatea',
        'action_verify_now' => 'Verificați acum',
        'action_reset_password' => 'Resetați parola',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/credit',
        'save_amount' => '(Salvați __ph0__)',
        'for_credits' => 'pentru __ph0__ credite',

        // New pending property email
        'new_pending_property_message' => 'O nouă proprietate creată de __ph0__ "__ph1__" așteaptă aprobarea.',

        // Property approved email
        'property_approved_greeting' => 'Bună ziua __ph0__,',
        'property_approved_message' => 'Proprietatea dvs. „__ph0__” a fost aprobată cu succes pe __ph1__.',
        'property_approved_access' => 'Acum puteți accesa site -ul web și vă puteți gestiona proprietatea.',
        'property_approved_view_edit' => 'Pentru a vizualiza sau edita proprietatea, vă rugăm să faceți clic pe acest link:',

        // Property rejected email
        'property_rejected_greeting' => 'Bună ziua __ph0__,',
        'property_rejected_message' => 'Proprietatea dvs. „__ph0__” a fost respinsă cu succes pe __ph1__.',
        'property_rejected_reason' => 'Motivul respingerii este următorul:',
        'property_rejected_contact' => 'Dacă credeți că această decizie a fost luată din greșeală, vă rugăm să contactați echipa noastră de asistență la __ph0__.',
        'property_rejected_view_edit' => 'Pentru a vizualiza sau edita proprietatea, vă rugăm să faceți clic pe acest link:',

        // Account registered email
        'account_registered_message' => 'Un nou cont înregistrat:',

        // Account approved email
        'account_approved_title' => 'Cont aprobat',
        'account_approved_greeting' => 'Dragă __ph0__,',
        'account_approved_congratulations' => 'Felicitări! Contul dvs. pe __ph0__ a fost aprobat.',
        'account_approved_login' => 'Acum vă puteți conecta și începe să utilizați serviciile noastre. Dacă aveți întrebări, nu ezitați să vă adresați echipei noastre de asistență.',
        'account_approved_thanks' => 'Vă mulțumim că v -ați alăturat __ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Cont respins',
        'account_rejected_greeting' => 'Dragă __ph0__,',
        'account_rejected_regret' => 'Regretăm să vă informăm că contul dvs. de pe __ph0__ a fost respins.',
        'account_rejected_reason' => 'Motiv pentru respingere:',
        'account_rejected_contact' => 'Dacă aveți întrebări sau credeți că aceasta este o eroare, vă rugăm să contactați echipa noastră de asistență.',
        'account_rejected_thanks' => 'Vă mulțumim pentru înțelegere.',

        // Confirm email
        'confirm_email_greeting' => 'Buna ziua!',
        'confirm_email_verify' => 'Vă rugăm să verificați adresa de e -mail pentru a accesa acest site web. Faceți clic pe butonul de mai jos pentru a vă verifica e -mailul ..',
        'confirm_email_trouble' => 'Dacă aveți probleme cu clic pe butonul „Verificați acum”, copiați și lipiți URL -ul de mai jos în browserul dvs. web:',

        // Password reminder email
        'password_reminder_greeting' => 'Buna ziua!',
        'password_reminder_request' => 'Primiți acest e -mail pentru că am primit o cerere de resetare a parolei pentru contul dvs.',
        'password_reminder_no_action' => 'Dacă nu ați solicitat o resetare a parolei, nu este necesară o altă acțiune.',
        'password_reminder_trouble' => 'Dacă aveți probleme cu clic pe butonul „Resetați parola”, copiați și lipiți URL -ul de mai jos în browserul dvs. web:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Salut __ph0__,',
        'payment_receipt_title' => 'Chitanță de plată pentru achiziție:',

        // Payment received email (admin)
        'payment_received_message' => 'Plata primită de la __ph0__',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Ph0__ a revendicat credite gratuite.',

        // Notice email (consult form)
        'notice_title' => 'Noua cerere de consultație',
        'notice_message' => 'Există un nou consult de la __ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Vă mulțumim pentru cererea dvs. de consult!',
        'sender_confirmation_greeting' => 'Dragă __ph0__,',
        'sender_confirmation_thank_you' => 'Vă mulțumim că ați ajuns la noi! Am primit cererea dvs. de consultanță și echipa noastră o va examina în curând. Unul dintre reprezentanții noștri vă va reveni cât mai curând posibil.',
        'sender_confirmation_submission_details' => 'Iată detaliile transmiterii dvs .:',
        'sender_confirmation_additional_info' => 'Dacă aveți informații sau întrebări suplimentare, nu ezitați să răspundeți la acest e -mail.',
        'sender_confirmation_appreciate' => 'Apreciem interesul dvs. și vom fi în contact în curând!',
    ],
];
