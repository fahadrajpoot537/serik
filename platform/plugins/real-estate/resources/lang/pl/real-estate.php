<?php

return [
    'name' => 'Nieruchomość',
    'settings' => 'Settings',
    'login_form' => 'Formularz logowania',
    'register_form' => 'Formularz rejestracyjny',
    'forgot_password_form' => 'Zapomniałem hasła. Formularz',
    'reset_password_form' => 'Zresetuj formularz hasła',
    'consult_form' => 'Skonsultuj się z formularzem',
    'review_form' => 'Formularz recenzji',
    'property_translations' => 'Tłumaczenia nieruchomości',
    'project_translations' => 'Tłumaczenia projektów',
    'theme_options' => [
        'slug_name' => 'Adresy URL nieruchomości',
        'slug_description' => 'Dostosuj ślimaki używane na stronach z nieruchomościami. Zachowaj ostrożność podczas modyfikowania, ponieważ może to mieć wpływ na SEO i wygodę użytkownika. Jeśli coś pójdzie nie tak, możesz przywrócić ustawienia domyślne, wpisując wartość domyślną lub pozostawiając ją pustą.',
        'page_slug_name' => ':page błąd strony',
        'page_slug_description' => 'Po wejściu na stronę będzie wyglądać jak :slug. Wartość domyślna to :default.',
        'page_slug_already_exists' => 'Ślimak strony :slug jest już używany. Proszę wybrać inny.',
        'page_slugs' => [
            'projects_city' => 'Projekty według miasta',
            'projects_state' => 'Projekty według stanu',
            'properties_city' => 'Nieruchomości według miasta',
            'properties_state' => 'Właściwości według stanu',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Zresetuj hasło',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nazwa:',
        'field_email' => 'E-mail:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Temat:',
        'field_content' => 'Treść:',
        'field_address' => 'Adres:',
        'field_package' => 'Pakiet:',
        'field_price' => 'Cena:',
        'field_total' => 'Całkowity:',
        'field_account' => 'Konto:',

        // Common greetings and closings
        'greeting_admin' => 'Cześć Adminie!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Drogi Adminie,',
        'closing_regards' => 'Pozdrowienia,',
        'closing_thank_you' => 'Dziękuję',
        'closing_best_regards' => 'Z wyrazami szacunku,',

        // Common actions
        'action_view_property' => 'Zobacz nieruchomość',
        'action_verify_now' => 'Sprawdź teraz',
        'action_reset_password' => 'Zresetuj hasło',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kredyt',
        'save_amount' => '(Zapisz :amount)',
        'for_credits' => 'za :count kredytów',

        // New pending property email
        'new_pending_property_message' => 'Nowa właściwość utworzona przez :author „:name” czeka na zatwierdzenie.',

        // Property approved email
        'property_approved_greeting' => 'Witaj :name,',
        'property_approved_message' => 'Twoja nieruchomość „:property_name” została pomyślnie zatwierdzona w dniu :site_name.',
        'property_approved_access' => 'Możesz teraz uzyskać dostęp do strony internetowej i zarządzać swoją nieruchomością.',
        'property_approved_view_edit' => 'Aby wyświetlić lub edytować swoją nieruchomość, kliknij ten link:',

        // Property rejected email
        'property_rejected_greeting' => 'Witaj :name,',
        'property_rejected_message' => 'Twoja nieruchomość „:property_name” została pomyślnie odrzucona w dniu :site_name.',
        'property_rejected_reason' => 'Powód odrzucenia jest następujący:',
        'property_rejected_contact' => 'Jeśli uważasz, że ta decyzja została podjęta przez pomyłkę, skontaktuj się z naszym zespołem pomocy technicznej pod adresem :email.',
        'property_rejected_view_edit' => 'Aby wyświetlić lub edytować swoją nieruchomość, kliknij ten link:',

        // Account registered email
        'account_registered_message' => 'Zarejestrowano nowe konto:',

        // Account approved email
        'account_approved_title' => 'Konto zatwierdzone',
        'account_approved_greeting' => 'Drogi :name,',
        'account_approved_congratulations' => 'Gratulacje! Twoje konto na :site_name zostało zatwierdzone.',
        'account_approved_login' => 'Możesz już zalogować się i zacząć korzystać z naszych usług. Jeśli masz jakiekolwiek pytania, skontaktuj się z naszym zespołem wsparcia.',
        'account_approved_thanks' => 'Dziękujemy za przyłączenie się do :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Konto odrzucone',
        'account_rejected_greeting' => 'Drogi :name,',
        'account_rejected_regret' => 'Z przykrością informujemy, że Twoje konto na :site_name zostało odrzucone.',
        'account_rejected_reason' => 'Powód odrzucenia:',
        'account_rejected_contact' => 'Jeśli masz jakieś pytania lub uważasz, że to błąd, skontaktuj się z naszym zespołem wsparcia.',
        'account_rejected_thanks' => 'Dziękuję za zrozumienie.',

        // Confirm email
        'confirm_email_greeting' => 'Cześć!',
        'confirm_email_verify' => 'Aby uzyskać dostęp do tej witryny, zweryfikuj swój adres e-mail. Kliknij przycisk poniżej, aby zweryfikować swój adres e-mail..',
        'confirm_email_trouble' => 'Jeśli masz problemy z kliknięciem przycisku „Zweryfikuj teraz”, skopiuj i wklej poniższy adres URL do swojej przeglądarki internetowej:',

        // Password reminder email
        'password_reminder_greeting' => 'Cześć!',
        'password_reminder_request' => 'Otrzymujesz tego e-maila, ponieważ otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.',
        'password_reminder_no_action' => 'Jeśli nie poprosiłeś o zresetowanie hasła, nie są wymagane żadne dalsze działania.',
        'password_reminder_trouble' => 'Jeśli masz problemy z kliknięciem przycisku „Resetuj hasło”, skopiuj i wklej poniższy adres URL do swojej przeglądarki internetowej:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Cześć :name,',
        'payment_receipt_title' => 'Potwierdzenie płatności za zakup:',

        // Payment received email (admin)
        'payment_received_message' => 'Płatność otrzymana od :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name odebrał darmowe środki.',

        // Notice email (consult form)
        'notice_title' => 'Nowa prośba o konsultację',
        'notice_message' => 'Jest nowa konsultacja od :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Dziękujemy za Twoją prośbę o konsultację!',
        'sender_confirmation_greeting' => 'Drogi :name,',
        'sender_confirmation_thank_you' => 'Dziękujemy, że się z nami skontaktowałeś! Otrzymaliśmy Twoją prośbę o konsultację i nasz zespół wkrótce ją sprawdzi. Jeden z naszych przedstawicieli skontaktuje się z Tobą tak szybko, jak to możliwe.',
        'sender_confirmation_submission_details' => 'Oto szczegóły Twojego zgłoszenia:',
        'sender_confirmation_additional_info' => 'Jeżeli mają Państwo dodatkowe informacje lub pytania, prosimy odpowiedzieć na tę wiadomość e-mail.',
        'sender_confirmation_appreciate' => 'Dziękujemy za zainteresowanie i wkrótce się z Tobą skontaktujemy!',
    ],
];
