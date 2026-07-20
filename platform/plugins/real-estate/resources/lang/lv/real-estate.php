<?php

return [
    'name' => 'Nekustamais īpašums',
    'settings' => 'Settings',
    'login_form' => 'Pieteikšanās forma',
    'register_form' => 'Reģistrēties',
    'forgot_password_form' => 'Aizmirsāt paroles veidlapu',
    'reset_password_form' => 'Atiestatīt paroles veidlapu',
    'consult_form' => 'Konsultēties',
    'review_form' => 'Pārskatīšanas forma',
    'property_translations' => 'Īpašuma tulkojumi',
    'project_translations' => 'Projekta tulkojumi',
    'theme_options' => [
        'slug_name' => 'Nekustamā īpašuma URL',
        'slug_description' => 'Pielāgojiet lodes, ko izmanto nekustamā īpašuma lapām. Esiet piesardzīgs, mainoties, jo tas var ietekmēt SEO un lietotāja pieredzi. Ja kaut kas noiet greizi, varat atiestatīt uz noklusējumu, ierakstot noklusējuma vērtību vai atstājot to tukšu.',
        'page_slug_name' => '__Ph0__ lapas lode',
        'page_slug_description' => 'Piekļūstot lapai, tas izskatīsies kā __ph0__. Noklusējuma vērtība ir __ph1__.',
        'page_slug_already_exists' => '__Ph0__ lapas lode jau tiek izmantota. Lūdzu, izvēlieties citu.',
        'page_slugs' => [
            'projects_city' => 'Projekti pēc pilsētas',
            'projects_state' => 'Projekti pēc valsts',
            'properties_city' => 'Īpašumi pēc pilsētas',
            'properties_state' => 'Īpašības pēc stāvokļa',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Atiestatīt paroli',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nosaukums:',
        'field_email' => 'E -pasts:',
        'field_phone' => 'Tālrunis:',
        'field_subject' => 'Temats:',
        'field_content' => 'Saturs:',
        'field_address' => 'Adrese:',
        'field_package' => 'Pakete:',
        'field_price' => 'Cena:',
        'field_total' => 'Kopsumma:',
        'field_account' => 'Konts:',

        // Common greetings and closings
        'greeting_admin' => 'Sveiks, admin!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Dārgais admin,',
        'closing_regards' => 'Ar cieņu,',
        'closing_thank_you' => 'Paldies',
        'closing_best_regards' => 'Ar cieņu,',

        // Common actions
        'action_view_property' => 'Skatīt īpašumu',
        'action_verify_now' => 'Pārbaudiet tūlīt',
        'action_reset_password' => 'Atiestatīt paroli',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kredīts',
        'save_amount' => '(Saglabāt __ph0__)',
        'for_credits' => 'par __ph0__ kredītiem',

        // New pending property email
        'new_pending_property_message' => 'Apstipriniet jaunu īpašumu, ko izveidojis __ph0__ "__ph1__".',

        // Property approved email
        'property_approved_greeting' => 'Sveiki __ph0__,',
        'property_approved_message' => 'Jūsu īpašums "__ph0__" ir veiksmīgi apstiprināts __ph1__.',
        'property_approved_access' => 'Tagad jūs varat piekļūt vietnei un pārvaldīt savu īpašumu.',
        'property_approved_view_edit' => 'Lai apskatītu vai rediģētu savu īpašumu, lūdzu, noklikšķiniet uz šīs saites:',

        // Property rejected email
        'property_rejected_greeting' => 'Sveiki __ph0__,',
        'property_rejected_message' => 'Jūsu īpašums "__ph0__" ir veiksmīgi noraidīts uz __ph1__.',
        'property_rejected_reason' => 'Noraidījuma iemesls ir šāds:',
        'property_rejected_contact' => 'Ja uzskatāt, ka šis lēmums tika pieņemts kļūdaini, lūdzu, sazinieties ar mūsu atbalsta komandu __ph0__.',
        'property_rejected_view_edit' => 'Lai apskatītu vai rediģētu savu īpašumu, lūdzu, noklikšķiniet uz šīs saites:',

        // Account registered email
        'account_registered_message' => 'Reģistrēts jauns konts:',

        // Account approved email
        'account_approved_title' => 'Konts apstiprināts',
        'account_approved_greeting' => 'Dārgais __ph0__,',
        'account_approved_congratulations' => 'Apsveicam! Jūsu konts __ph0__ ir apstiprināts.',
        'account_approved_login' => 'Tagad jūs varat pieteikties un sākt izmantot mūsu pakalpojumus. Ja jums ir kādi jautājumi, nekautrējieties sazināties ar mūsu atbalsta komandu.',
        'account_approved_thanks' => 'Paldies, ka pievienojāties __ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Konts noraidīts',
        'account_rejected_greeting' => 'Dārgais __ph0__,',
        'account_rejected_regret' => 'Mēs ar nožēlu informējam jūs, ka jūsu konts __ph0__ ir noraidīts.',
        'account_rejected_reason' => 'Noraidījuma iemesls:',
        'account_rejected_contact' => 'Ja jums ir kādi jautājumi vai ticat, ka tā ir kļūda, lūdzu, sazinieties ar mūsu atbalsta komandu.',
        'account_rejected_thanks' => 'Paldies par sapratni.',

        // Confirm email
        'confirm_email_greeting' => 'Sveiki!',
        'confirm_email_verify' => 'Lūdzu, pārbaudiet savu e -pasta adresi, lai piekļūtu šai vietnei. Noklikšķiniet uz pogas zemāk, lai pārbaudītu savu e -pastu ..',
        'confirm_email_trouble' => 'Ja jums ir grūtības noklikšķināt uz pogas "Pārbaudiet tūlīt", nokopējiet un ielīmējiet zemāk esošo URL savā tīmekļa pārlūkprogrammā:',

        // Password reminder email
        'password_reminder_greeting' => 'Sveiki!',
        'password_reminder_request' => 'Jūs saņemat šo e -pastu, jo mēs saņēmām jūsu konta paroles atiestatīšanas pieprasījumu.',
        'password_reminder_no_action' => 'Ja jūs nepieprasījāt paroles atiestatīšanu, turpmāka darbība nav nepieciešama.',
        'password_reminder_trouble' => 'Ja jums ir grūtības noklikšķināt uz pogas "Atiestatīt paroli", kopējiet un ielīmējiet zemāk esošo URL savā tīmekļa pārlūkprogrammā:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Čau __ph0__,',
        'payment_receipt_title' => 'Maksājuma kvīts par pirkumu:',

        // Payment received email (admin)
        'payment_received_message' => 'Maksājums, kas saņemts no __ph0__',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Ph0__ ir pieprasījis bezmaksas kredītus.',

        // Notice email (consult form)
        'notice_title' => 'Jauns konsultācijas pieprasījums',
        'notice_message' => 'Ir jauna konsultācija no __ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Paldies par jūsu konsultācijas pieprasījumu!',
        'sender_confirmation_greeting' => 'Dārgais __ph0__,',
        'sender_confirmation_thank_you' => 'Paldies, ka sazinājāties ar mums! Mēs esam saņēmuši jūsu konsultācijas pieprasījumu, un mūsu komanda to drīz pārskatīs. Viens no mūsu pārstāvjiem pēc iespējas ātrāk sazināsies ar jums.',
        'sender_confirmation_submission_details' => 'Šeit ir sīkāka informācija par jūsu iesniegumu:',
        'sender_confirmation_additional_info' => 'Ja jums ir kāda papildu informācija vai jautājumi, nekautrējieties atbildēt uz šo e -pastu.',
        'sender_confirmation_appreciate' => 'Mēs novērtējam jūsu interesi un drīz sazināsimies!',
    ],
];
