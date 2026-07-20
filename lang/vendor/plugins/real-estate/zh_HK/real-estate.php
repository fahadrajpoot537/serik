<?php

return [
    'name' => '房地產',
    'settings' => '設定',
    'login_form' => '登入表單',
    'register_form' => '註冊表單',
    'forgot_password_form' => '忘記密碼表單',
    'reset_password_form' => '重設密碼表單',
    'consult_form' => '諮詢表單',
    'review_form' => '評論表單',
    'property_translations' => '物業翻譯',
    'project_translations' => '項目翻譯',
    'theme_options' => [
        'slug_name' => '房地產網址',
        'slug_description' => '自訂房地產頁面使用的網址。修改時請謹慎,因為這可能會影響 SEO 和用戶體驗。如果出現問題,您可以輸入預設值或留空以重設為預設值。',
        'page_slug_name' => ':page 頁面網址',
        'page_slug_description' => '當您訪問該頁面時,網址將顯示為 :slug。預設值為 :default。',
        'page_slug_already_exists' => ':slug 頁面網址已被使用。請選擇其他網址。',
        'page_slugs' => [
            'projects_city' => '按城市瀏覽項目',
            'projects_state' => '按州/省瀏覽項目',
            'properties_city' => '按城市瀏覽物業',
            'properties_state' => '按州/省瀏覽物業',
            'login' => '登入',
            'register' => '註冊',
            'reset_password' => '重設密碼',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => '姓名:',
        'field_email' => '電郵:',
        'field_phone' => '電話:',
        'field_subject' => '主旨:',
        'field_content' => '內容:',
        'field_address' => '地址:',
        'field_package' => '套餐:',
        'field_price' => '價格:',
        'field_total' => '總計:',
        'field_account' => '帳戶:',

        // Common greetings and closings
        'greeting_admin' => '您好,管理員!',
        'greeting_hello' => '您好',
        'greeting_dear' => '親愛的',
        'greeting_dear_admin' => '親愛的管理員,',
        'closing_regards' => '此致,',
        'closing_thank_you' => '謝謝',
        'closing_best_regards' => '謹啟,',

        // Common actions
        'action_view_property' => '查看物業',
        'action_verify_now' => '立即驗證',
        'action_reset_password' => '重設密碼',

        // Common terms
        'credits' => '積分',
        'per_credit' => '/積分',
        'save_amount' => '(節省 :amount)',
        'for_credits' => ':count 積分',

        // New pending property email
        'new_pending_property_message' => '由 :author 建立的新物業「:name」正在等待審核。',

        // Property approved email
        'property_approved_greeting' => '您好 :name,',
        'property_approved_message' => '您的物業「:property_name」已在 :site_name 成功批准。',
        'property_approved_access' => '您現在可以訪問網站並管理您的物業。',
        'property_approved_view_edit' => '要查看或編輯您的物業,請按此連結:',

        // Property rejected email
        'property_rejected_greeting' => '您好 :name,',
        'property_rejected_message' => '您的物業「:property_name」已在 :site_name 被拒絕。',
        'property_rejected_reason' => '拒絕原因如下:',
        'property_rejected_contact' => '如果您認為此決定有誤,請透過 :email 聯絡我們的支援團隊。',
        'property_rejected_view_edit' => '要查看或編輯您的物業,請按此連結:',

        // Account registered email
        'account_registered_message' => '新帳戶已註冊:',

        // Account approved email
        'account_approved_title' => '帳戶已批准',
        'account_approved_greeting' => '親愛的 :name,',
        'account_approved_congratulations' => '恭喜!您在 :site_name 的帳戶已獲批准。',
        'account_approved_login' => '您現在可以登入並開始使用我們的服務。如有任何問題,請隨時聯絡我們的支援團隊。',
        'account_approved_thanks' => '感謝您加入 :site_name!',

        // Account rejected email
        'account_rejected_title' => '帳戶已拒絕',
        'account_rejected_greeting' => '親愛的 :name,',
        'account_rejected_regret' => '很遺憾通知您,您在 :site_name 的帳戶已被拒絕。',
        'account_rejected_reason' => '拒絕原因:',
        'account_rejected_contact' => '如有任何問題或認為此決定有誤,請聯絡我們的支援團隊。',
        'account_rejected_thanks' => '感謝您的諒解。',

        // Confirm email
        'confirm_email_greeting' => '您好!',
        'confirm_email_verify' => '請驗證您的電郵地址以訪問此網站。按下方按鈕以驗證您的電郵。',
        'confirm_email_trouble' => '如果無法點擊「立即驗證」按鈕,請複製以下網址並貼到您的瀏覽器:',

        // Password reminder email
        'password_reminder_greeting' => '您好!',
        'password_reminder_request' => '您收到此電郵是因為我們收到了您帳戶的密碼重設請求。',
        'password_reminder_no_action' => '如果您沒有請求重設密碼,則無需採取任何行動。',
        'password_reminder_trouble' => '如果無法點擊「重設密碼」按鈕,請複製以下網址並貼到您的瀏覽器:',

        // Payment receipt email
        'payment_receipt_greeting' => '您好 :name,',
        'payment_receipt_title' => '您的購買收據:',

        // Payment received email (admin)
        'payment_received_message' => '已收到來自 :name 的付款',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name 已領取免費積分。',

        // Notice email (consult form)
        'notice_title' => '新諮詢請求',
        'notice_message' => '來自 :property_name 的新諮詢',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => '感謝您的諮詢請求!',
        'sender_confirmation_greeting' => '親愛的 :name,',
        'sender_confirmation_thank_you' => '感謝您聯絡我們!我們已收到您的諮詢請求,我們的團隊將盡快審閱。我們的代表將盡快回覆您。',
        'sender_confirmation_submission_details' => '以下是您提交的詳情:',
        'sender_confirmation_additional_info' => '如有任何補充資訊或問題,請隨時回覆此電郵。',
        'sender_confirmation_appreciate' => '感謝您的關注,我們將盡快與您聯絡!',
    ],
];
