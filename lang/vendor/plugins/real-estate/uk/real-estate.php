<?php

return [
    'name' => 'Нерухомість',
    'settings' => 'Налаштування',
    'login_form' => 'Форма входу',
    'register_form' => 'Форма реєстрації',
    'forgot_password_form' => 'Форма відновлення пароля',
    'reset_password_form' => 'Форма скидання пароля',
    'consult_form' => 'Форма консультації',
    'review_form' => 'Форма відгуку',
    'property_translations' => 'Переклади об\'єктів',
    'project_translations' => 'Переклади проєктів',
    'theme_options' => [
        'slug_name' => 'URL-адреси нерухомості',
        'slug_description' => 'Налаштуйте слаги, що використовуються для сторінок нерухомості. Будьте обережні при зміні, оскільки це може вплинути на SEO та користувацький досвід. Якщо щось піде не так, ви можете скинути до значення за замовчуванням, ввівши значення за замовчуванням або залишивши порожнім.',
        'page_slug_name' => 'Слаг сторінки :page',
        'page_slug_description' => 'Це виглядатиме як :slug при доступі до сторінки. Значення за замовчуванням :default.',
        'page_slug_already_exists' => 'Слаг сторінки :slug вже використовується. Будь ласка, виберіть інший.',
        'page_slugs' => [
            'projects_city' => 'Проєкти за містом',
            'projects_state' => 'Проєкти за областю',
            'properties_city' => 'Об\'єкти за містом',
            'properties_state' => 'Об\'єкти за областю',
            'login' => 'Вхід',
            'register' => 'Реєстрація',
            'reset_password' => 'Скидання пароля',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Ім\'я:',
        'field_email' => 'Email:',
        'field_phone' => 'Телефон:',
        'field_subject' => 'Тема:',
        'field_content' => 'Вміст:',
        'field_address' => 'Адреса:',
        'field_package' => 'Пакет:',
        'field_price' => 'Ціна:',
        'field_total' => 'Всього:',
        'field_account' => 'Обліковий запис:',

        // Common greetings and closings
        'greeting_admin' => 'Вітаємо, Адміністратор!',
        'greeting_hello' => 'Вітаємо',
        'greeting_dear' => 'Шановний',
        'greeting_dear_admin' => 'Шановний Адміністратор,',
        'closing_regards' => 'З повагою,',
        'closing_thank_you' => 'Дякуємо',
        'closing_best_regards' => 'З найкращими побажаннями,',

        // Common actions
        'action_view_property' => 'Переглянути об\'єкт',
        'action_verify_now' => 'Підтвердити зараз',
        'action_reset_password' => 'Скинути пароль',

        // Common terms
        'credits' => 'кредити',
        'per_credit' => '/кредит',
        'save_amount' => '(Економія :amount)',
        'for_credits' => 'за :count кредитів',

        // New pending property email
        'new_pending_property_message' => 'Новий об\'єкт, створений :author ":name", очікує схвалення.',

        // Property approved email
        'property_approved_greeting' => 'Вітаємо :name,',
        'property_approved_message' => 'Ваш об\'єкт ":property_name" успішно схвалено на :site_name.',
        'property_approved_access' => 'Тепер ви можете отримати доступ до веб-сайту та керувати своїм об\'єктом.',
        'property_approved_view_edit' => 'Щоб переглянути або відредагувати свій об\'єкт, будь ласка, натисніть на це посилання:',

        // Property rejected email
        'property_rejected_greeting' => 'Вітаємо :name,',
        'property_rejected_message' => 'Ваш об\'єкт ":property_name" було відхилено на :site_name.',
        'property_rejected_reason' => 'Причина відхилення наступна:',
        'property_rejected_contact' => 'Якщо ви вважаєте, що це рішення було прийнято помилково, будь ласка, зв\'яжіться з нашою службою підтримки за адресою :email.',
        'property_rejected_view_edit' => 'Щоб переглянути або відредагувати свій об\'єкт, будь ласка, натисніть на це посилання:',

        // Account registered email
        'account_registered_message' => 'Зареєстровано новий обліковий запис:',

        // Account approved email
        'account_approved_title' => 'Обліковий запис схвалено',
        'account_approved_greeting' => 'Шановний :name,',
        'account_approved_congratulations' => 'Вітаємо! Ваш обліковий запис на :site_name схвалено.',
        'account_approved_login' => 'Тепер ви можете увійти та почати користуватися нашими послугами. Якщо у вас є запитання, зв\'яжіться з нашою службою підтримки.',
        'account_approved_thanks' => 'Дякуємо, що приєдналися до :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Обліковий запис відхилено',
        'account_rejected_greeting' => 'Шановний :name,',
        'account_rejected_regret' => 'На жаль, повідомляємо вам, що ваш обліковий запис на :site_name було відхилено.',
        'account_rejected_reason' => 'Причина відхилення:',
        'account_rejected_contact' => 'Якщо у вас є запитання або ви вважаєте, що це помилка, будь ласка, зв\'яжіться з нашою службою підтримки.',
        'account_rejected_thanks' => 'Дякуємо за розуміння.',

        // Confirm email
        'confirm_email_greeting' => 'Вітаємо!',
        'confirm_email_verify' => 'Будь ласка, підтвердіть свою електронну адресу, щоб отримати доступ до цього веб-сайту. Натисніть на кнопку нижче, щоб підтвердити свій email.',
        'confirm_email_trouble' => 'Якщо у вас виникли проблеми з натисканням кнопки "Підтвердити зараз", скопіюйте та вставте URL-адресу нижче у свій веб-браузер:',

        // Password reminder email
        'password_reminder_greeting' => 'Вітаємо!',
        'password_reminder_request' => 'Ви отримали цей email, оскільки ми отримали запит на скидання пароля для вашого облікового запису.',
        'password_reminder_no_action' => 'Якщо ви не запитували скидання пароля, жодних подальших дій не потрібно.',
        'password_reminder_trouble' => 'Якщо у вас виникли проблеми з натисканням кнопки "Скинути пароль", скопіюйте та вставте URL-адресу нижче у свій веб-браузер:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Вітаємо :name,',
        'payment_receipt_title' => 'Квитанція про оплату вашої покупки:',

        // Payment received email (admin)
        'payment_received_message' => 'Отримано платіж від :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name отримав безкоштовні кредити.',

        // Notice email (consult form)
        'notice_title' => 'Новий запит на консультацію',
        'notice_message' => 'Надійшла нова консультація від :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Дякуємо за ваш запит на консультацію!',
        'sender_confirmation_greeting' => 'Шановний :name,',
        'sender_confirmation_thank_you' => 'Дякуємо, що звернулися до нас! Ми отримали ваш запит на консультацію, і наша команда незабаром його розгляне. Один з наших представників зв\'яжеться з вами якомога швидше.',
        'sender_confirmation_submission_details' => 'Ось деталі вашого запиту:',
        'sender_confirmation_additional_info' => 'Якщо у вас є додаткова інформація або запитання, не соромтеся відповісти на цей email.',
        'sender_confirmation_appreciate' => 'Ми цінуємо ваш інтерес і скоро зв\'яжемося з вами!',
    ],
];
