<?php

return [
    'name' => '房地产',
    'settings' => '设置',
    'login_form' => '登录表单',
    'register_form' => '注册表单',
    'forgot_password_form' => '忘记密码表单',
    'reset_password_form' => '重置密码表单',
    'consult_form' => '咨询表单',
    'review_form' => '评论表单',
    'property_translations' => '房产翻译',
    'project_translations' => '项目翻译',
    'theme_options' => [
        'slug_name' => '房地产URL',
        'slug_description' => '自定义房地产页面使用的URL别名。修改时请谨慎,因为这可能会影响SEO和用户体验。如果出现问题,您可以通过输入默认值或留空来重置为默认值。',
        'page_slug_name' => ':page 页面URL别名',
        'page_slug_description' => '访问页面时将显示为 :slug。默认值为 :default。',
        'page_slug_already_exists' => ':slug 页面URL别名已被使用。请选择另一个。',
        'page_slugs' => [
            'projects_city' => '按城市浏览项目',
            'projects_state' => '按省/州浏览项目',
            'properties_city' => '按城市浏览房产',
            'properties_state' => '按省/州浏览房产',
            'login' => '登录',
            'register' => '注册',
            'reset_password' => '重置密码',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => '姓名:',
        'field_email' => '邮箱:',
        'field_phone' => '电话:',
        'field_subject' => '主题:',
        'field_content' => '内容:',
        'field_address' => '地址:',
        'field_package' => '套餐:',
        'field_price' => '价格:',
        'field_total' => '总计:',
        'field_account' => '账号:',

        // Common greetings and closings
        'greeting_admin' => '您好,管理员!',
        'greeting_hello' => '您好',
        'greeting_dear' => '尊敬的',
        'greeting_dear_admin' => '尊敬的管理员,',
        'closing_regards' => '此致,',
        'closing_thank_you' => '谢谢',
        'closing_best_regards' => '诚挚问候,',

        // Common actions
        'action_view_property' => '查看房产',
        'action_verify_now' => '立即验证',
        'action_reset_password' => '重置密码',

        // Common terms
        'credits' => '积分',
        'per_credit' => '/积分',
        'save_amount' => '(节省 :amount)',
        'for_credits' => ':count 积分',

        // New pending property email
        'new_pending_property_message' => '由 :author 创建的新房产 ":name" 正在等待批准。',

        // Property approved email
        'property_approved_greeting' => '您好 :name,',
        'property_approved_message' => '您的房产 ":property_name" 已在 :site_name 成功批准。',
        'property_approved_access' => '您现在可以访问网站并管理您的房产。',
        'property_approved_view_edit' => '要查看或编辑您的房产,请点击此链接:',

        // Property rejected email
        'property_rejected_greeting' => '您好 :name,',
        'property_rejected_message' => '您的房产 ":property_name" 已在 :site_name 被拒绝。',
        'property_rejected_reason' => '拒绝原因如下:',
        'property_rejected_contact' => '如果您认为此决定有误,请通过 :email 联系我们的支持团队。',
        'property_rejected_view_edit' => '要查看或编辑您的房产,请点击此链接:',

        // Account registered email
        'account_registered_message' => '新账号已注册:',

        // Account approved email
        'account_approved_title' => '账号已批准',
        'account_approved_greeting' => '尊敬的 :name,',
        'account_approved_congratulations' => '恭喜!您在 :site_name 的账号已被批准。',
        'account_approved_login' => '您现在可以登录并开始使用我们的服务。如有任何问题,请随时联系我们的支持团队。',
        'account_approved_thanks' => '感谢您加入 :site_name!',

        // Account rejected email
        'account_rejected_title' => '账号被拒绝',
        'account_rejected_greeting' => '尊敬的 :name,',
        'account_rejected_regret' => '我们很遗憾地通知您,您在 :site_name 的账号已被拒绝。',
        'account_rejected_reason' => '拒绝原因:',
        'account_rejected_contact' => '如有任何问题或认为这是错误,请联系我们的支持团队。',
        'account_rejected_thanks' => '感谢您的理解。',

        // Confirm email
        'confirm_email_greeting' => '您好!',
        'confirm_email_verify' => '请验证您的邮箱地址以访问本网站。点击下面的按钮验证您的邮箱。',
        'confirm_email_trouble' => '如果您无法点击"立即验证"按钮,请将下面的URL复制并粘贴到您的网络浏览器中:',

        // Password reminder email
        'password_reminder_greeting' => '您好!',
        'password_reminder_request' => '您收到此邮件是因为我们收到了您账号的密码重置请求。',
        'password_reminder_no_action' => '如果您没有请求重置密码,则无需进一步操作。',
        'password_reminder_trouble' => '如果您无法点击"重置密码"按钮,请将下面的URL复制并粘贴到您的网络浏览器中:',

        // Payment receipt email
        'payment_receipt_greeting' => '您好 :name,',
        'payment_receipt_title' => '您购买的付款收据:',

        // Payment received email (admin)
        'payment_received_message' => '收到来自 :name 的付款',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name 已领取免费积分。',

        // Notice email (consult form)
        'notice_title' => '新咨询请求',
        'notice_message' => '来自 :property_name 的新咨询',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => '感谢您的咨询请求!',
        'sender_confirmation_greeting' => '尊敬的 :name,',
        'sender_confirmation_thank_you' => '感谢您与我们联系!我们已收到您的咨询请求,我们的团队将尽快审核。我们的代表将尽快与您联系。',
        'sender_confirmation_submission_details' => '以下是您提交的详细信息:',
        'sender_confirmation_additional_info' => '如有任何其他信息或问题,请随时回复此邮件。',
        'sender_confirmation_appreciate' => '感谢您的关注,我们将尽快与您联系!',
    ],
];
