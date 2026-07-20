<?php

return [
    'name' => 'Real Estate',
    'settings' => 'Settings',
    'login_form' => 'Login Form',
    'register_form' => 'Register Form',
    'forgot_password_form' => 'Forgot Password Form',
    'reset_password_form' => 'Reset Password Form',
    'consult_form' => 'Consult Form',
    'review_form' => 'Review Form',
    'property_translations' => 'Property Translations',
    'project_translations' => 'Project Translations',
    'theme_options' => [
        'slug_name' => 'Real Estate URLs',
        'slug_description' => 'Customize the slugs used for real estate pages. Be cautious when modifying as it can affect SEO and user experience. If something goes wrong, you can reset to default by typing the default value or leave it blank.',
        'page_slug_name' => ':page page slug',
        'page_slug_description' => 'It will look like :slug when you access the page. Default value is :default.',
        'page_slug_already_exists' => 'The :slug page slug is already in use. Please choose another one.',
        'page_slugs' => [
            'projects_city' => 'Projects by City',
            'projects_state' => 'Projects by State',
            'properties_city' => 'Properties by City',
            'properties_state' => 'Properties by State',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => 'Reset Password',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'Name:',
        'field_email' => 'Email:',
        'field_phone' => 'Phone:',
        'field_subject' => 'Subject:',
        'field_content' => 'Content:',
        'field_address' => 'Address:',
        'field_package' => 'Package:',
        'field_price' => 'Price:',
        'field_total' => 'Total:',
        'field_account' => 'Account:',

        // Common greetings and closings
        'greeting_admin' => 'Hi Admin!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => 'Dear Admin,',
        'closing_regards' => 'Regards,',
        'closing_thank_you' => 'Thank you',
        'closing_best_regards' => 'Best regards,',

        // Common actions
        'action_view_property' => 'View Property',
        'action_verify_now' => 'Verify now',
        'action_reset_password' => 'Reset password',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/credit',
        'save_amount' => '(Save :amount)',
        'for_credits' => 'for :count credits',

        // New pending property email
        'new_pending_property_message' => 'A new property created by :author ":name" is waiting for approve.',

        // Property approved email
        'property_approved_greeting' => 'Hello :name,',
        'property_approved_message' => 'Your property ":property_name" has been successfully approved on :site_name.',
        'property_approved_access' => 'You can now access the website and manage your property.',
        'property_approved_view_edit' => 'To view or edit your property, please click on this link:',

        // Property rejected email
        'property_rejected_greeting' => 'Hello :name,',
        'property_rejected_message' => 'Your property ":property_name" has been successfully rejected on :site_name.',
        'property_rejected_reason' => 'The reason for rejection is as follows:',
        'property_rejected_contact' => 'If you believe this decision was made in error, please contact our support team at :email.',
        'property_rejected_view_edit' => 'To view or edit your property, please click on this link:',

        // Account registered email
        'account_registered_message' => 'A new account registered:',

        // Account approved email
        'account_approved_title' => 'Account Approved',
        'account_approved_greeting' => 'Dear :name,',
        'account_approved_congratulations' => 'Congratulations! Your account on :site_name has been approved.',
        'account_approved_login' => 'You can now log in and start using our services. If you have any questions, feel free to reach out to our support team.',
        'account_approved_thanks' => 'Thank you for joining :site_name!',

        // Account rejected email
        'account_rejected_title' => 'Account Rejected',
        'account_rejected_greeting' => 'Dear :name,',
        'account_rejected_regret' => 'We regret to inform you that your account on :site_name has been rejected.',
        'account_rejected_reason' => 'Reason for rejection:',
        'account_rejected_contact' => 'If you have any questions or believe this is an error, please contact our support team.',
        'account_rejected_thanks' => 'Thank you for your understanding.',

        // Confirm email
        'confirm_email_greeting' => 'Hello!',
        'confirm_email_verify' => 'Please verify your email address in order to access this website. Click on the button below to verify your email..',
        'confirm_email_trouble' => 'If you\'re having trouble clicking the "Verify now" button, copy and paste the URL below into your web browser:',

        // Password reminder email
        'password_reminder_greeting' => 'Hello!',
        'password_reminder_request' => 'You are receiving this email because we received a password reset request for your account.',
        'password_reminder_no_action' => 'If you did not request a password reset, no further action is required.',
        'password_reminder_trouble' => 'If you\'re having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:',

        // Payment receipt email
        'payment_receipt_greeting' => 'Hi :name,',
        'payment_receipt_title' => 'Payment receipt for your purchase:',

        // Payment received email (admin)
        'payment_received_message' => 'Payment received from :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name has claimed free credits.',

        // Notice email (consult form)
        'notice_title' => 'New Consult Request',
        'notice_message' => 'There is a new consult from :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'Thank You for Your Consult Request!',
        'sender_confirmation_greeting' => 'Dear :name,',
        'sender_confirmation_thank_you' => 'Thank you for reaching out to us! We have received your consult request and our team will review it shortly. One of our representatives will get back to you as soon as possible.',
        'sender_confirmation_submission_details' => 'Here are the details of your submission:',
        'sender_confirmation_additional_info' => 'If you have any additional information or questions, feel free to reply to this email.',
        'sender_confirmation_appreciate' => 'We appreciate your interest and will be in touch soon!',
    ],
];
