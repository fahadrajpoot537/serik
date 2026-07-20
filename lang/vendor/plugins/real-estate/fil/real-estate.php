<?php

return [
    'name' => 'Real Estate',
    'settings' => 'Mga Setting',
    'login_form' => 'Form ng Login',
    'register_form' => 'Form ng Pagrehistro',
    'forgot_password_form' => 'Form ng Nakalimutang Password',
    'reset_password_form' => 'Form ng Pag-reset ng Password',
    'consult_form' => 'Form ng Konsulta',
    'review_form' => 'Form ng Review',
    'property_translations' => 'Mga Pagsasalin ng Ari-arian',
    'project_translations' => 'Mga Pagsasalin ng Proyekto',
    'theme_options' => [
        'slug_name' => 'Mga URL ng Real Estate',
        'slug_description' => 'I-customize ang mga slug na ginagamit para sa mga pahina ng real estate. Mag-ingat kapag nag-modify dahil maaari itong makaapekto sa SEO at karanasan ng user. Kung may mangyaring mali, maaari mong i-reset sa default sa pamamagitan ng pag-type ng default value o pag-iwan nitong blangko.',
        'page_slug_name' => ':page slug ng pahina',
        'page_slug_description' => 'Ito ay magiging ganito :slug kapag nag-access ka sa pahina. Ang default value ay :default.',
        'page_slug_already_exists' => 'Ang :slug slug ng pahina ay ginagamit na. Mangyaring pumili ng iba.',
        'page_slugs' => [
            'projects_city' => 'Mga Proyekto ayon sa Lungsod',
            'projects_state' => 'Mga Proyekto ayon sa Estado',
            'properties_city' => 'Mga Ari-arian ayon sa Lungsod',
            'properties_state' => 'Mga Ari-arian ayon sa Estado',
            'login' => 'Login',
            'register' => 'Magrehistro',
            'reset_password' => 'I-reset ang Password',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Pangalan:',
        'field_email' => 'Email:',
        'field_phone' => 'Telepono:',
        'field_subject' => 'Paksa:',
        'field_content' => 'Nilalaman:',
        'field_address' => 'Address:',
        'field_package' => 'Package:',
        'field_price' => 'Presyo:',
        'field_total' => 'Kabuuan:',
        'field_account' => 'Account:',

        // Common greetings and closings
        'greeting_admin' => 'Hi Admin!',
        'greeting_hello' => 'Kumusta',
        'greeting_dear' => 'Mahal na',
        'greeting_dear_admin' => 'Mahal na Admin,',
        'closing_regards' => 'Sumasaiyo,',
        'closing_thank_you' => 'Salamat',
        'closing_best_regards' => 'Lubos na gumagalang,',

        // Common actions
        'action_view_property' => 'Tingnan ang Ari-arian',
        'action_verify_now' => 'I-verify ngayon',
        'action_reset_password' => 'I-reset ang password',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/credit',
        'save_amount' => '(Makatipid ng :amount)',
        'for_credits' => 'para sa :count credits',

        // New pending property email
        'new_pending_property_message' => 'Ang bagong ari-arian na nilikha ni :author ":name" ay naghihintay ng pag-apruba.',

        // Property approved email
        'property_approved_greeting' => 'Kumusta :name,',
        'property_approved_message' => 'Ang iyong ari-arian na ":property_name" ay matagumpay na naaprubahan sa :site_name.',
        'property_approved_access' => 'Maaari mo na ngayong ma-access ang website at pamahalaan ang iyong ari-arian.',
        'property_approved_view_edit' => 'Upang tingnan o i-edit ang iyong ari-arian, mangyaring mag-click sa link na ito:',

        // Property rejected email
        'property_rejected_greeting' => 'Kumusta :name,',
        'property_rejected_message' => 'Ang iyong ari-arian na ":property_name" ay tinanggihan sa :site_name.',
        'property_rejected_reason' => 'Ang dahilan ng pagtanggi ay ang sumusunod:',
        'property_rejected_contact' => 'Kung naniniwala ka na ang desisyong ito ay isang pagkakamali, mangyaring makipag-ugnayan sa aming support team sa :email.',
        'property_rejected_view_edit' => 'Upang tingnan o i-edit ang iyong ari-arian, mangyaring mag-click sa link na ito:',

        // Account registered email
        'account_registered_message' => 'Isang bagong account ang nagrehistro:',

        // Account approved email
        'account_approved_title' => 'Naaprubahan ang Account',
        'account_approved_greeting' => 'Mahal na :name,',
        'account_approved_congratulations' => 'Binabati kita! Ang iyong account sa :site_name ay naaprubahan na.',
        'account_approved_login' => 'Maaari ka na ngayong mag-log in at simulang gamitin ang aming mga serbisyo. Kung mayroon kang mga tanong, huwag mag-atubiling makipag-ugnayan sa aming support team.',
        'account_approved_thanks' => 'Salamat sa pagsali sa :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Tinanggihan ang Account',
        'account_rejected_greeting' => 'Mahal na :name,',
        'account_rejected_regret' => 'Ikinalulungkot naming ipaalam sa iyo na ang iyong account sa :site_name ay tinanggihan.',
        'account_rejected_reason' => 'Dahilan ng pagtanggi:',
        'account_rejected_contact' => 'Kung mayroon kang mga tanong o naniniwala ka na ito ay isang pagkakamali, mangyaring makipag-ugnayan sa aming support team.',
        'account_rejected_thanks' => 'Salamat sa iyong pag-unawa.',

        // Confirm email
        'confirm_email_greeting' => 'Kumusta!',
        'confirm_email_verify' => 'Mangyaring i-verify ang iyong email address upang ma-access ang website na ito. Mag-click sa button sa ibaba upang i-verify ang iyong email.',
        'confirm_email_trouble' => 'Kung may problema ka sa pag-click ng button na "I-verify ngayon", kopyahin at i-paste ang URL sa ibaba sa iyong web browser:',

        // Password reminder email
        'password_reminder_greeting' => 'Kumusta!',
        'password_reminder_request' => 'Nakatanggap ka ng email na ito dahil nakatanggap kami ng kahilingan para sa pag-reset ng password para sa iyong account.',
        'password_reminder_no_action' => 'Kung hindi ka humingi ng pag-reset ng password, walang karagdagang aksyon na kinakailangan.',
        'password_reminder_trouble' => 'Kung may problema ka sa pag-click ng button na "I-reset ang Password", kopyahin at i-paste ang URL sa ibaba sa iyong web browser:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hi :name,',
        'payment_receipt_title' => 'Resibo ng pagbabayad para sa iyong pagbili:',

        // Payment received email (admin)
        'payment_received_message' => 'Nakatanggap ng bayad mula kay :name',

        // Free credit claimed email
        'free_credit_claimed_message' => 'Nag-claim si :name ng mga libreng credit.',

        // Notice email (consult form)
        'notice_title' => 'Bagong Kahilingan ng Konsulta',
        'notice_message' => 'May bagong konsulta mula sa :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Salamat sa Iyong Kahilingan ng Konsulta!',
        'sender_confirmation_greeting' => 'Mahal na :name,',
        'sender_confirmation_thank_you' => 'Salamat sa pakikipag-ugnayan sa amin! Natanggap namin ang iyong kahilingan ng konsulta at susuriin ito ng aming team sa lalong madaling panahon. Isa sa aming mga kinatawan ay makikipag-ugnayan sa iyo sa lalong madaling panahon.',
        'sender_confirmation_submission_details' => 'Narito ang mga detalye ng iyong pagsusumite:',
        'sender_confirmation_additional_info' => 'Kung mayroon kang karagdagang impormasyon o mga tanong, huwag mag-atubiling tumugon sa email na ito.',
        'sender_confirmation_appreciate' => 'Pinahahalagahan namin ang iyong interes at makikipag-ugnayan kami sa lalong madaling panahon!',
    ],
];
