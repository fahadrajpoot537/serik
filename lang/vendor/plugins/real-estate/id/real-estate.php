<?php

return [
    'name' => 'Real Estat',
    'settings' => 'Pengaturan',
    'login_form' => 'Form Login',
    'register_form' => 'Form Registrasi',
    'forgot_password_form' => 'Form Lupa Password',
    'reset_password_form' => 'Form Reset Password',
    'consult_form' => 'Form Konsultasi',
    'review_form' => 'Form Ulasan',
    'property_translations' => 'Terjemahan Properti',
    'project_translations' => 'Terjemahan Proyek',
    'theme_options' => [
        'slug_name' => 'URL Real Estat',
        'slug_description' => 'Kustomisasi slug yang digunakan untuk halaman real estat. Berhati-hatilah saat memodifikasi karena dapat mempengaruhi SEO dan pengalaman pengguna. Jika terjadi kesalahan, Anda dapat mereset ke default dengan mengetik nilai default atau membiarkannya kosong.',
        'page_slug_name' => 'Slug halaman :page',
        'page_slug_description' => 'Akan terlihat seperti :slug saat Anda mengakses halaman. Nilai default adalah :default.',
        'page_slug_already_exists' => 'Slug halaman :slug sudah digunakan. Silakan pilih yang lain.',
        'page_slugs' => [
            'projects_city' => 'Proyek berdasarkan Kota',
            'projects_state' => 'Proyek berdasarkan Provinsi',
            'properties_city' => 'Properti berdasarkan Kota',
            'properties_state' => 'Properti berdasarkan Provinsi',
            'login' => 'Login',
            'register' => 'Registrasi',
            'reset_password' => 'Reset Password',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Nama:',
        'field_email' => 'Email:',
        'field_phone' => 'Telepon:',
        'field_subject' => 'Subjek:',
        'field_content' => 'Konten:',
        'field_address' => 'Alamat:',
        'field_package' => 'Paket:',
        'field_price' => 'Harga:',
        'field_total' => 'Total:',
        'field_account' => 'Akun:',

        // Common greetings and closings
        'greeting_admin' => 'Halo Admin!',
        'greeting_hello' => 'Halo',
        'greeting_dear' => 'Yang Terhormat',
        'greeting_dear_admin' => 'Yang Terhormat Admin,',
        'closing_regards' => 'Hormat kami,',
        'closing_thank_you' => 'Terima kasih',
        'closing_best_regards' => 'Salam hormat,',

        // Common actions
        'action_view_property' => 'Lihat Properti',
        'action_verify_now' => 'Verifikasi sekarang',
        'action_reset_password' => 'Reset password',

        // Common terms
        'credits' => 'kredit',
        'per_credit' => '/kredit',
        'save_amount' => '(Hemat :amount)',
        'for_credits' => 'untuk :count kredit',

        // New pending property email
        'new_pending_property_message' => 'Properti baru yang dibuat oleh :author ":name" menunggu persetujuan.',

        // Property approved email
        'property_approved_greeting' => 'Halo :name,',
        'property_approved_message' => 'Properti Anda ":property_name" telah berhasil disetujui di :site_name.',
        'property_approved_access' => 'Sekarang Anda dapat mengakses website dan mengelola properti Anda.',
        'property_approved_view_edit' => 'Untuk melihat atau mengedit properti Anda, silakan klik tautan ini:',

        // Property rejected email
        'property_rejected_greeting' => 'Halo :name,',
        'property_rejected_message' => 'Properti Anda ":property_name" telah ditolak di :site_name.',
        'property_rejected_reason' => 'Alasan penolakan adalah sebagai berikut:',
        'property_rejected_contact' => 'Jika Anda yakin keputusan ini salah, silakan hubungi tim dukungan kami di :email.',
        'property_rejected_view_edit' => 'Untuk melihat atau mengedit properti Anda, silakan klik tautan ini:',

        // Account registered email
        'account_registered_message' => 'Akun baru telah terdaftar:',

        // Account approved email
        'account_approved_title' => 'Akun Disetujui',
        'account_approved_greeting' => 'Yang Terhormat :name,',
        'account_approved_congratulations' => 'Selamat! Akun Anda di :site_name telah disetujui.',
        'account_approved_login' => 'Sekarang Anda dapat login dan mulai menggunakan layanan kami. Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi tim dukungan kami.',
        'account_approved_thanks' => 'Terima kasih telah bergabung dengan :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Akun Ditolak',
        'account_rejected_greeting' => 'Yang Terhormat :name,',
        'account_rejected_regret' => 'Kami menyesal menginformasikan bahwa akun Anda di :site_name telah ditolak.',
        'account_rejected_reason' => 'Alasan penolakan:',
        'account_rejected_contact' => 'Jika Anda memiliki pertanyaan atau yakin ini adalah kesalahan, silakan hubungi tim dukungan kami.',
        'account_rejected_thanks' => 'Terima kasih atas pengertian Anda.',

        // Confirm email
        'confirm_email_greeting' => 'Halo!',
        'confirm_email_verify' => 'Silakan verifikasi alamat email Anda untuk mengakses website ini. Klik tombol di bawah untuk memverifikasi email Anda.',
        'confirm_email_trouble' => 'Jika Anda mengalami masalah mengklik tombol "Verifikasi sekarang", salin dan tempel URL di bawah ini ke browser web Anda:',

        // Password reminder email
        'password_reminder_greeting' => 'Halo!',
        'password_reminder_request' => 'Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.',
        'password_reminder_no_action' => 'Jika Anda tidak meminta reset password, tidak diperlukan tindakan lebih lanjut.',
        'password_reminder_trouble' => 'Jika Anda mengalami masalah mengklik tombol "Reset Password", salin dan tempel URL di bawah ini ke browser web Anda:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Halo :name,',
        'payment_receipt_title' => 'Kwitansi pembayaran untuk pembelian Anda:',

        // Payment received email (admin)
        'payment_received_message' => 'Pembayaran diterima dari :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name telah mengklaim kredit gratis.',

        // Notice email (consult form)
        'notice_title' => 'Permintaan Konsultasi Baru',
        'notice_message' => 'Ada konsultasi baru dari :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Terima Kasih atas Permintaan Konsultasi Anda!',
        'sender_confirmation_greeting' => 'Yang Terhormat :name,',
        'sender_confirmation_thank_you' => 'Terima kasih telah menghubungi kami! Kami telah menerima permintaan konsultasi Anda dan tim kami akan segera meninjaunya. Salah satu perwakilan kami akan menghubungi Anda secepatnya.',
        'sender_confirmation_submission_details' => 'Berikut adalah detail pengajuan Anda:',
        'sender_confirmation_additional_info' => 'Jika Anda memiliki informasi tambahan atau pertanyaan, jangan ragu untuk membalas email ini.',
        'sender_confirmation_appreciate' => 'Kami menghargai minat Anda dan akan segera menghubungi Anda!',
    ],
    'contact_for_price' => 'Kontak',
    'contact_for_price' => 'Kontak',
    'yes' => 'Ya',
    'no' => 'Tidak',
    'projects' => 'Proyek',
    'properties' => 'Properti',
    'agents' => 'Agen',
    'projects_in_city' => 'Proyek di :city',
    'properties_in_city' => 'Properti di :city',
    'projects_in_state' => 'Proyek di :state',
    'properties_in_state' => 'Properti di :state',
    'sort_date_asc' => 'Terlama',
    'sort_date_desc' => 'Terbaru',
    'sort_price_asc' => 'Harga (rendah ke tinggi)',
    'sort_price_desc' => 'Harga (tinggi ke rendah)',
    'sort_name_asc' => 'Nama (A-Z)',
    'sort_name_desc' => 'Nama (Z-A)',
    'area_unit_m2' => 'm²',
    'area_unit_ft2' => 'ft2',
    'area_unit_yd2' => 'yd2',
    'edit_property' => 'Edit properti ini',
    'edit_project' => 'Edit proyek ini',
    'edit_agent' => 'Edit agen ini',
    'property_no_longer_available' => 'Properti Tidak Lagi Tersedia: :name',
    'property_listing_expired' => 'Listing properti ini telah kedaluwarsa',
    'property_listing_no_longer_available' => 'Listing properti ini tidak lagi tersedia',
    'property_expired_title' => 'Properti Kedaluwarsa: :name',
];
