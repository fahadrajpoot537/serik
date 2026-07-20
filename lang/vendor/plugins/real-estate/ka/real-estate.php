<?php

return [
    'name' => 'უძრავი ქონება',
    'settings' => 'Settings',
    'login_form' => 'შესვლის ფორმა',
    'register_form' => 'რეგისტრაცია ფორმა',
    'forgot_password_form' => 'დაგავიწყდა პაროლის ფორმა',
    'reset_password_form' => 'პაროლის ფორმის გადატვირთვა',
    'consult_form' => 'კონსულტაციის ფორმა',
    'review_form' => 'განხილვის ფორმა',
    'property_translations' => 'ქონების თარგმანები',
    'project_translations' => 'პროექტის თარგმანები',
    'theme_options' => [
        'slug_name' => 'უძრავი ქონების URL',
        'slug_description' => 'შეაფასეთ უძრავი ქონების გვერდებისათვის გამოყენებული შლაკები. ფრთხილად იყავით, როდესაც ეს შეიძლება გავლენა იქონიოს SEO და მომხმარებლის გამოცდილებაზე. თუ რამე არასწორად მიდის, შეგიძლიათ ნაგულისხმევი გადატვირთვა ნაგულისხმევი მნიშვნელობის აკრეფით ან ცარიელი დატოვოთ.',
        'page_slug_name' => '__Ph0__ გვერდი Slug',
        'page_slug_description' => 'ეს გამოიყურება __ph0__ გვერდზე შესვლისას. ნაგულისხმევი მნიშვნელობა არის __ph1__.',
        'page_slug_already_exists' => '__Ph0__ გვერდის slug უკვე გამოიყენება. გთხოვთ აირჩიოთ სხვა.',
        'page_slugs' => [
            'projects_city' => 'პროექტები ქალაქის მიერ',
            'projects_state' => 'პროექტები სახელმწიფოს მიერ',
            'properties_city' => 'თვისებები ქალაქის მიერ',
            'properties_state' => 'თვისებები სახელმწიფოს მიერ',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'პაროლის გადატვირთვა',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'სახელი:',
        'field_email' => 'ელ.ფოსტა:',
        'field_phone' => 'ტელეფონი:',
        'field_subject' => 'თემა:',
        'field_content' => 'შინაარსი:',
        'field_address' => 'მისამართი:',
        'field_package' => 'პაკეტი:',
        'field_price' => 'ფასი:',
        'field_total' => 'სულ:',
        'field_account' => 'ანგარიში:',

        // Common greetings and closings
        'greeting_admin' => 'გამარჯობა ადმინ!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'ძვირფასო ადმინისტრატორი,',
        'closing_regards' => 'პატივისცემით,',
        'closing_thank_you' => 'მადლობა',
        'closing_best_regards' => 'საუკეთესო სურვილებით,',

        // Common actions
        'action_view_property' => 'ქონების ნახვა',
        'action_verify_now' => 'გადაამოწმეთ ახლავე',
        'action_reset_password' => 'პაროლის გადატვირთვა',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/კრედიტი',
        'save_amount' => '(შეინახეთ __ph0__)',
        'for_credits' => '__ph0__ კრედიტებისთვის',

        // New pending property email
        'new_pending_property_message' => '__P0__ ":name" მიერ შექმნილი ახალი ქონება ელოდება დამტკიცებას.',

        // Property approved email
        'property_approved_greeting' => 'გამარჯობა __ph0__,',
        'property_approved_message' => 'თქვენი ქონება "__ph0__" წარმატებით იქნა დამტკიცებული __ph1__- ზე.',
        'property_approved_access' => 'ახლა შეგიძლიათ ვებგვერდზე შესვლა და თქვენი ქონების მართვა.',
        'property_approved_view_edit' => 'თქვენი ქონების სანახავად ან რედაქტირებისთვის, გთხოვთ, დააჭირეთ ამ ბმულს:',

        // Property rejected email
        'property_rejected_greeting' => 'გამარჯობა __ph0__,',
        'property_rejected_message' => 'თქვენი ქონება "__ph0__" წარმატებით უარი ეთქვა __ph1__- ზე.',
        'property_rejected_reason' => 'უარის თქმის მიზეზი შემდეგია:',
        'property_rejected_contact' => 'თუ თვლით, რომ ეს გადაწყვეტილება შეცდომით იქნა მიღებული, გთხოვთ, დაუკავშირდეთ ჩვენს დამხმარე გუნდს __P0__.',
        'property_rejected_view_edit' => 'თქვენი ქონების სანახავად ან რედაქტირებისთვის, გთხოვთ, დააჭირეთ ამ ბმულს:',

        // Account registered email
        'account_registered_message' => 'რეგისტრირებულია ახალი ანგარიში:',

        // Account approved email
        'account_approved_title' => 'ანგარიში დამტკიცებულია',
        'account_approved_greeting' => 'ძვირფასო __ph0__,',
        'account_approved_congratulations' => 'გილოცავთ! თქვენი ანგარიში __ph0__ დამტკიცებულია.',
        'account_approved_login' => 'ახლა შეგიძლიათ შეხვიდეთ და დაიწყოთ ჩვენი სერვისების გამოყენება. თუ თქვენ გაქვთ რაიმე შეკითხვები, მოგერიდებათ ჩვენს დამხმარე გუნდს.',
        'account_approved_thanks' => 'გმადლობთ, რომ გაწევრიანდეთ __ph0__!',

        // Account rejected email
        'account_rejected_title' => 'ანგარიში უარყოფილი',
        'account_rejected_greeting' => 'ძვირფასო __ph0__,',
        'account_rejected_regret' => 'ჩვენ ვწუხვართ, რომ გაცნობებთ, რომ თქვენი ანგარიში __ph0__– ზე უარი ეთქვა.',
        'account_rejected_reason' => 'უარის თქმის მიზეზი:',
        'account_rejected_contact' => 'თუ თქვენ გაქვთ რაიმე შეკითხვები ან გჯერათ, რომ ეს შეცდომაა, გთხოვთ, დაუკავშირდეთ ჩვენს დამხმარე გუნდს.',
        'account_rejected_thanks' => 'გმადლობთ გაგებისთვის.',

        // Confirm email
        'confirm_email_greeting' => 'გამარჯობა!',
        'confirm_email_verify' => 'გთხოვთ, გადაამოწმოთ თქვენი ელ.ფოსტის მისამართი ამ ვებსაიტზე შესასვლელად. დააჭირეთ ქვემოთ მოცემულ ღილაკს, რომ გადაამოწმოთ თქვენი ელ.ფოსტა ..',
        'confirm_email_trouble' => 'თუ თქვენ გაქვთ პრობლემები დააჭირეთ ღილაკს "გადამოწმება ახლა", დააკოპირეთ და ჩასვით URL ქვემოთ თქვენს ბრაუზერში:',

        // Password reminder email
        'password_reminder_greeting' => 'გამარჯობა!',
        'password_reminder_request' => 'თქვენ იღებთ ამ ელ.წერილს, რადგან მივიღეთ პაროლის გადატვირთვის მოთხოვნა თქვენი ანგარიშისთვის.',
        'password_reminder_no_action' => 'თუ თქვენ არ მოითხოვეთ პაროლის გადატვირთვა, აღარ არის საჭირო დამატებითი მოქმედება.',
        'password_reminder_trouble' => 'თუ თქვენ გაქვთ პრობლემები დააჭირეთ ღილაკს "გადატვირთვის პაროლი", დააკოპირეთ და ჩასვით ქვემოთ URL თქვენს ვებ - ბრაუზერში:',

        // Payment receipt email
        'payment_receipt_greeting' => 'გამარჯობა __ph0__,',
        'payment_receipt_title' => 'გადახდის ქვითარი თქვენი შეძენისთვის:',

        // Payment received email (admin)
        'payment_received_message' => 'გადახდა მიღებული __P0__– დან',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Ph0__ მოითხოვა უფასო კრედიტები.',

        // Notice email (consult form)
        'notice_title' => 'ახალი კონსულტაციის მოთხოვნა',
        'notice_message' => 'არსებობს ახალი კონსულტაცია __P0__– დან',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'გმადლობთ კონსულტაციის თხოვნისთვის!',
        'sender_confirmation_greeting' => 'ძვირფასო __ph0__,',
        'sender_confirmation_thank_you' => 'გმადლობთ, რომ მოგვმართეთ! ჩვენ მივიღეთ თქვენი კონსულტაციის მოთხოვნა და ჩვენი გუნდი ამას მალე გადახედავს. ჩვენი ერთ -ერთი წარმომადგენელი რაც შეიძლება მალე დაგიბრუნდებათ.',
        'sender_confirmation_submission_details' => 'აქ მოცემულია თქვენი წარდგენის დეტალები:',
        'sender_confirmation_additional_info' => 'თუ თქვენ გაქვთ დამატებითი ინფორმაცია ან შეკითხვები, მოგერიდებათ უპასუხეთ ამ ელ.ფოსტაზე.',
        'sender_confirmation_appreciate' => 'ჩვენ ვაფასებთ თქვენს ინტერესს და მალე დაუკავშირდებით!',
    ],
];
