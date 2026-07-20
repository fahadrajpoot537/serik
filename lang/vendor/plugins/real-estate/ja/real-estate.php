<?php

return [
    'name' => '不動産',
    'settings' => '設定',
    'login_form' => 'ログインフォーム',
    'register_form' => '登録フォーム',
    'forgot_password_form' => 'パスワード忘れフォーム',
    'reset_password_form' => 'パスワードリセットフォーム',
    'consult_form' => '相談フォーム',
    'review_form' => 'レビューフォーム',
    'property_translations' => '物件翻訳',
    'project_translations' => 'プロジェクト翻訳',
    'theme_options' => [
        'slug_name' => '不動産URL',
        'slug_description' => '不動産ページで使用されるスラッグをカスタマイズします。SEOやユーザーエクスペリエンスに影響する可能性があるため、変更時は注意してください。問題が発生した場合は、デフォルト値を入力するか空白にしてデフォルトにリセットできます。',
        'page_slug_name' => ':pageページスラッグ',
        'page_slug_description' => 'ページにアクセスする際は:slugのように表示されます。デフォルト値は:defaultです。',
        'page_slug_already_exists' => ':slugページスラッグは既に使用されています。別のものを選択してください。',
        'page_slugs' => [
            'projects_city' => '都市別プロジェクト',
            'projects_state' => '都道府県別プロジェクト',
            'properties_city' => '都市別物件',
            'properties_state' => '都道府県別物件',
            'login' => 'ログイン',
            'register' => '登録',
            'reset_password' => 'パスワードリセット',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'お名前:',
        'field_email' => 'メールアドレス:',
        'field_phone' => '電話番号:',
        'field_subject' => '件名:',
        'field_content' => '内容:',
        'field_address' => '住所:',
        'field_package' => 'パッケージ:',
        'field_price' => '価格:',
        'field_total' => '合計:',
        'field_account' => 'アカウント:',

        // Common greetings and closings
        'greeting_admin' => '管理者様',
        'greeting_hello' => 'こんにちは',
        'greeting_dear' => '拝啓',
        'greeting_dear_admin' => '管理者様へ',
        'closing_regards' => '敬具',
        'closing_thank_you' => 'ありがとうございます',
        'closing_best_regards' => '何卒よろしくお願いいたします',

        // Common actions
        'action_view_property' => '物件を見る',
        'action_verify_now' => '今すぐ確認',
        'action_reset_password' => 'パスワードをリセット',

        // Common terms
        'credits' => 'クレジット',
        'per_credit' => '/クレジット',
        'save_amount' => '(:amount節約)',
        'for_credits' => ':countクレジット分',

        // New pending property email
        'new_pending_property_message' => ':authorによって作成された新しい物件「:name」が承認待ちです。',

        // Property approved email
        'property_approved_greeting' => ':name様',
        'property_approved_message' => 'お客様の物件「:property_name」が:site_nameで正常に承認されました。',
        'property_approved_access' => 'ウェブサイトにアクセスして物件を管理することができるようになりました。',
        'property_approved_view_edit' => '物件を表示または編集するには、こちらのリンクをクリックしてください:',

        // Property rejected email
        'property_rejected_greeting' => ':name様',
        'property_rejected_message' => 'お客様の物件「:property_name」が:site_nameで却下されました。',
        'property_rejected_reason' => '却下の理由は以下の通りです:',
        'property_rejected_contact' => 'この決定が誤りであると思われる場合は、:emailまでサポートチームにお問い合わせください。',
        'property_rejected_view_edit' => '物件を表示または編集するには、こちらのリンクをクリックしてください:',

        // Account registered email
        'account_registered_message' => '新しいアカウントが登録されました:',

        // Account approved email
        'account_approved_title' => 'アカウント承認',
        'account_approved_greeting' => ':name様',
        'account_approved_congratulations' => 'おめでとうございます！:site_nameでのお客様のアカウントが承認されました。',
        'account_approved_login' => 'ログインしてサービスのご利用を開始いただけます。ご質問がございましたら、お気軽にサポートチームまでお問い合わせください。',
        'account_approved_thanks' => ':site_nameへのご参加をありがとうございます！',

        // Account rejected email
        'account_rejected_title' => 'アカウント却下',
        'account_rejected_greeting' => ':name様',
        'account_rejected_regret' => '申し訳ございませんが、:site_nameでのお客様のアカウントが却下されました。',
        'account_rejected_reason' => '却下の理由:',
        'account_rejected_contact' => 'ご質問がある場合や、これが誤りであると思われる場合は、サポートチームまでお問い合わせください。',
        'account_rejected_thanks' => 'ご理解いただきありがとうございます。',

        // Confirm email
        'confirm_email_greeting' => 'こんにちは！',
        'confirm_email_verify' => 'このウェブサイトにアクセスするために、メールアドレスをご確認ください。下のボタンをクリックしてメールアドレスを確認してください。',
        'confirm_email_trouble' => '「今すぐ確認」ボタンのクリックに問題がある場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:',

        // Password reminder email
        'password_reminder_greeting' => 'こんにちは！',
        'password_reminder_request' => 'お客様のアカウントのパスワードリセット要求を受信したため、このメールをお送りしています。',
        'password_reminder_no_action' => 'パスワードリセットを要求していない場合は、それ以上の操作は必要ありません。',
        'password_reminder_trouble' => '「パスワードリセット」ボタンのクリックに問題がある場合は、以下のURLをコピーしてWebブラウザに貼り付けてください:',

        // Payment receipt email
        'payment_receipt_greeting' => ':name様',
        'payment_receipt_title' => 'ご購入の決済領収書:',

        // Payment received email (admin)
        'payment_received_message' => ':nameより決済を受領しました',

        // Free credit claimed email
        'free_credit_claimed_message' => ':nameが無料クレジットを取得しました。',

        // Notice email (consult form)
        'notice_title' => '新しい相談依頼',
        'notice_message' => ':property_nameから新しい相談があります',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'ご相談をありがとうございます！',
        'sender_confirmation_greeting' => ':name様',
        'sender_confirmation_thank_you' => 'お問い合わせいただきありがとうございます！ご相談を受け付けており、弊社チームが速やかに確認いたします。担当者より可能な限り早急にご連絡いたします。',
        'sender_confirmation_submission_details' => 'ご提出いただいた内容の詳細:',
        'sender_confirmation_additional_info' => '追加情報やご質問がございましたら、このメールに返信してお知らせください。',
        'sender_confirmation_appreciate' => 'ご関心をお寄せいただきありがとうございます。間もなくご連絡いたします！',
    ],
    'contact_for_price' => 'お問い合わせ',
    'contact_for_price' => '連絡',
    'yes' => 'はい',
    'no' => 'いいえ',
    'projects' => 'プロジェクト',
    'properties' => '物件',
    'agents' => 'エージェント',
    'projects_in_city' => ':cityのプロジェクト',
    'properties_in_city' => ':cityの物件',
    'projects_in_state' => ':stateのプロジェクト',
    'properties_in_state' => ':stateの物件',
    'sort_date_asc' => '古い順',
    'sort_date_desc' => '新しい順',
    'sort_price_asc' => '価格 (低から高)',
    'sort_price_desc' => '価格 (高から低)',
    'sort_name_asc' => '名前 (A-Z)',
    'sort_name_desc' => '名前 (Z-A)',
    'area_unit_m2' => 'm²',
    'area_unit_ft2' => 'ft2',
    'area_unit_yd2' => 'yd2',
    'edit_property' => 'この物件を編集',
    'edit_project' => 'このプロジェクトを編集',
    'edit_agent' => 'このエージェントを編集',
    'property_no_longer_available' => '物件は利用できません: :name',
    'property_listing_expired' => 'この物件リストの有効期限が切れました',
    'property_listing_no_longer_available' => 'この物件リストは利用できなくなりました',
    'property_expired_title' => '物件の有効期限切れ: :name',
];
