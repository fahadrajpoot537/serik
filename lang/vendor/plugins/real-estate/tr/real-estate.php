<?php

return [
    'name' => 'Gayrimenkul',
    'settings' => 'Ayarlar',
    'login_form' => 'Giriş Formu',
    'register_form' => 'Kayıt Formu',
    'forgot_password_form' => 'Şifre Unuttum Formu',
    'reset_password_form' => 'Şifre Sıfırlama Formu',
    'consult_form' => 'Danışma Formu',
    'review_form' => 'İnceleme Formu',
    'property_translations' => 'Mülk Çevirileri',
    'project_translations' => 'Proje Çevirileri',
    'theme_options' => [
        'slug_name' => 'Gayrimenkul URL\'leri',
        'slug_description' => 'Gayrimenkul sayfaları için kullanılan slug\'ları özelleştirin. Değiştirirken dikkatli olun çünkü SEO ve kullanıcı deneyimini etkileyebilir. Bir şeyler ters giderse, varsayılan değeri yazarak veya boş bırakarak varsayılana sıfırlayabilirsiniz.',
        'page_slug_name' => ':page sayfa slug\'ı',
        'page_slug_description' => 'Sayfaya eriştiğinizde :slug gibi görünecektir. Varsayılan değer :default\'dir.',
        'page_slug_already_exists' => ':slug sayfa slug\'ı zaten kullanımda. Lütfen başka birini seçin.',
        'page_slugs' => [
            'projects_city' => 'Şehre Göre Projeler',
            'projects_state' => 'Eyalete Göre Projeler',
            'properties_city' => 'Şehre Göre Mülkler',
            'properties_state' => 'Eyalete Göre Mülkler',
            'login' => 'Giriş',
            'register' => 'Kayıt',
            'reset_password' => 'Şifre Sıfırlama',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'İsim:',
        'field_email' => 'E-posta:',
        'field_phone' => 'Telefon:',
        'field_subject' => 'Konu:',
        'field_content' => 'İçerik:',
        'field_address' => 'Adres:',
        'field_package' => 'Paket:',
        'field_price' => 'Fiyat:',
        'field_total' => 'Toplam:',
        'field_account' => 'Hesap:',

        // Common greetings and closings
        'greeting_admin' => 'Merhaba Yönetici!',
        'greeting_hello' => 'Merhaba',
        'greeting_dear' => 'Sayın',
        'greeting_dear_admin' => 'Sayın Yönetici,',
        'closing_regards' => 'Saygılarımla,',
        'closing_thank_you' => 'Teşekkürler',
        'closing_best_regards' => 'Saygılarımla,',

        // Common actions
        'action_view_property' => 'Mülkü Görüntüle',
        'action_verify_now' => 'Şimdi doğrula',
        'action_reset_password' => 'Şifreyi sıfırla',

        // Common terms
        'credits' => 'kredi',
        'per_credit' => '/kredi',
        'save_amount' => '(:amount tasarruf)',
        'for_credits' => ':count kredi için',

        // New pending property email
        'new_pending_property_message' => ':author tarafından oluşturulan ":name" adlı yeni mülk onay bekliyor.',

        // Property approved email
        'property_approved_greeting' => 'Merhaba :name,',
        'property_approved_message' => '":property_name" adlı mülkünüz :site_name sitesinde başarıyla onaylandı.',
        'property_approved_access' => 'Artık web sitesine erişebilir ve mülkünüzü yönetebilirsiniz.',
        'property_approved_view_edit' => 'Mülkünüzü görüntülemek veya düzenlemek için lütfen bu bağlantıya tıklayın:',

        // Property rejected email
        'property_rejected_greeting' => 'Merhaba :name,',
        'property_rejected_message' => '":property_name" adlı mülkünüz :site_name sitesinde reddedildi.',
        'property_rejected_reason' => 'Reddetme nedeni şu şekildedir:',
        'property_rejected_contact' => 'Bu kararın hatalı olduğunu düşünüyorsanız, lütfen :email adresindeki destek ekibimizle iletişime geçin.',
        'property_rejected_view_edit' => 'Mülkünüzü görüntülemek veya düzenlemek için lütfen bu bağlantıya tıklayın:',

        // Account registered email
        'account_registered_message' => 'Yeni bir hesap kaydedildi:',

        // Account approved email
        'account_approved_title' => 'Hesap Onaylandı',
        'account_approved_greeting' => 'Sayın :name,',
        'account_approved_congratulations' => 'Tebrikler! :site_name\'deki hesabınız onaylandı.',
        'account_approved_login' => 'Artık giriş yapabilir ve hizmetlerimizi kullanmaya başlayabilirsiniz. Herhangi bir sorunuz varsa, destek ekibimizle iletişime geçmekten çekinmeyin.',
        'account_approved_thanks' => ':site_name\'ye katıldığınız için teşekkürler!',

        // Account rejected email
        'account_rejected_title' => 'Hesap Reddedildi',
        'account_rejected_greeting' => 'Sayın :name,',
        'account_rejected_regret' => ':site_name\'deki hesabınızın reddedildiğini bildirmekten üzüntü duyuyoruz.',
        'account_rejected_reason' => 'Reddetme nedeni:',
        'account_rejected_contact' => 'Herhangi bir sorunuz varsa veya bunun bir hata olduğunu düşünüyorsanız, lütfen destek ekibimizle iletişime geçin.',
        'account_rejected_thanks' => 'Anlayışınız için teşekkürler.',

        // Confirm email
        'confirm_email_greeting' => 'Merhaba!',
        'confirm_email_verify' => 'Bu web sitesine erişmek için lütfen e-posta adresinizi doğrulayın. E-postanızı doğrulamak için aşağıdaki düğmeye tıklayın.',
        'confirm_email_trouble' => '"Şimdi doğrula" düğmesine tıklamada sorun yaşıyorsanız, aşağıdaki URL\'yi web tarayıcınıza kopyalayıp yapıştırın:',

        // Password reminder email
        'password_reminder_greeting' => 'Merhaba!',
        'password_reminder_request' => 'Hesabınız için şifre sıfırlama isteği aldığımız için bu e-postayı alıyorsunuz.',
        'password_reminder_no_action' => 'Şifre sıfırlama talebinde bulunmadıysanız, başka bir işlem yapmanız gerekmez.',
        'password_reminder_trouble' => '"Şifreyi Sıfırla" düğmesine tıklamada sorun yaşıyorsanız, aşağıdaki URL\'yi web tarayıcınıza kopyalayıp yapıştırın:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Merhaba :name,',
        'payment_receipt_title' => 'Satın alımınız için ödeme makbuzu:',

        // Payment received email (admin)
        'payment_received_message' => ':name\'den ödeme alındı',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name ücretsiz kredi talep etti.',

        // Notice email (consult form)
        'notice_title' => 'Yeni Danışma Talebi',
        'notice_message' => ':property_name\'den yeni bir danışma var',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Danışma Talebiniz İçin Teşekkürler!',
        'sender_confirmation_greeting' => 'Sayın :name,',
        'sender_confirmation_thank_you' => 'Bizimle iletişime geçtiğiniz için teşekkürler! Danışma talebinizi aldık ve ekibimiz kısa süre içinde inceleyecek. Temsilcilerimizden biri en kısa sürede size geri dönüş yapacak.',
        'sender_confirmation_submission_details' => 'Gönderiminizin detayları:',
        'sender_confirmation_additional_info' => 'Ek bilginiz veya sorunuz varsa, bu e-postayı yanıtlamaktan çekinmeyin.',
        'sender_confirmation_appreciate' => 'İlginiz için teşekkür ederiz ve yakında iletişime geçeceğiz!',
    ],
    'contact_for_price' => 'İletişim',
    'contact_for_price' => 'İletişim',
    'yes' => 'Evet',
    'no' => 'Hayır',
    'projects' => 'Projeler',
    'properties' => 'Mülkler',
    'agents' => 'Acenteler',
    'projects_in_city' => ':city\'deki Projeler',
    'properties_in_city' => ':city\'deki Mülkler',
    'projects_in_state' => ':state\'deki Projeler',
    'properties_in_state' => ':state\'deki Mülkler',
    'sort_date_asc' => 'En eski',
    'sort_date_desc' => 'En yeni',
    'sort_price_asc' => 'Fiyat (düşükten yükseğe)',
    'sort_price_desc' => 'Fiyat (yüksekten düşüğe)',
    'sort_name_asc' => 'İsim (A-Z)',
    'sort_name_desc' => 'İsim (Z-A)',
    'area_unit_m2' => 'm²',
    'area_unit_ft2' => 'ft2',
    'area_unit_yd2' => 'yd2',
    'edit_property' => 'Bu mülkü düzenle',
    'edit_project' => 'Bu projeyi düzenle',
    'edit_agent' => 'Bu acenteyi düzenle',
    'property_no_longer_available' => 'Mülk Artık Mevcut Değil: :name',
    'property_listing_expired' => 'Bu mülk ilanının süresi doldu',
    'property_listing_no_longer_available' => 'Bu mülk ilanı artık mevcut değil',
    'property_expired_title' => 'Mülkün Süresi Doldu: :name',
];
