<?php

return [
    'name' => 'রিয়েল এস্টেট',
    'settings' => 'সেটিংস',
    'login_form' => 'লগইন ফর্ম',
    'register_form' => 'রেজিস্টার ফর্ম',
    'forgot_password_form' => 'পাসওয়ার্ড ভুলে গেছেন ফর্ম',
    'reset_password_form' => 'পাসওয়ার্ড রিসেট ফর্ম',
    'consult_form' => 'পরামর্শ ফর্ম',
    'review_form' => 'পর্যালোচনা ফর্ম',
    'property_translations' => 'সম্পত্তি অনুবাদ',
    'project_translations' => 'প্রকল্প অনুবাদ',
    'theme_options' => [
        'slug_name' => 'রিয়েল এস্টেট URLগুলি',
        'slug_description' => 'রিয়েল এস্টেট পৃষ্ঠাগুলির জন্য ব্যবহৃত স্লাগগুলি কাস্টমাইজ করুন। পরিবর্তন করার সময় সতর্ক থাকুন কারণ এটি SEO এবং ব্যবহারকারীর অভিজ্ঞতাকে প্রভাবিত করতে পারে। যদি কোন সমস্যা হয়, আপনি ডিফল্ট মান লিখে অথবা খালি রেখে ডিফল্টে রিসেট করতে পারেন।',
        'page_slug_name' => ':page পৃষ্ঠার স্লাগ',
        'page_slug_description' => 'আপনি পৃষ্ঠায় প্রবেশ করলে এটি :slug এর মতো দেখাবে। ডিফল্ট মান হল :default।',
        'page_slug_already_exists' => ':slug পৃষ্ঠার স্লাগ ইতিমধ্যে ব্যবহৃত হচ্ছে। অনুগ্রহ করে অন্য একটি বেছে নিন।',
        'page_slugs' => [
            'projects_city' => 'শহর অনুযায়ী প্রকল্প',
            'projects_state' => 'রাজ্য অনুযায়ী প্রকল্প',
            'properties_city' => 'শহর অনুযায়ী সম্পত্তি',
            'properties_state' => 'রাজ্য অনুযায়ী সম্পত্তি',
            'login' => 'লগইন',
            'register' => 'রেজিস্টার',
            'reset_password' => 'পাসওয়ার্ড রিসেট',
        ],
    ],
    'email_templates' => [
        // সাধারণ ফিল্ড লেবেল
        'field_name' => 'নাম:',
        'field_email' => 'ইমেইল:',
        'field_phone' => 'ফোন:',
        'field_subject' => 'বিষয়:',
        'field_content' => 'বিষয়বস্তু:',
        'field_address' => 'ঠিকানা:',
        'field_package' => 'প্যাকেজ:',
        'field_price' => 'মূল্য:',
        'field_total' => 'মোট:',
        'field_account' => 'অ্যাকাউন্ট:',

        // সাধারণ শুভেচ্ছা এবং সমাপনী
        'greeting_admin' => 'হাই অ্যাডমিন!',
        'greeting_hello' => 'হ্যালো',
        'greeting_dear' => 'প্রিয়',
        'greeting_dear_admin' => 'প্রিয় অ্যাডমিন,',
        'closing_regards' => 'শুভেচ্ছা সহ,',
        'closing_thank_you' => 'ধন্যবাদ',
        'closing_best_regards' => 'শুভেচ্ছান্তে,',

        // সাধারণ কর্ম
        'action_view_property' => 'সম্পত্তি দেখুন',
        'action_verify_now' => 'এখন যাচাই করুন',
        'action_reset_password' => 'পাসওয়ার্ড রিসেট করুন',

        // সাধারণ শর্তাবলী
        'credits' => 'ক্রেডিট',
        'per_credit' => '/ক্রেডিট',
        'save_amount' => '(:amount সংরক্ষণ করুন)',
        'for_credits' => ':count ক্রেডিটের জন্য',

        // নতুন মুলতুবি সম্পত্তি ইমেইল
        'new_pending_property_message' => ':author দ্বারা সৃষ্ট একটি নতুন সম্পত্তি ":name" অনুমোদনের জন্য অপেক্ষা করছে।',

        // সম্পত্তি অনুমোদিত ইমেইল
        'property_approved_greeting' => 'হ্যালো :name,',
        'property_approved_message' => 'আপনার সম্পত্তি ":property_name" :site_name এ সফলভাবে অনুমোদিত হয়েছে।',
        'property_approved_access' => 'আপনি এখন ওয়েবসাইটে প্রবেশ করতে এবং আপনার সম্পত্তি পরিচালনা করতে পারেন।',
        'property_approved_view_edit' => 'আপনার সম্পত্তি দেখতে বা সম্পাদনা করতে, দয়া করে এই লিংকে ক্লিক করুন:',

        // সম্পত্তি প্রত্যাখ্যাত ইমেইল
        'property_rejected_greeting' => 'হ্যালো :name,',
        'property_rejected_message' => 'আপনার সম্পত্তি ":property_name" :site_name এ প্রত্যাখ্যান করা হয়েছে।',
        'property_rejected_reason' => 'প্রত্যাখ্যানের কারণ নিম্নরূপ:',
        'property_rejected_contact' => 'আপনি যদি মনে করেন এই সিদ্ধান্তটি ত্রুটিপূর্ণ, দয়া করে আমাদের সহায়তা দলের সাথে :email এ যোগাযোগ করুন।',
        'property_rejected_view_edit' => 'আপনার সম্পত্তি দেখতে বা সম্পাদনা করতে, দয়া করে এই লিংকে ক্লিক করুন:',

        // অ্যাকাউন্ট নিবন্ধিত ইমেইল
        'account_registered_message' => 'একটি নতুন অ্যাকাউন্ট নিবন্ধিত হয়েছে:',

        // অ্যাকাউন্ট অনুমোদিত ইমেইল
        'account_approved_title' => 'অ্যাকাউন্ট অনুমোদিত',
        'account_approved_greeting' => 'প্রিয় :name,',
        'account_approved_congratulations' => 'অভিনন্দন! :site_name এ আপনার অ্যাকাউন্ট অনুমোদিত হয়েছে।',
        'account_approved_login' => 'আপনি এখন লগইন করতে এবং আমাদের সেবা ব্যবহার শুরু করতে পারেন। যদি আপনার কোন প্রশ্ন থাকে, আমাদের সহায়তা দলের সাথে যোগাযোগ করতে দ্বিধা করবেন না।',
        'account_approved_thanks' => ':site_name এ যোগ দেওয়ার জন্য ধন্যবাদ!',

        // অ্যাকাউন্ট প্রত্যাখ্যাত ইমেইল
        'account_rejected_title' => 'অ্যাকাউন্ট প্রত্যাখ্যাত',
        'account_rejected_greeting' => 'প্রিয় :name,',
        'account_rejected_regret' => 'আমরা দুঃখিত যে :site_name এ আপনার অ্যাকাউন্ট প্রত্যাখ্যান করা হয়েছে।',
        'account_rejected_reason' => 'প্রত্যাখ্যানের কারণ:',
        'account_rejected_contact' => 'যদি আপনার কোন প্রশ্ন থাকে বা আপনি মনে করেন এটি একটি ত্রুটি, দয়া করে আমাদের সহায়তা দলের সাথে যোগাযোগ করুন।',
        'account_rejected_thanks' => 'আপনার বোঝার জন্য ধন্যবাদ।',

        // ইমেইল নিশ্চিত করুন
        'confirm_email_greeting' => 'হ্যালো!',
        'confirm_email_verify' => 'এই ওয়েবসাইটে প্রবেশ করতে দয়া করে আপনার ইমেইল ঠিকানা যাচাই করুন। আপনার ইমেইল যাচাই করতে নীচের বোতামে ক্লিক করুন।',
        'confirm_email_trouble' => 'যদি আপনি "এখন যাচাই করুন" বোতামে ক্লিক করতে সমস্যা হয়, নীচের URL টি কপি করে আপনার ওয়েব ব্রাউজারে পেস্ট করুন:',

        // পাসওয়ার্ড রিমাইন্ডার ইমেইল
        'password_reminder_greeting' => 'হ্যালো!',
        'password_reminder_request' => 'আপনি এই ইমেইলটি পাচ্ছেন কারণ আমরা আপনার অ্যাকাউন্টের জন্য পাসওয়ার্ড রিসেট অনুরোধ পেয়েছি।',
        'password_reminder_no_action' => 'যদি আপনি পাসওয়ার্ড রিসেট অনুরোধ না করে থাকেন, তাহলে আর কোন পদক্ষেপের প্রয়োজন নেই।',
        'password_reminder_trouble' => 'যদি আপনি "পাসওয়ার্ড রিসেট" বোতামে ক্লিক করতে সমস্যা হয়, নীচের URL টি কপি করে আপনার ওয়েব ব্রাউজারে পেস্ট করুন:',

        // পেমেন্ট রসিদ ইমেইল
        'payment_receipt_greeting' => 'হাই :name,',
        'payment_receipt_title' => 'আপনার ক্রয়ের জন্য পেমেন্ট রসিদ:',

        // পেমেন্ট প্রাপ্ত ইমেইল (অ্যাডমিন)
        'payment_received_message' => ':name থেকে পেমেন্ট প্রাপ্ত হয়েছে',

        // বিনামূল্যে ক্রেডিট দাবি করা ইমেইল
        'free_credit_claimed_message' => ':name বিনামূল্যে ক্রেডিট দাবি করেছে।',

        // নোটিশ ইমেইল (পরামর্শ ফর্ম)
        'notice_title' => 'নতুন পরামর্শ অনুরোধ',
        'notice_message' => ':property_name থেকে একটি নতুন পরামর্শ আছে',

        // প্রেরক নিশ্চিতকরণ ইমেইল (পরামর্শ ফর্ম)
        'sender_confirmation_title' => 'আপনার পরামর্শ অনুরোধের জন্য ধন্যবাদ!',
        'sender_confirmation_greeting' => 'প্রিয় :name,',
        'sender_confirmation_thank_you' => 'আমাদের সাথে যোগাযোগ করার জন্য ধন্যবাদ! আমরা আপনার পরামর্শ অনুরোধ পেয়েছি এবং আমাদের দল শীঘ্রই এটি পর্যালোচনা করবে। আমাদের একজন প্রতিনিধি যত তাড়াতাড়ি সম্ভব আপনার সাথে যোগাযোগ করবে।',
        'sender_confirmation_submission_details' => 'এখানে আপনার জমা দেওয়ার বিস্তারিত:',
        'sender_confirmation_additional_info' => 'যদি আপনার কোন অতিরিক্ত তথ্য বা প্রশ্ন থাকে, এই ইমেইলে উত্তর দিতে দ্বিধা করবেন না।',
        'sender_confirmation_appreciate' => 'আমরা আপনার আগ্রহের প্রশংসা করি এবং শীঘ্রই যোগাযোগ করব!',
    ],
    'contact_for_price' => 'যোগাযোগ',
    'contact_for_price' => 'যোগাযোগ',
    'yes' => 'হ্যাঁ',
    'no' => 'না',
    'projects' => 'প্রকল্প',
    'properties' => 'সম্পত্তি',
    'agents' => 'এজেন্ট',
    'projects_in_city' => ':city-এ প্রকল্প',
    'properties_in_city' => ':city-এ সম্পত্তি',
    'projects_in_state' => ':state-এ প্রকল্প',
    'properties_in_state' => ':state-এ সম্পত্তি',
    'sort_date_asc' => 'পুরাতন',
    'sort_date_desc' => 'নতুন',
    'sort_price_asc' => 'মূল্য (কম থেকে বেশি)',
    'sort_price_desc' => 'মূল্য (বেশি থেকে কম)',
    'sort_name_asc' => 'নাম (A-Z)',
    'sort_name_desc' => 'নাম (Z-A)',
    'area_unit_m2' => 'বর্গমিটার',
    'area_unit_ft2' => 'বর্গফুট',
    'area_unit_yd2' => 'বর্গগজ',
    'edit_property' => 'এই সম্পত্তি সম্পাদনা করুন',
    'edit_project' => 'এই প্রকল্প সম্পাদনা করুন',
    'edit_agent' => 'এই এজেন্ট সম্পাদনা করুন',
    'property_no_longer_available' => 'সম্পত্তি আর উপলব্ধ নেই: :name',
    'property_listing_expired' => 'এই সম্পত্তির তালিকার মেয়াদ শেষ হয়েছে',
    'property_listing_no_longer_available' => 'এই সম্পত্তির তালিকা আর উপলব্ধ নেই',
    'property_expired_title' => 'সম্পত্তির মেয়াদ শেষ: :name',
];
