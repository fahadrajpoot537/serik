<?php

return [
    'name' => 'Hartanah',
    'settings' => 'Settings',
    'login_form' => 'Borang log masuk',
    'register_form' => 'Borang Daftar',
    'forgot_password_form' => 'Lupa borang kata laluan',
    'reset_password_form' => 'Tetapkan semula borang kata laluan',
    'consult_form' => 'Rujuk borang',
    'review_form' => 'Borang semakan',
    'property_translations' => 'Terjemahan harta',
    'project_translations' => 'Terjemahan projek',
    'theme_options' => [
        'slug_name' => 'URL hartanah',
        'slug_description' => 'Sesuaikan slug yang digunakan untuk halaman hartanah. Berhati -hati apabila mengubah suai kerana ia boleh menjejaskan pengalaman SEO dan pengguna. Sekiranya ada masalah, anda boleh menetapkan semula ke lalai dengan menaip nilai lalai atau biarkan kosong.',
        'page_slug_name' => '__Ph0__ halaman slug',
        'page_slug_description' => 'Ia akan kelihatan seperti __ph0__ apabila anda mengakses halaman. Nilai lalai ialah __ph1__.',
        'page_slug_already_exists' => 'Slug halaman __ph0__ sudah digunakan. Sila pilih yang lain.',
        'page_slugs' => [
            'projects_city' => 'Projek oleh bandar',
            'projects_state' => 'Projek oleh Negeri',
            'properties_city' => 'Hartanah oleh City',
            'properties_state' => 'Hartanah oleh Negeri',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Tetapkan semula kata laluan',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nama:',
        'field_email' => 'E -mel:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Subjek:',
        'field_content' => 'Kandungan:',
        'field_address' => 'Alamat:',
        'field_package' => 'Pakej:',
        'field_price' => 'Harga:',
        'field_total' => 'Jumlah:',
        'field_account' => 'Akaun:',

        // Common greetings and closings
        'greeting_admin' => 'Hai admin!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Admin yang dihormati,',
        'closing_regards' => 'Salam,',
        'closing_thank_you' => 'Terima kasih',
        'closing_best_regards' => 'Salam,',

        // Common actions
        'action_view_property' => 'Lihat harta',
        'action_verify_now' => 'Sahkan sekarang',
        'action_reset_password' => 'Tetapkan semula kata laluan',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/kredit',
        'save_amount' => '(Jimat __ph0__)',
        'for_credits' => 'Untuk kredit __ph0__',

        // New pending property email
        'new_pending_property_message' => 'Harta baru yang dibuat oleh __ph0__ "__ph1__" sedang menunggu untuk meluluskan.',

        // Property approved email
        'property_approved_greeting' => 'Hello __ph0__,',
        'property_approved_message' => 'Harta anda "__ph0__" telah berjaya diluluskan pada __ph1__.',
        'property_approved_access' => 'Anda kini boleh mengakses laman web dan menguruskan harta anda.',
        'property_approved_view_edit' => 'Untuk melihat atau mengedit harta anda, sila klik pada pautan ini:',

        // Property rejected email
        'property_rejected_greeting' => 'Hello __ph0__,',
        'property_rejected_message' => 'Harta anda "__ph0__" telah berjaya ditolak pada __ph1__.',
        'property_rejected_reason' => 'Sebab penolakan adalah seperti berikut:',
        'property_rejected_contact' => 'Jika anda percaya keputusan ini dibuat secara tidak sengaja, sila hubungi pasukan sokongan kami di :email.',
        'property_rejected_view_edit' => 'Untuk melihat atau mengedit harta anda, sila klik pada pautan ini:',

        // Account registered email
        'account_registered_message' => 'Akaun baru yang didaftarkan:',

        // Account approved email
        'account_approved_title' => 'Akaun yang diluluskan',
        'account_approved_greeting' => 'Sayang __ph0__,',
        'account_approved_congratulations' => 'Tahniah! Akaun anda di __ph0__ telah diluluskan.',
        'account_approved_login' => 'Anda kini boleh log masuk dan mula menggunakan perkhidmatan kami. Sekiranya anda mempunyai sebarang pertanyaan, jangan ragu untuk menjangkau pasukan sokongan kami.',
        'account_approved_thanks' => 'Terima kasih kerana menyertai __ph0__!',

        // Account rejected email
        'account_rejected_title' => 'Akaun ditolak',
        'account_rejected_greeting' => 'Sayang __ph0__,',
        'account_rejected_regret' => 'Kami menyesal untuk memberitahu anda bahawa akaun anda di :site_name telah ditolak.',
        'account_rejected_reason' => 'Sebab penolakan:',
        'account_rejected_contact' => 'Jika anda mempunyai sebarang pertanyaan atau percaya ini adalah ralat, sila hubungi pasukan sokongan kami.',
        'account_rejected_thanks' => 'Terima kasih atas pemahaman anda.',

        // Confirm email
        'confirm_email_greeting' => 'Helo!',
        'confirm_email_verify' => 'Sila sahkan alamat e -mel anda untuk mengakses laman web ini. Klik pada butang di bawah untuk mengesahkan e -mel anda ..',
        'confirm_email_trouble' => 'Sekiranya anda mengalami masalah mengklik butang "Sahkan sekarang", salin dan tampal URL di bawah ke dalam pelayar web anda:',

        // Password reminder email
        'password_reminder_greeting' => 'Helo!',
        'password_reminder_request' => 'Anda menerima e -mel ini kerana kami menerima permintaan tetapan semula kata laluan untuk akaun anda.',
        'password_reminder_no_action' => 'Jika anda tidak meminta tetapan semula kata laluan, tiada tindakan selanjutnya diperlukan.',
        'password_reminder_trouble' => 'Sekiranya anda mengalami masalah mengklik butang "Reset Kata Laluan", salin dan tampal URL di bawah ke dalam penyemak imbas web anda:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hai __ph0__,',
        'payment_receipt_title' => 'Resit pembayaran untuk pembelian anda:',

        // Payment received email (admin)
        'payment_received_message' => 'Pembayaran diterima dari __ph0__',

        // Free credit claimed email
        'free_credit_claimed_message' => '__Ph0__ telah mendakwa kredit percuma.',

        // Notice email (consult form)
        'notice_title' => 'Permintaan perundingan baru',
        'notice_message' => 'Terdapat perundingan baru dari __ph0__',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Terima kasih atas permintaan anda!',
        'sender_confirmation_greeting' => 'Sayang __ph0__,',
        'sender_confirmation_thank_you' => 'Terima kasih kerana menghubungi kami! Kami telah menerima permintaan konsultasi anda dan pasukan kami akan mengkaji semula tidak lama lagi. Salah satu wakil kami akan kembali kepada anda secepat mungkin.',
        'sender_confirmation_submission_details' => 'Berikut adalah butiran penyerahan anda:',
        'sender_confirmation_additional_info' => 'Sekiranya anda mempunyai maklumat atau soalan tambahan, sila balasan untuk membalas e -mel ini.',
        'sender_confirmation_appreciate' => 'Kami menghargai minat anda dan akan segera menghubungi!',
    ],
];
