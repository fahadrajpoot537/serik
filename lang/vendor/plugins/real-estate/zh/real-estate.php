<?php

return [
    'name' => '房地產',
    'settings' => '設定',
    'login_form' => '登錄表單',
    'register_form' => '註冊表單',
    'forgot_password_form' => '忘記密碼表單',
    'reset_password_form' => '重置密碼表單',
    'consult_form' => '諮詢表單',
    'review_form' => '評論表單',
    'property_translations' => '房產翻譯',
    'project_translations' => '專案翻譯',
    'theme_options' => [
        'page_slug_name' => '頁面別名',
        'slug_description' => '自定義房地產頁面使用的別名。修改時請謹慎，因為這可能會影響 SEO 和用戶體驗。如果發生錯誤，您可以通過輸入默認值或將其留空來重置為默認值。',
        'page_slugs' => [
            'properties_state' => '按縣/市分類的屬性',
            'properties_city' => '按城市分類的屬性',
            'projects_state' => '按縣/市分類的項目',
            'projects_city' => '按城市分類的項目',
            'login' => '登入',
            'register' => '註冊',
            'reset_password' => '重設密碼',
        ],
        'page_slug_already_exists' => '頁面別名已被使用。請選擇另一個。',
        'page_slug_description' => '當您訪問該頁面時，它看起來像 :slug 。預設值為：預設。',
        'slug_name' => '房地產網址',
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => '姓名：',
        'field_email' => 'Email：',
        'field_phone' => '電話：',
        'field_subject' => '主旨：',
        'field_content' => '內容：',
        'field_address' => '地址：',
        'field_package' => '套餐：',
        'field_price' => '價格：',
        'field_total' => '總計：',
        'field_account' => '帳戶：',

        // Common greetings and closings
        'greeting_admin' => '您好，管理員！',
        'greeting_hello' => '您好',
        'greeting_dear' => '親愛的',
        'greeting_dear_admin' => '親愛的管理員，',
        'closing_regards' => '此致敬禮，',
        'closing_thank_you' => '謝謝您',
        'closing_best_regards' => '最誠摯的問候，',

        // Common actions
        'action_view_property' => '查看房產',
        'action_verify_now' => '立即驗證',
        'action_reset_password' => '重設密碼',

        // Common terms
        'credits' => '積分',
        'per_credit' => '/積分',
        'save_amount' => '（節省 :amount）',
        'for_credits' => ':count 個積分',

        // New pending property email
        'new_pending_property_message' => '由 :author 建立的新房產「:name」正在等待批准。',

        // Property approved email
        'property_approved_greeting' => '您好 :name，',
        'property_approved_message' => '您的房產「:property_name」已在 :site_name 成功批准。',
        'property_approved_access' => '您現在可以存取網站並管理您的房產。',
        'property_approved_view_edit' => '要查看或編輯您的房產，請點擊此連結：',

        // Property rejected email
        'property_rejected_greeting' => '您好 :name，',
        'property_rejected_message' => '您的房產「:property_name」已在 :site_name 被拒絕。',
        'property_rejected_reason' => '拒絕的原因如下：',
        'property_rejected_contact' => '如果您認為此決定有誤，請透過 :email 聯絡我們的支援團隊。',
        'property_rejected_view_edit' => '要查看或編輯您的房產，請點擊此連結：',

        // Account registered email
        'account_registered_message' => '新帳戶已註冊：',

        // Account approved email
        'account_approved_title' => '帳戶已批准',
        'account_approved_greeting' => '親愛的 :name，',
        'account_approved_congratulations' => '恭喜！您在 :site_name 的帳戶已被批准。',
        'account_approved_login' => '您現在可以登入並開始使用我們的服務。如有任何問題，請隨時聯絡我們的支援團隊。',
        'account_approved_thanks' => '感謝您加入 :site_name！',

        // Account rejected email
        'account_rejected_title' => '帳戶已拒絕',
        'account_rejected_greeting' => '親愛的 :name，',
        'account_rejected_regret' => '很遺憾地通知您，您在 :site_name 的帳戶已被拒絕。',
        'account_rejected_reason' => '拒絕原因：',
        'account_rejected_contact' => '如有任何問題或認為此為錯誤，請聯絡我們的支援團隊。',
        'account_rejected_thanks' => '感謝您的理解。',

        // Confirm email
        'confirm_email_greeting' => '您好！',
        'confirm_email_verify' => '請驗證您的Email地址以存取此網站。點擊下面的按鈕以驗證您的Email。',
        'confirm_email_trouble' => '如果您在點擊「立即驗證」按鈕時遇到問題，請將下面的網址複製並貼到您的網頁瀏覽器中：',

        // Password reminder email
        'password_reminder_greeting' => '您好！',
        'password_reminder_request' => '您收到此Email是因為我們收到了您帳戶的密碼重設請求。',
        'password_reminder_no_action' => '如果您沒有要求重設密碼，則無需進一步操作。',
        'password_reminder_trouble' => '如果您在點擊「重設密碼」按鈕時遇到問題，請將下面的網址複製並貼到您的網頁瀏覽器中：',

        // Payment receipt email
        'payment_receipt_greeting' => '您好 :name，',
        'payment_receipt_title' => '您的購買付款收據：',

        // Payment received email (admin)
        'payment_received_message' => '收到來自 :name 的付款',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name 已領取免費積分。',

        // Notice email (consult form)
        'notice_title' => '新的諮詢請求',
        'notice_message' => '來自 :property_name 的新諮詢',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => '感謝您的諮詢請求！',
        'sender_confirmation_greeting' => '親愛的 :name，',
        'sender_confirmation_thank_you' => '感謝您與我們聯絡！我們已收到您的諮詢請求，我們的團隊將盡快審核。我們的代表將盡快與您聯絡。',
        'sender_confirmation_submission_details' => '以下是您提交的詳細資訊：',
        'sender_confirmation_additional_info' => '如果您有任何其他資訊或問題，請隨時回覆此Email。',
        'sender_confirmation_appreciate' => '我們感謝您的關注，並將盡快與您聯絡！',
    ],
    'contact_for_price' => '聯繫',
    'contact_for_price' => '聯繫',
    'yes' => '是',
    'no' => '否',
    'projects' => '專案',
    'properties' => '房產',
    'agents' => '經紀人',
    'projects_in_city' => ':city 的專案',
    'properties_in_city' => ':city 的房產',
    'projects_in_state' => ':state 的專案',
    'properties_in_state' => ':state 的房產',
    'sort_date_asc' => '最舊',
    'sort_date_desc' => '最新',
    'sort_price_asc' => '價格（低到高）',
    'sort_price_desc' => '價格（高到低）',
    'sort_name_asc' => '名稱 (A-Z)',
    'sort_name_desc' => '名稱 (Z-A)',
    'area_unit_m2' => '平方米',
    'area_unit_ft2' => '平方英尺',
    'area_unit_yd2' => '平方碼',
    'edit_property' => '編輯此房產',
    'edit_project' => '編輯此專案',
    'edit_agent' => '編輯此經紀人',
    'property_no_longer_available' => '房產不再可用：:name',
    'property_listing_expired' => '此房產列表已過期',
    'property_listing_no_longer_available' => '此房產列表不再可用',
    'property_expired_title' => '房產已過期：:name',
];
