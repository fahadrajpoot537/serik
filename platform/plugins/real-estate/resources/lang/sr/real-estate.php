<?php

return [
    'name' => 'Некретнина',
    'settings' => 'Settings',
    'login_form' => 'Пријављивање образац',
    'register_form' => 'Региструјте се',
    'forgot_password_form' => 'Заборавили сте лозинку Образац',
    'reset_password_form' => 'Ресетујте образац за лозинку',
    'consult_form' => 'Консултовати се',
    'review_form' => 'Образац за преглед',
    'property_translations' => 'Преводи имовине',
    'project_translations' => 'Пројектни преводи',
    'theme_options' => [
        'slug_name' => 'УРЛ-ови некретнина',
        'slug_description' => 'Прилагодите Слугове који се користе за странице некретнина. Будите опрезни када модификују како може утицати на СЕО и корисничко искуство. Ако нешто пође по злу, можете да се ресетујете на задано уносом задане вредности или га оставите празно.',
        'page_slug_name' => '__Пх0__ Страница',
        'page_slug_description' => 'Изгледаће као __пх0__ када приступите страници. Подразумевана вредност је __Х1__.',
        'page_slug_already_exists' => '__Пх0__ Страница је већ у употреби. Молимо одаберите другу.',
        'page_slugs' => [
            'projects_city' => 'Пројекти по граду',
            'projects_state' => 'Пројекти од стране државе',
            'properties_city' => 'Некретнине по граду',
            'properties_state' => 'Некретнине од стране државе',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Ресетујте лозинку',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Име:',
        'field_email' => 'Е-пошта:',
        'field_phone' => 'Телефон:',
        'field_subject' => 'Предмет:',
        'field_content' => 'Садржај:',
        'field_address' => 'Адреса:',
        'field_package' => 'Паковање:',
        'field_price' => 'Цена:',
        'field_total' => 'Укупно:',
        'field_account' => 'Рачун:',

        // Common greetings and closings
        'greeting_admin' => 'Поздрав Админ!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Драги администратор,',
        'closing_regards' => 'Поздрави,',
        'closing_thank_you' => 'Хвала',
        'closing_best_regards' => 'Срдачан поздрав,',

        // Common actions
        'action_view_property' => 'Погледајте имовину',
        'action_verify_now' => 'Верификовати сада',
        'action_reset_password' => 'Ресетујте лозинку',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/ кредит',
        'save_amount' => '(Уштедите __пх0__)',
        'for_credits' => 'за __пх0__ кредита',

        // New pending property email
        'new_pending_property_message' => 'Нова некретнина створена од __пх0__ "__пх1__" чека одобрење.',

        // Property approved email
        'property_approved_greeting' => 'Поздрав __пх0__,',
        'property_approved_message' => 'Ваша имовина "__Х30__" је успешно одобрена на __Х17__.',
        'property_approved_access' => 'Сада можете да приступите веб локацији и управљате својом имовином.',
        'property_approved_view_edit' => 'Да бисте видели или уредили своју имовину, кликните на ову везу:',

        // Property rejected email
        'property_rejected_greeting' => 'Поздрав __пх0__,',
        'property_rejected_message' => 'Ваша имовина "__ХП0__" је успешно одбијена на __Х52.',
        'property_rejected_reason' => 'Разлог одбацивања је следећи:',
        'property_rejected_contact' => 'Ако верујете да је ова одлука извршена грешком, обратите се нашем тиму за подршку на __Х0__.',
        'property_rejected_view_edit' => 'Да бисте видели или уредили своју имовину, кликните на ову везу:',

        // Account registered email
        'account_registered_message' => 'Нови налог регистровани:',

        // Account approved email
        'account_approved_title' => 'Одобрени рачун',
        'account_approved_greeting' => 'Драги __Х0__,',
        'account_approved_congratulations' => 'Честитам! Ваш налог на __Х0__ је одобрен.',
        'account_approved_login' => 'Сада се можете пријавити и почети да користите наше услуге. Ако имате било каквих питања, слободно се посегнете са нашем тимом за подршку.',
        'account_approved_thanks' => 'Хвала вам што сте се придружили __Х0__!',

        // Account rejected email
        'account_rejected_title' => 'Рачун је одбачен',
        'account_rejected_greeting' => 'Драги __Х0__,',
        'account_rejected_regret' => 'Са жаљењем вас обавештавамо да је ваш рачун на __пх0__ одбијено.',
        'account_rejected_reason' => 'Разлог одбацивања:',
        'account_rejected_contact' => 'Ако имате било каквих питања или да верујете да је ово грешка, обратите се нашем тиму за подршку.',
        'account_rejected_thanks' => 'Хвала на разумевању.',

        // Confirm email
        'confirm_email_greeting' => 'Здраво!',
        'confirm_email_verify' => 'Проверите своју адресу е-поште да бисте приступили овој веб страници. Кликните на дугме испод да бисте проверили своју е-пошту ..',
        'confirm_email_trouble' => 'Ако имате проблема са кликом на дугме "Верифи Нов", копирајте и залепите УРЛ адресу у свој веб претраживач:',

        // Password reminder email
        'password_reminder_greeting' => 'Здраво!',
        'password_reminder_request' => 'Овај емаил добијате јер смо добили захтев за ресетовање лозинке за ваш рачун.',
        'password_reminder_no_action' => 'Ако нисте затражили ресетовање лозинке, није потребна даљња радња.',
        'password_reminder_trouble' => 'Ако имате проблема са кликом на дугме "Ресетовање лозинке", копирајте и залепите УРЛ адресу испод у вашем веб претраживачу:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Здраво __пх0__,',
        'payment_receipt_title' => 'Пријем плаћања за куповину:',

        // Payment received email (admin)
        'payment_received_message' => 'Плаћање примљено од __пх0__',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Х0__ је затражио бесплатне кредите.',

        // Notice email (consult form)
        'notice_title' => 'Нови захтев за консултацију',
        'notice_message' => 'Постоји ново саветовање са __пх0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Хвала вам на консултантском захтеву!',
        'sender_confirmation_greeting' => 'Драги __Х0__,',
        'sender_confirmation_thank_you' => 'Хвала вам што сте му посегнули! Примили смо ваш консултни захтев и наш тим ће га ускоро прегледати. Један од наших представника ће вам се вратити што је пре могуће.',
        'sender_confirmation_submission_details' => 'Ево детаља вашег подношења:',
        'sender_confirmation_additional_info' => 'Ако имате било каквих додатних информација или питања, слободно одговорите на овај мејл.',
        'sender_confirmation_appreciate' => 'Цијенимо ваше интересовање и ускоро ћемо бити у контакту!',
    ],
];
