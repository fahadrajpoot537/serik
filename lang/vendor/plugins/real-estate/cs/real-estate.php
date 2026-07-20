<?php

return [
    'name' => 'Reality',
    'settings' => 'Nastavení',
    'login_form' => 'Přihlašovací formulář',
    'register_form' => 'Registrační formulář',
    'forgot_password_form' => 'Formulář zapomenutého hesla',
    'reset_password_form' => 'Formulář obnovení hesla',
    'consult_form' => 'Konzultační formulář',
    'review_form' => 'Formulář recenze',
    'property_translations' => 'Překlady nemovitostí',
    'project_translations' => 'Překlady projektů',
    'theme_options' => [
        'slug_name' => 'URL reality',
        'slug_description' => 'Přizpůsobte slugy používané pro stránky realit. Při úpravách buďte opatrní, protože to může ovlivnit SEO a uživatelskou zkušenost. Pokud se něco pokazí, můžete obnovit výchozí hodnotu zadáním výchozí hodnoty nebo ponecháním prázdné.',
        'page_slug_name' => 'Slug stránky :page',
        'page_slug_description' => 'Bude vypadat jako :slug když přistoupíte na stránku. Výchozí hodnota je :default.',
        'page_slug_already_exists' => 'Slug stránky :slug je již používán. Vyberte prosím jiný.',
        'page_slugs' => [
            'projects_city' => 'Projekty podle města',
            'projects_state' => 'Projekty podle státu',
            'properties_city' => 'Nemovitosti podle města',
            'properties_state' => 'Nemovitosti podle státu',
            'login' => 'Přihlášení',
            'register' => 'Registrace',
            'reset_password' => 'Obnovení hesla',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Jméno:',
        'field_email' => 'E-mail:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Předmět:',
        'field_content' => 'Obsah:',
        'field_address' => 'Adresa:',
        'field_package' => 'Balíček:',
        'field_price' => 'Cena:',
        'field_total' => 'Celkem:',
        'field_account' => 'Účet:',

        // Common greetings and closings
        'greeting_admin' => 'Ahoj administrátore!',
        'greeting_hello' => 'Ahoj',
        'greeting_dear' => 'Vážený/á',
        'greeting_dear_admin' => 'Vážený administrátore,',
        'closing_regards' => 'S pozdravem,',
        'closing_thank_you' => 'Děkujeme',
        'closing_best_regards' => 'S přátelským pozdravem,',

        // Common actions
        'action_view_property' => 'Zobrazit nemovitost',
        'action_verify_now' => 'Ověřit nyní',
        'action_reset_password' => 'Obnovit heslo',

        // Common terms
        'credits' => 'kredity',
        'per_credit' => '/kredit',
        'save_amount' => '(Ušetříte :amount)',
        'for_credits' => 'za :count kreditů',

        // New pending property email
        'new_pending_property_message' => 'Nová nemovitost vytvořená :author ":name" čeká na schválení.',

        // Property approved email
        'property_approved_greeting' => 'Ahoj :name,',
        'property_approved_message' => 'Vaše nemovitost ":property_name" byla úspěšně schválena na :site_name.',
        'property_approved_access' => 'Nyní můžete přistupovat na webové stránky a spravovat svou nemovitost.',
        'property_approved_view_edit' => 'Pro zobrazení nebo úpravu vaší nemovitosti klikněte na tento odkaz:',

        // Property rejected email
        'property_rejected_greeting' => 'Ahoj :name,',
        'property_rejected_message' => 'Vaše nemovitost ":property_name" byla zamítnuta na :site_name.',
        'property_rejected_reason' => 'Důvod zamítnutí je následující:',
        'property_rejected_contact' => 'Pokud si myslíte, že toto rozhodnutí bylo chybné, kontaktujte prosím náš tým podpory na :email.',
        'property_rejected_view_edit' => 'Pro zobrazení nebo úpravu vaší nemovitosti klikněte na tento odkaz:',

        // Account registered email
        'account_registered_message' => 'Zaregistrován nový účet:',

        // Account approved email
        'account_approved_title' => 'Účet schválen',
        'account_approved_greeting' => 'Vážený/á :name,',
        'account_approved_congratulations' => 'Gratulujeme! Váš účet na :site_name byl schválen.',
        'account_approved_login' => 'Nyní se můžete přihlásit a začít používat naše služby. Pokud máte jakékoli otázky, neváhejte kontaktovat náš tým podpory.',
        'account_approved_thanks' => 'Děkujeme, že jste se připojili k :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Účet zamítnut',
        'account_rejected_greeting' => 'Vážený/á :name,',
        'account_rejected_regret' => 'Je nám líto, že vás musíme informovat, že váš účet na :site_name byl zamítnut.',
        'account_rejected_reason' => 'Důvod zamítnutí:',
        'account_rejected_contact' => 'Pokud máte jakékoli otázky nebo si myslíte, že se jedná o chybu, kontaktujte prosím náš tým podpory.',
        'account_rejected_thanks' => 'Děkujeme za pochopení.',

        // Confirm email
        'confirm_email_greeting' => 'Ahoj!',
        'confirm_email_verify' => 'Ověřte prosím svou e-mailovou adresu, abyste mohli přistupovat na tyto webové stránky. Klikněte na tlačítko níže pro ověření vašeho e-mailu.',
        'confirm_email_trouble' => 'Pokud máte problémy s kliknutím na tlačítko "Ověřit nyní", zkopírujte a vložte níže uvedenou URL do vašeho webového prohlížeče:',

        // Password reminder email
        'password_reminder_greeting' => 'Ahoj!',
        'password_reminder_request' => 'Tento e-mail jste obdrželi, protože jsme obdrželi žádost o obnovení hesla pro váš účet.',
        'password_reminder_no_action' => 'Pokud jste nepožádali o obnovení hesla, není potřeba žádná další akce.',
        'password_reminder_trouble' => 'Pokud máte problémy s kliknutím na tlačítko "Obnovit heslo", zkopírujte a vložte níže uvedenou URL do vašeho webového prohlížeče:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Ahoj :name,',
        'payment_receipt_title' => 'Potvrzení o platbě za váš nákup:',

        // Payment received email (admin)
        'payment_received_message' => 'Přijata platba od :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name si uplatnil/a bezplatné kredity.',

        // Notice email (consult form)
        'notice_title' => 'Nová žádost o konzultaci',
        'notice_message' => 'Je tu nová konzultace od :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Děkujeme za vaši žádost o konzultaci!',
        'sender_confirmation_greeting' => 'Vážený/á :name,',
        'sender_confirmation_thank_you' => 'Děkujeme, že jste nás kontaktovali! Obdrželi jsme vaši žádost o konzultaci a náš tým ji brzy zkontroluje. Jeden z našich zástupců se vám co nejdříve ozve.',
        'sender_confirmation_submission_details' => 'Zde jsou podrobnosti vašeho podání:',
        'sender_confirmation_additional_info' => 'Pokud máte jakékoli další informace nebo otázky, neváhejte odpovědět na tento e-mail.',
        'sender_confirmation_appreciate' => 'Vážíme si vašeho zájmu a brzy se vám ozveme!',
    ],
];
