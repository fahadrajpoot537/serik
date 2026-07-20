<?php

return [
    'name' => 'Недвижими имоти',
    'settings' => 'Настройки',
    'login_form' => 'Форма за вход',
    'register_form' => 'Форма за регистрация',
    'forgot_password_form' => 'Форма за забравена парола',
    'reset_password_form' => 'Форма за нулиране на парола',
    'consult_form' => 'Форма за консултация',
    'review_form' => 'Форма за отзив',
    'property_translations' => 'Преводи на имоти',
    'project_translations' => 'Преводи на проекти',
    'theme_options' => [
        'slug_name' => 'URL адреси за недвижими имоти',
        'slug_description' => 'Персонализирайте слъговете, използвани за страници с недвижими имоти. Бъдете внимателни при промяна, тъй като може да повлияе на SEO и потребителското изживяване. Ако нещо се обърка, можете да върнете стойността по подразбиране, като въведете стойността по подразбиране или оставите празно.',
        'page_slug_name' => 'Слъг на страница :page',
        'page_slug_description' => 'Ще изглежда като :slug, когато отворите страницата. Стойността по подразбиране е :default.',
        'page_slug_already_exists' => 'Слъгът :slug на страницата вече се използва. Моля, изберете друг.',
        'page_slugs' => [
            'projects_city' => 'Проекти по град',
            'projects_state' => 'Проекти по област',
            'properties_city' => 'Имоти по град',
            'properties_state' => 'Имоти по област',
            'login' => 'Вход',
            'register' => 'Регистрация',
            'reset_password' => 'Нулиране на парола',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Име:',
        'field_email' => 'Имейл:',
        'field_phone' => 'Телефон:',
        'field_subject' => 'Тема:',
        'field_content' => 'Съдържание:',
        'field_address' => 'Адрес:',
        'field_package' => 'Пакет:',
        'field_price' => 'Цена:',
        'field_total' => 'Общо:',
        'field_account' => 'Акаунт:',

        // Common greetings and closings
        'greeting_admin' => 'Здравейте, Администратор!',
        'greeting_hello' => 'Здравейте',
        'greeting_dear' => 'Уважаеми',
        'greeting_dear_admin' => 'Уважаеми администратор,',
        'closing_regards' => 'Поздрави,',
        'closing_thank_you' => 'Благодаря ви',
        'closing_best_regards' => 'С най-добри пожелания,',

        // Common actions
        'action_view_property' => 'Преглед на имот',
        'action_verify_now' => 'Верифицирайте сега',
        'action_reset_password' => 'Нулиране на парола',

        // Common terms
        'credits' => 'кредити',
        'per_credit' => '/кредит',
        'save_amount' => '(Икономия :amount)',
        'for_credits' => 'за :count кредита',

        // New pending property email
        'new_pending_property_message' => 'Нов имот, създаден от :author ":name", очаква одобрение.',

        // Property approved email
        'property_approved_greeting' => 'Здравейте, :name,',
        'property_approved_message' => 'Вашият имот ":property_name" е одобрен успешно на :site_name.',
        'property_approved_access' => 'Вече можете да достъпите уебсайта и да управлявате вашия имот.',
        'property_approved_view_edit' => 'За да видите или редактирате вашия имот, моля кликнете на тази връзка:',

        // Property rejected email
        'property_rejected_greeting' => 'Здравейте, :name,',
        'property_rejected_message' => 'Вашият имот ":property_name" е отхвърлен на :site_name.',
        'property_rejected_reason' => 'Причината за отхвърлянето е следната:',
        'property_rejected_contact' => 'Ако смятате, че това решение е погрешно, моля свържете се с нашия екип за поддръжка на :email.',
        'property_rejected_view_edit' => 'За да видите или редактирате вашия имот, моля кликнете на тази връзка:',

        // Account registered email
        'account_registered_message' => 'Регистриран е нов акаунт:',

        // Account approved email
        'account_approved_title' => 'Акаунт одобрен',
        'account_approved_greeting' => 'Уважаеми :name,',
        'account_approved_congratulations' => 'Поздравления! Вашият акаунт на :site_name е одобрен.',
        'account_approved_login' => 'Вече можете да влезете и да започнете да използвате нашите услуги. Ако имате въпроси, не се колебайте да се свържете с нашия екип за поддръжка.',
        'account_approved_thanks' => 'Благодарим ви, че се присъединихте към :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Акаунт отхвърлен',
        'account_rejected_greeting' => 'Уважаеми :name,',
        'account_rejected_regret' => 'Съжаляваме да ви уведомим, че вашият акаунт на :site_name е отхвърлен.',
        'account_rejected_reason' => 'Причина за отхвърлянето:',
        'account_rejected_contact' => 'Ако имате въпроси или смятате, че това е грешка, моля свържете се с нашия екип за поддръжка.',
        'account_rejected_thanks' => 'Благодарим ви за разбирането.',

        // Confirm email
        'confirm_email_greeting' => 'Здравейте!',
        'confirm_email_verify' => 'Моля, верифицирайте вашия имейл адрес, за да получите достъп до този уебсайт. Кликнете на бутона по-долу, за да верифицирате вашия имейл.',
        'confirm_email_trouble' => 'Ако имате проблеми с кликването на бутона "Верифицирайте сега", копирайте и поставете URL адреса по-долу във вашия уеб браузър:',

        // Password reminder email
        'password_reminder_greeting' => 'Здравейте!',
        'password_reminder_request' => 'Получавате този имейл, защото получихме заявка за нулиране на паролата за вашия акаунт.',
        'password_reminder_no_action' => 'Ако не сте поискали нулиране на паролата, не е необходимо да предприемате никакви действия.',
        'password_reminder_trouble' => 'Ако имате проблеми с кликването на бутона "Нулиране на парола", копирайте и поставете URL адреса по-долу във вашия уеб браузър:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Здравейте, :name,',
        'payment_receipt_title' => 'Разписка за плащане за вашата покупка:',

        // Payment received email (admin)
        'payment_received_message' => 'Получено плащане от :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name е поискал безплатни кредити.',

        // Notice email (consult form)
        'notice_title' => 'Нова заявка за консултация',
        'notice_message' => 'Има нова консултация от :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Благодарим ви за вашата заявка за консултация!',
        'sender_confirmation_greeting' => 'Уважаеми :name,',
        'sender_confirmation_thank_you' => 'Благодарим ви, че се свързахте с нас! Получихме вашата заявка за консултация и нашият екип ще я прегледа скоро. Един от нашите представители ще се свърже с вас възможно най-скоро.',
        'sender_confirmation_submission_details' => 'Ето подробностите за вашето подаване:',
        'sender_confirmation_additional_info' => 'Ако имате допълнителна информация или въпроси, не се колебайте да отговорите на този имейл.',
        'sender_confirmation_appreciate' => 'Оценяваме вашия интерес и скоро ще се свържем с вас!',
    ],
];
