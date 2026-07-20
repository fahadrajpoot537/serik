<?php

return [
    'name' => 'Kinnisvara',
    'settings' => 'Seaded',
    'login_form' => 'Sisselogimise vorm',
    'register_form' => 'Registreerimise vorm',
    'forgot_password_form' => 'Unustatud parooli vorm',
    'reset_password_form' => 'Parooli lähtestamise vorm',
    'consult_form' => 'Konsultatsiooni vorm',
    'review_form' => 'Arvustuse vorm',
    'property_translations' => 'Kinnisvara tõlked',
    'project_translations' => 'Projekti tõlked',
    'theme_options' => [
        'slug_name' => 'Kinnisvara URL-id',
        'slug_description' => 'Kohanda kinnisvara lehtede slugisid. Olge ettevaatlik muutmisel, kuna see võib mõjutada SEO-d ja kasutajakogemust. Kui midagi läheb valesti, saate taastada vaikeväärtuse, tippides vaikeväärtuse või jättes selle tühjaks.',
        'page_slug_name' => ':page lehe slug',
        'page_slug_description' => 'See näeb välja nagu :slug, kui te lehele sisenete. Vaikeväärtus on :default.',
        'page_slug_already_exists' => ':slug lehe slug on juba kasutusel. Palun valige teine.',
        'page_slugs' => [
            'projects_city' => 'Projektid linna järgi',
            'projects_state' => 'Projektid osariigi järgi',
            'properties_city' => 'Kinnisvara linna järgi',
            'properties_state' => 'Kinnisvara osariigi järgi',
            'login' => 'Logi sisse',
            'register' => 'Registreeru',
            'reset_password' => 'Lähtesta parool',
        ],
    ],
    'email_templates' => [
        'field_name' => 'Nimi:',
        'field_email' => 'E-post:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Teema:',
        'field_content' => 'Sisu:',
        'field_address' => 'Aadress:',
        'field_package' => 'Pakett:',
        'field_price' => 'Hind:',
        'field_total' => 'Kokku:',
        'field_account' => 'Konto:',

        'greeting_admin' => 'Tere administraator!',
        'greeting_hello' => 'Tere',
        'greeting_dear' => 'Lugupeetud',
        'greeting_dear_admin' => 'Lugupeetud administraator,',
        'closing_regards' => 'Lugupidamisega,',
        'closing_thank_you' => 'Täname teid',
        'closing_best_regards' => 'Parimate soovidega,',

        'action_view_property' => 'Vaata kinnisvara',
        'action_verify_now' => 'Kontrolli kohe',
        'action_reset_password' => 'Lähtesta parool',

        'credits' => 'krediiti',
        'per_credit' => '/krediit',
        'save_amount' => '(Säästa :amount)',
        'for_credits' => ':count krediidi eest',

        'new_pending_property_message' => 'Uus kinnisvara, mille lõi :author ":name", ootab kinnitust.',

        'property_approved_greeting' => 'Tere :name,',
        'property_approved_message' => 'Teie kinnisvara ":property_name" on edukalt kinnitatud saidil :site_name.',
        'property_approved_access' => 'Nüüd saate veebilehele siseneda ja oma kinnisvara hallata.',
        'property_approved_view_edit' => 'Oma kinnisvara vaatamiseks või muutmiseks klõpsake sellel lingil:',

        'property_rejected_greeting' => 'Tere :name,',
        'property_rejected_message' => 'Teie kinnisvara ":property_name" on saidil :site_name tagasi lükatud.',
        'property_rejected_reason' => 'Tagasilükkamise põhjus on järgmine:',
        'property_rejected_contact' => 'Kui arvate, et see otsus tehti ekslikult, võtke ühendust meie tugimeeskonnaga aadressil :email.',
        'property_rejected_view_edit' => 'Oma kinnisvara vaatamiseks või muutmiseks klõpsake sellel lingil:',

        'account_registered_message' => 'Uus konto registreeritud:',

        'account_approved_title' => 'Konto kinnitatud',
        'account_approved_greeting' => 'Lugupeetud :name,',
        'account_approved_congratulations' => 'Palju õnne! Teie konto saidil :site_name on kinnitatud.',
        'account_approved_login' => 'Nüüd saate sisse logida ja hakata meie teenuseid kasutama. Kui teil on küsimusi, võtke julgelt ühendust meie tugimeeskonnaga.',
        'account_approved_thanks' => 'Täname, et liitusite saidiga :site_name!',

        'account_rejected_title' => 'Konto tagasi lükatud',
        'account_rejected_greeting' => 'Lugupeetud :name,',
        'account_rejected_regret' => 'Kahjuks peame teatama, et teie konto saidil :site_name on tagasi lükatud.',
        'account_rejected_reason' => 'Tagasilükkamise põhjus:',
        'account_rejected_contact' => 'Kui teil on küsimusi või arvate, et see on viga, võtke ühendust meie tugimeeskonnaga.',
        'account_rejected_thanks' => 'Täname teie mõistva suhtumise eest.',

        'confirm_email_greeting' => 'Tere!',
        'confirm_email_verify' => 'Palun kontrollige oma e-posti aadressi, et sellele veebilehele siseneda. Klõpsake alloleval nupul oma e-posti kontrollimiseks.',
        'confirm_email_trouble' => 'Kui teil on probleeme nupu "Kontrolli kohe" klõpsamisega, kopeerige ja kleepige allolev URL oma veebibrauserisse:',

        'password_reminder_greeting' => 'Tere!',
        'password_reminder_request' => 'Saate selle e-kirja, kuna saime teie konto jaoks parooli lähtestamise taotluse.',
        'password_reminder_no_action' => 'Kui te ei taotlenud parooli lähtestamist, ei ole vaja midagi teha.',
        'password_reminder_trouble' => 'Kui teil on probleeme nupu "Lähtesta parool" klõpsamisega, kopeerige ja kleepige allolev URL oma veebibrauserisse:',

        'payment_receipt_greeting' => 'Tere :name,',
        'payment_receipt_title' => 'Makse kviitung teie ostu kohta:',

        'payment_received_message' => 'Makse vastu võetud kasutajalt :name',

        'free_credit_claimed_message' => ':name on nõudnud tasuta krediiti.',

        'notice_title' => 'Uus konsultatsioonipäring',
        'notice_message' => 'Uus konsultatsioon kinnisvaralt :property_name',

        'sender_confirmation_title' => 'Täname teid konsultatsioonipäringu eest!',
        'sender_confirmation_greeting' => 'Lugupeetud :name,',
        'sender_confirmation_thank_you' => 'Täname, et võtsite meiega ühendust! Oleme teie konsultatsioonipäringu kätte saanud ja meie meeskond vaatab selle lähiajal läbi. Üks meie esindajatest võtab teiega võimalikult kiiresti ühendust.',
        'sender_confirmation_submission_details' => 'Siin on teie esitatud andmed:',
        'sender_confirmation_additional_info' => 'Kui teil on lisainformatsiooni või küsimusi, vastake julgelt sellele e-kirjale.',
        'sender_confirmation_appreciate' => 'Hindame teie huvi ja võtame teiega varsti ühendust!',
    ],
];
