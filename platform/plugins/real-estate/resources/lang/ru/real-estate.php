<?php

return [
    'name' => 'Недвижимость',
    'settings' => 'Settings',
    'login_form' => 'Форма входа',
    'register_form' => 'Регистрационная форма',
    'forgot_password_form' => 'Форма забытого пароля',
    'reset_password_form' => 'Форма сброса пароля',
    'consult_form' => 'Форма консультации',
    'review_form' => 'Форма обзора',
    'property_translations' => 'Переводы недвижимости',
    'project_translations' => 'Переводы проектов',
    'theme_options' => [
        'slug_name' => 'URL-адреса недвижимости',
        'slug_description' => 'Настройте ярлыки, используемые для страниц недвижимости. Будьте осторожны при внесении изменений, поскольку это может повлиять на SEO и пользовательский опыт. Если что-то пойдет не так, вы можете сбросить настройки по умолчанию, введя значение по умолчанию или оставив это поле пустым.',
        'page_slug_name' => ':page фрагмент страницы',
        'page_slug_description' => 'Когда вы зайдете на страницу, он будет выглядеть как :slug. Значение по умолчанию — :default.',
        'page_slug_already_exists' => 'Слаг страницы :slug уже используется. Пожалуйста, выберите другой.',
        'page_slugs' => [
            'projects_city' => 'Проекты по городам',
            'projects_state' => 'Проекты по штатам',
            'properties_city' => 'Недвижимость по городам',
            'properties_state' => 'Недвижимость по штатам',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Сбросить пароль',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Имя:',
        'field_email' => 'Электронная почта:',
        'field_phone' => 'Телефон:',
        'field_subject' => 'Предмет:',
        'field_content' => 'Содержание:',
        'field_address' => 'Адрес:',
        'field_package' => 'Упаковка:',
        'field_price' => 'Цена:',
        'field_total' => 'Общий:',
        'field_account' => 'Счет:',

        // Common greetings and closings
        'greeting_admin' => 'Привет Админ!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Уважаемый Админ,',
        'closing_regards' => 'С уважением,',
        'closing_thank_you' => 'Спасибо',
        'closing_best_regards' => 'С наилучшими пожеланиями,',

        // Common actions
        'action_view_property' => 'Посмотреть недвижимость',
        'action_verify_now' => 'Подтвердить сейчас',
        'action_reset_password' => 'Сбросить пароль',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/кредит',
        'save_amount' => '(Сохранить :amount)',
        'for_credits' => 'за :count кредитов',

        // New pending property email
        'new_pending_property_message' => 'Новое свойство, созданное :author ":name", ожидает одобрения.',

        // Property approved email
        'property_approved_greeting' => 'Привет :name!',
        'property_approved_message' => 'Ваш объект «:property_name» был успешно одобрен :site_name.',
        'property_approved_access' => 'Теперь вы можете получить доступ к веб-сайту и управлять своей собственностью.',
        'property_approved_view_edit' => 'Чтобы просмотреть или отредактировать свой объект, нажмите на эту ссылку:',

        // Property rejected email
        'property_rejected_greeting' => 'Привет :name!',
        'property_rejected_message' => 'Ваш ресурс «:property_name» был успешно отклонен :site_name.',
        'property_rejected_reason' => 'Причина отказа следующая:',
        'property_rejected_contact' => 'Если вы считаете, что это решение было принято по ошибке, обратитесь в нашу службу поддержки по телефону :email.',
        'property_rejected_view_edit' => 'Чтобы просмотреть или отредактировать свой объект, нажмите на эту ссылку:',

        // Account registered email
        'account_registered_message' => 'Зарегистрирован новый аккаунт:',

        // Account approved email
        'account_approved_title' => 'Аккаунт одобрен',
        'account_approved_greeting' => 'Дорогой :name,',
        'account_approved_congratulations' => 'Поздравляем! Ваш аккаунт на :site_name одобрен.',
        'account_approved_login' => 'Теперь вы можете войти в систему и начать пользоваться нашими услугами. Если у вас есть какие-либо вопросы, не стесняйтесь обращаться в нашу службу поддержки.',
        'account_approved_thanks' => 'Спасибо, что присоединились к :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Аккаунт отклонен',
        'account_rejected_greeting' => 'Дорогой :name,',
        'account_rejected_regret' => 'С сожалением сообщаем вам, что ваша учетная запись на :site_name была отклонена.',
        'account_rejected_reason' => 'Причина отказа:',
        'account_rejected_contact' => 'Если у вас есть какие-либо вопросы или вы считаете, что это ошибка, свяжитесь с нашей службой поддержки.',
        'account_rejected_thanks' => 'Благодарим вас за понимание.',

        // Confirm email
        'confirm_email_greeting' => 'Привет!',
        'confirm_email_verify' => 'Пожалуйста, подтвердите свой адрес электронной почты, чтобы получить доступ к этому сайту. Нажмите на кнопку ниже, чтобы подтвердить свой адрес электронной почты.',
        'confirm_email_trouble' => 'Если у вас возникли проблемы с нажатием кнопки «Подтвердить сейчас», скопируйте и вставьте приведенный ниже URL-адрес в свой веб-браузер:',

        // Password reminder email
        'password_reminder_greeting' => 'Привет!',
        'password_reminder_request' => 'Вы получили это письмо, поскольку мы получили запрос на сброс пароля для вашей учетной записи.',
        'password_reminder_no_action' => 'Если вы не запрашивали сброс пароля, никаких дальнейших действий не требуется.',
        'password_reminder_trouble' => 'Если у вас возникли проблемы с нажатием кнопки «Сбросить пароль», скопируйте и вставьте приведенный ниже URL-адрес в свой веб-браузер:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Привет :name!',
        'payment_receipt_title' => 'Квитанция об оплате покупки:',

        // Payment received email (admin)
        'payment_received_message' => 'Платеж получен от :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name запросил бесплатные кредиты.',

        // Notice email (consult form)
        'notice_title' => 'Новый запрос на консультацию',
        'notice_message' => 'Появилась новая консультация от :property_name.',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Благодарим Вас за запрос на консультацию!',
        'sender_confirmation_greeting' => 'Дорогой :name,',
        'sender_confirmation_thank_you' => 'Спасибо, что обратились к нам! Мы получили ваш запрос на консультацию, и наша команда рассмотрит его в ближайшее время. Один из наших представителей свяжется с вами как можно скорее.',
        'sender_confirmation_submission_details' => 'Вот подробности вашего обращения:',
        'sender_confirmation_additional_info' => 'Если у вас есть дополнительная информация или вопросы, ответьте на это письмо.',
        'sender_confirmation_appreciate' => 'Мы ценим ваш интерес и скоро свяжемся с вами!',
    ],
];
