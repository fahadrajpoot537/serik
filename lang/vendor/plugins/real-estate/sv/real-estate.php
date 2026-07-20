<?php

return [
    'name' => 'Fastighet',
    'settings' => 'Settings',
    'login_form' => 'Inloggningsformulär',
    'register_form' => 'Registerformulär',
    'forgot_password_form' => 'Glömt lösenordsformulär',
    'reset_password_form' => 'Återställ lösenordsformulär',
    'consult_form' => 'Consultformulär',
    'review_form' => 'Granskningsformulär',
    'property_translations' => 'Fastighetsöversättningar',
    'project_translations' => 'Projektöversättningar',
    'theme_options' => [
        'slug_name' => 'Fastighetsadresser',
        'slug_description' => 'Anpassa de sniglar som används för fastighetssidor. Var försiktig när du modifierar eftersom det kan påverka SEO och användarupplevelse. Om något går fel kan du återställa till standard genom att skriva standardvärdet eller lämna det tomt.',
        'page_slug_name' => '__Ph0__ Sidsnig',
        'page_slug_description' => 'Det kommer att se ut som __ph0__ när du kommer åt sidan. Standardvärdet är __ph1__.',
        'page_slug_already_exists' => '__Ph0__ -sidan Slug används redan. Välj en annan.',
        'page_slugs' => [
            'projects_city' => 'Projekt efter stad',
            'projects_state' => 'Projekt efter stat',
            'properties_city' => 'Fastigheter efter stad',
            'properties_state' => 'Egenskaper efter stat',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Återställa lösenord',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Namn:',
        'field_email' => 'E-post:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Ämne:',
        'field_content' => 'Innehåll:',
        'field_address' => 'Adress:',
        'field_package' => 'Paket:',
        'field_price' => 'Pris:',
        'field_total' => 'Total:',
        'field_account' => 'Konto:',

        // Common greetings and closings
        'greeting_admin' => 'Hej administratör!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Kära admin,',
        'closing_regards' => 'Hälsningar,',
        'closing_thank_you' => 'Tack',
        'closing_best_regards' => 'Med vänliga hälsningar,',

        // Common actions
        'action_view_property' => 'Visningsfastigheter',
        'action_verify_now' => 'Verifiera nu',
        'action_reset_password' => 'Återställa lösenord',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kreditera',
        'save_amount' => '(Spara __ph0__)',
        'for_credits' => 'för __ph0__ poäng',

        // New pending property email
        'new_pending_property_message' => 'En ny egenskap skapad av __ph0__ "__ph1__" väntar på godkännande.',

        // Property approved email
        'property_approved_greeting' => 'Hej __ph0__,',
        'property_approved_message' => 'Din egendom "__ph0__" har framgångsrikt godkänts på __ph1__.',
        'property_approved_access' => 'Du kan nu komma åt webbplatsen och hantera din egendom.',
        'property_approved_view_edit' => 'För att visa eller redigera din egendom, klicka på den här länken:',

        // Property rejected email
        'property_rejected_greeting' => 'Hej __ph0__,',
        'property_rejected_message' => 'Din egendom "__ph0__" har framgångsrikt avvisats på __ph1__.',
        'property_rejected_reason' => 'Anledningen till avslag är som följer:',
        'property_rejected_contact' => 'Om du tror att detta beslut fattades, vänligen kontakta vårt supportteam på __ph0__.',
        'property_rejected_view_edit' => 'För att visa eller redigera din egendom, klicka på den här länken:',

        // Account registered email
        'account_registered_message' => 'Ett nytt konto registrerat:',

        // Account approved email
        'account_approved_title' => 'Godkännande',
        'account_approved_greeting' => 'Kära __ph0__,',
        'account_approved_congratulations' => 'Grattis! Ditt konto på __ph0__ har godkänts.',
        'account_approved_login' => 'Du kan nu logga in och börja använda våra tjänster. Om du har några frågor kan du gärna nå ut till vårt supportteam.',
        'account_approved_thanks' => 'Tack för att du gick med __ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Konto avvisad',
        'account_rejected_greeting' => 'Kära __ph0__,',
        'account_rejected_regret' => 'Vi beklagar att informera dig om att ditt konto på __ph0__ har avvisats.',
        'account_rejected_reason' => 'Anledning till avslag:',
        'account_rejected_contact' => 'Om du har några frågor eller tror att detta är ett fel, vänligen kontakta vårt supportteam.',
        'account_rejected_thanks' => 'Tack för din förståelse.',

        // Confirm email
        'confirm_email_greeting' => 'Hej!',
        'confirm_email_verify' => 'Kontrollera din e -postadress för att komma åt denna webbplats. Klicka på knappen nedan för att verifiera din e -post ..',
        'confirm_email_trouble' => 'Om du har problem med att klicka på "Verifiera nu" -knappen, kopiera och klistra in URL nedan i din webbläsare:',

        // Password reminder email
        'password_reminder_greeting' => 'Hej!',
        'password_reminder_request' => 'Du får det här e -postmeddelandet eftersom vi fick en begäran om återställning av lösenord för ditt konto.',
        'password_reminder_no_action' => 'Om du inte begärde en återställning av lösenord krävs ingen ytterligare åtgärder.',
        'password_reminder_trouble' => 'Om du har problem med att klicka på knappen "Återställ lösenord", kopiera och klistra in webbadressen nedan i din webbläsare:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hej __ph0__,',
        'payment_receipt_title' => 'Betalningskvitto för ditt köp:',

        // Payment received email (admin)
        'payment_received_message' => 'Betalning mottagen från __ph0__',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Ph0__ har krävt gratis krediter.',

        // Notice email (consult form)
        'notice_title' => 'Ny konsultförfrågan',
        'notice_message' => 'Det finns ett nytt samråd från __ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Tack för din konsultförfrågan!',
        'sender_confirmation_greeting' => 'Kära __ph0__,',
        'sender_confirmation_thank_you' => 'Tack för att du når ut till oss! Vi har fått din konsultförfrågan och vårt team kommer att granska den inom kort. En av våra representanter kommer tillbaka till dig så snart som möjligt.',
        'sender_confirmation_submission_details' => 'Här är detaljerna om din inlämning:',
        'sender_confirmation_additional_info' => 'Om du har ytterligare information eller frågor kan du gärna svara på det här e -postmeddelandet.',
        'sender_confirmation_appreciate' => 'Vi uppskattar ditt intresse och kommer snart att vara i kontakt!',
    ],
];
