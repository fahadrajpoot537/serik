<?php

return [
    'name' => 'Nekilnojamasis turtas',
    'settings' => 'Settings',
    'login_form' => 'Prisijungimo forma',
    'register_form' => 'Registracijos forma',
    'forgot_password_form' => 'Pamiršote slaptažodžio formą',
    'reset_password_form' => 'Iš naujo nustatykite slaptažodžio formą',
    'consult_form' => 'Pasitarkite su forma',
    'review_form' => 'Peržiūros forma',
    'property_translations' => 'Turto vertimai',
    'project_translations' => 'Projekto vertimai',
    'theme_options' => [
        'slug_name' => 'Nekilnojamojo turto URL',
        'slug_description' => 'Tinkinkite šliužus, naudojamus nekilnojamojo turto puslapiams. Būkite atsargūs modifikuodami, nes tai gali paveikti SEO ir vartotojo patirtį. Jei kažkas nutinka ne taip, galite iš naujo nustatyti pagal numatytąjį, įvesdami numatytąją vertę arba palikdami ją tuščią.',
        'page_slug_name' => '__Ph0__ puslapio šliužas',
        'page_slug_description' => 'Kai pasieksite puslapį, tai atrodys kaip __Ph0__. Numatytoji vertė yra __Ph1__.',
        'page_slug_already_exists' => '__Ph0__ puslapio šliužas jau naudojamas. Pasirinkite kitą.',
        'page_slugs' => [
            'projects_city' => 'Projektai pagal miestą',
            'projects_state' => 'Projektai pagal valstybę',
            'properties_city' => 'Nuosavybės pagal miestą',
            'properties_state' => 'Savybės pagal būseną',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Iš naujo nustatykite slaptažodį',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Vardas:',
        'field_email' => 'El. Paštas:',
        'field_phone' => 'Telefonas:',
        'field_subject' => 'Tema:',
        'field_content' => 'Turinys:',
        'field_address' => 'Adresas:',
        'field_package' => 'Paketas:',
        'field_price' => 'Kaina:',
        'field_total' => 'Iš viso:',
        'field_account' => 'Sąskaita:',

        // Common greetings and closings
        'greeting_admin' => 'Sveiki, administratorius!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Mielas administratorius,',
        'closing_regards' => 'Pagarbiai,',
        'closing_thank_you' => 'Ačiū',
        'closing_best_regards' => 'Pagarbiai,',

        // Common actions
        'action_view_property' => 'Peržiūrėti nuosavybę',
        'action_verify_now' => 'Patikrinkite dabar',
        'action_reset_password' => 'Iš naujo nustatykite slaptažodį',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kreditas',
        'save_amount' => '(Išsaugoti __Ph0__)',
        'for_credits' => 'už __Ph0__ kreditus',

        // New pending property email
        'new_pending_property_message' => 'Laukia naujos __Ph0__ „__Ph1__“ sukurtos nuosavybės.',

        // Property approved email
        'property_approved_greeting' => 'Sveiki __Ph0__,',
        'property_approved_message' => 'Jūsų nuosavybė „__Ph0__“ buvo sėkmingai patvirtinta __Ph1__.',
        'property_approved_access' => 'Dabar galite pasiekti svetainę ir valdyti savo turtą.',
        'property_approved_view_edit' => 'Norėdami peržiūrėti ar redaguoti savo nuosavybę, spustelėkite šią nuorodą:',

        // Property rejected email
        'property_rejected_greeting' => 'Sveiki __Ph0__,',
        'property_rejected_message' => 'Jūsų nuosavybė „__Ph0__“ buvo sėkmingai atmesta __Ph1__.',
        'property_rejected_reason' => 'Atmetimo priežastis yra tokia:',
        'property_rejected_contact' => 'Jei manote, kad šis sprendimas buvo priimtas per klaidą, susisiekite su mūsų palaikymo komanda __Ph0__.',
        'property_rejected_view_edit' => 'Norėdami peržiūrėti ar redaguoti savo nuosavybę, spustelėkite šią nuorodą:',

        // Account registered email
        'account_registered_message' => 'Registruota nauja sąskaita:',

        // Account approved email
        'account_approved_title' => 'Patvirtinta sąskaita',
        'account_approved_greeting' => 'Mielas __Ph0__,',
        'account_approved_congratulations' => 'Sveikiname! Jūsų sąskaita __Ph0__ buvo patvirtinta.',
        'account_approved_login' => 'Dabar galite prisijungti ir pradėti naudoti mūsų paslaugas. Jei turite klausimų, nedvejodami susisiekite su mūsų palaikymo komanda.',
        'account_approved_thanks' => 'Dėkojame, kad prisijungėte prie __Ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Sąskaita atmesta',
        'account_rejected_greeting' => 'Mielas __Ph0__,',
        'account_rejected_regret' => 'Apgailestaujame, kad pranešėme, kad jūsų sąskaita __Ph0__ buvo atmesta.',
        'account_rejected_reason' => 'Atmetimo priežastis:',
        'account_rejected_contact' => 'Jei turite klausimų ar manote, kad tai yra klaida, susisiekite su mūsų palaikymo komanda.',
        'account_rejected_thanks' => 'Ačiū už supratimą.',

        // Confirm email
        'confirm_email_greeting' => 'Sveiki!',
        'confirm_email_verify' => 'Norėdami pasiekti šią svetainę, patikrinkite savo el. Pašto adresą. Spustelėkite žemiau esantį mygtuką, kad patikrintumėte savo el. Paštą.',
        'confirm_email_trouble' => 'Jei turite problemų spustelėdami mygtuką „Patikrinkite dabar“, nukopijuokite ir įklijuokite žemiau esantį URL į savo interneto naršyklę:',

        // Password reminder email
        'password_reminder_greeting' => 'Sveiki!',
        'password_reminder_request' => 'Jūs gaunate šį el. Laišką, nes gavome jūsų sąskaitos slaptažodžio nustatymo užklausą.',
        'password_reminder_no_action' => 'Jei neprašėte iš naujo nustatyti slaptažodį, papildomų veiksmų nereikia.',
        'password_reminder_trouble' => 'Jei jums kyla problemų spustelėdami mygtuką „RESET SWERD“, nukopijuokite ir įklijuokite žemiau esantį URL į savo žiniatinklio naršyklę:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Labas __Ph0__,',
        'payment_receipt_title' => 'Mokėjimo kvitas už pirkinį:',

        // Payment received email (admin)
        'payment_received_message' => 'Iš __Ph0__ gautas mokėjimas',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Ph0__ reikalavo nemokamų kreditų.',

        // Notice email (consult form)
        'notice_title' => 'Nauja konsultavimo užklausa',
        'notice_message' => 'Yra nauja konsultacija iš __Ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Dėkojame už jūsų konsultavimo prašymą!',
        'sender_confirmation_greeting' => 'Mielas __Ph0__,',
        'sender_confirmation_thank_you' => 'Ačiū, kad susisiekėte su mumis! Gavome jūsų konsultavimo prašymą ir mūsų komanda netrukus ją peržiūrės. Vienas iš mūsų atstovų susisieks su jumis kuo greičiau.',
        'sender_confirmation_submission_details' => 'Čia yra jūsų pateikimo informacija:',
        'sender_confirmation_additional_info' => 'Jei turite papildomos informacijos ar klausimų, atsakykite į šį el. Laišką.',
        'sender_confirmation_appreciate' => 'Mes vertiname jūsų susidomėjimą ir netrukus susisieksime!',
    ],
];
