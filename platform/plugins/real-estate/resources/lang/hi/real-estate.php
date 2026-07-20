<?php

return [
    'name' => 'रियल एस्टेट',
    'settings' => 'सेटिंग्स',
    'login_form' => 'लॉगिन फॉर्म',
    'register_form' => 'पंजीकरण फॉर्म',
    'forgot_password_form' => 'पासवर्ड भूल गए फॉर्म',
    'reset_password_form' => 'पासवर्ड रीसेट फॉर्म',
    'consult_form' => 'परामर्श फॉर्म',
    'review_form' => 'समीक्षा फॉर्म',
    'property_translations' => 'संपत्ति अनुवाद',
    'project_translations' => 'परियोजना अनुवाद',
    'theme_options' => [
        'slug_name' => 'रियल एस्टेट URLs',
        'slug_description' => 'रियल एस्टेट पेजों के लिए उपयोग किए जाने वाले स्लग को कस्टमाइज़ करें। संशोधन करते समय सावधान रहें क्योंकि इससे SEO और उपयोगकर्ता अनुभव प्रभावित हो सकता है। यदि कुछ गलत हो जाता है, तो आप डिफ़ॉल्ट मान टाइप करके या इसे खाली छोड़कर डिफ़ॉल्ट पर रीसेट कर सकते हैं।',
        'page_slug_name' => ':page पेज स्लग',
        'page_slug_description' => 'जब आप पेज को एक्सेस करेंगे तो यह :slug की तरह दिखेगा। डिफ़ॉल्ट मान :default है।',
        'page_slug_already_exists' => ':slug पेज स्लग पहले से उपयोग में है। कृपया कोई अन्य चुनें।',
        'page_slugs' => [
            'projects_city' => 'शहर के अनुसार परियोजनाएं',
            'projects_state' => 'राज्य के अनुसार परियोजनाएं',
            'properties_city' => 'शहर के अनुसार संपत्तियां',
            'properties_state' => 'राज्य के अनुसार संपत्तियां',
            'login' => 'लॉगिन',
            'register' => 'पंजीकरण',
            'reset_password' => 'पासवर्ड रीसेट',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'नाम:',
        'field_email' => 'ईमेल:',
        'field_phone' => 'फोन:',
        'field_subject' => 'विषय:',
        'field_content' => 'सामग्री:',
        'field_address' => 'पता:',
        'field_package' => 'पैकेज:',
        'field_price' => 'मूल्य:',
        'field_total' => 'कुल:',
        'field_account' => 'खाता:',

        // Common greetings and closings
        'greeting_admin' => 'नमस्कार एडमिन!',
        'greeting_hello' => 'नमस्कार',
        'greeting_dear' => 'प्रिय',
        'greeting_dear_admin' => 'प्रिय एडमिन,',
        'closing_regards' => 'सादर,',
        'closing_thank_you' => 'धन्यवाद',
        'closing_best_regards' => 'सादर धन्यवाद,',

        // Common actions
        'action_view_property' => 'संपत्ति देखें',
        'action_verify_now' => 'अभी सत्यापित करें',
        'action_reset_password' => 'पासवर्ड रीसेट करें',

        // Common terms
        'credits' => 'क्रेडिट्स',
        'per_credit' => '/क्रेडिट',
        'save_amount' => '(:amount की बचत)',
        'for_credits' => ':count क्रेडिट्स के लिए',

        // New pending property email
        'new_pending_property_message' => ':author द्वारा बनाई गई नई संपत्ति ":name" अनुमोदन की प्रतीक्षा में है।',

        // Property approved email
        'property_approved_greeting' => 'नमस्कार :name,',
        'property_approved_message' => 'आपकी संपत्ति ":property_name" को :site_name पर सफलतापूर्वक अनुमोदित कर दिया गया है।',
        'property_approved_access' => 'अब आप वेबसाइट पर पहुंच सकते हैं और अपनी संपत्ति का प्रबंधन कर सकते हैं।',
        'property_approved_view_edit' => 'अपनी संपत्ति को देखने या संपादित करने के लिए, कृपया इस लिंक पर क्लिक करें:',

        // Property rejected email
        'property_rejected_greeting' => 'नमस्कार :name,',
        'property_rejected_message' => 'आपकी संपत्ति ":property_name" को :site_name पर अस्वीकार कर दिया गया है।',
        'property_rejected_reason' => 'अस्वीकृति का कारण निम्नलिखित है:',
        'property_rejected_contact' => 'यदि आपको लगता है कि यह निर्णय गलत है, तो कृपया हमारी सहायता टीम से :email पर संपर्क करें।',
        'property_rejected_view_edit' => 'अपनी संपत्ति को देखने या संपादित करने के लिए, कृपया इस लिंक पर क्लिक करें:',

        // Account registered email
        'account_registered_message' => 'एक नया खाता पंजीकृत हुआ:',

        // Account approved email
        'account_approved_title' => 'खाता अनुमोदित',
        'account_approved_greeting' => 'प्रिय :name,',
        'account_approved_congratulations' => 'बधाई हो! :site_name पर आपका खाता अनुमोदित हो गया है।',
        'account_approved_login' => 'अब आप लॉगिन कर सकते हैं और हमारी सेवाओं का उपयोग शुरू कर सकते हैं। यदि आपके कोई प्रश्न हैं, तो बेझिझक हमारी सहायता टीम से संपर्क करें।',
        'account_approved_thanks' => ':site_name में शामिल होने के लिए धन्यवाद!',

        // Account rejected email
        'account_rejected_title' => 'खाता अस्वीकृत',
        'account_rejected_greeting' => 'प्रिय :name,',
        'account_rejected_regret' => 'हमें खुशी से सूचित करना पड़ रहा है कि :site_name पर आपका खाता अस्वीकार कर दिया गया है।',
        'account_rejected_reason' => 'अस्वीकृति का कारण:',
        'account_rejected_contact' => 'यदि आपके कोई प्रश्न हैं या आपको लगता है कि यह एक त्रुटि है, तो कृपया हमारी सहायता टीम से संपर्क करें।',
        'account_rejected_thanks' => 'आपकी समझ के लिए धन्यवाद।',

        // Confirm email
        'confirm_email_greeting' => 'नमस्कार!',
        'confirm_email_verify' => 'इस वेबसाइट तक पहुंचने के लिए कृपया अपना ईमेल पता सत्यापित करें। अपना ईमेल सत्यापित करने के लिए नीचे दिए गए बटन पर क्लिक करें।',
        'confirm_email_trouble' => 'यदि आपको "अभी सत्यापित करें" बटन पर क्लिक करने में समस्या हो रही है, तो नीचे दिए गए URL को कॉपी करके अपने वेब ब्राउज़र में पेस्ट करें:',

        // Password reminder email
        'password_reminder_greeting' => 'नमस्कार!',
        'password_reminder_request' => 'आपको यह ईमेल इसलिए मिला है क्योंकि हमें आपके खाते के लिए पासवर्ड रीसेट का अनुरोध मिला है।',
        'password_reminder_no_action' => 'यदि आपने पासवर्ड रीसेट का अनुरोध नहीं किया है, तो कोई और कार्रवाई की आवश्यकता नहीं है।',
        'password_reminder_trouble' => 'यदि आपको "पासवर्ड रीसेट करें" बटन पर क्लिक करने में समस्या हो रही है, तो नीचे दिए गए URL को कॉपी करके अपने वेब ब्राउज़र में पेस्ट करें:',

        // Payment receipt email
        'payment_receipt_greeting' => 'नमस्कार :name,',
        'payment_receipt_title' => 'आपकी खरीदारी के लिए भुगतान रसीद:',

        // Payment received email (admin)
        'payment_received_message' => ':name से भुगतान प्राप्त हुआ',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name ने मुफ्त क्रेडिट्स का दावा किया है।',

        // Notice email (consult form)
        'notice_title' => 'नया परामर्श अनुरोध',
        'notice_message' => ':property_name से एक नया परामर्श आया है',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'आपके परामर्श अनुरोध के लिए धन्यवाद!',
        'sender_confirmation_greeting' => 'प्रिय :name,',
        'sender_confirmation_thank_you' => 'हमसे संपर्क करने के लिए धन्यवाद! हमें आपका परामर्श अनुरोध मिल गया है और हमारी टीम जल्द ही इसकी समीक्षा करेगी। हमारे प्रतिनिधियों में से कोई एक जल्द से जल्द आपसे संपर्क करेगा।',
        'sender_confirmation_submission_details' => 'यहां आपके सबमिशन का विवरण है:',
        'sender_confirmation_additional_info' => 'यदि आपके पास कोई अतिरिक्त जानकारी या प्रश्न हैं, तो बेझिझक इस ईमेल का उत्तर दें।',
        'sender_confirmation_appreciate' => 'हम आपकी रुचि की सराहना करते हैं और जल्द ही संपर्क में रहेंगे!',
    ],
    'contact_for_price' => 'संपर्क करें',
    'contact_for_price' => 'संपर्क',
    'yes' => 'हां',
    'no' => 'नहीं',
    'projects' => 'परियोजनाएं',
    'properties' => 'संपत्तियां',
    'agents' => 'एजेंट',
    'projects_in_city' => ':city में परियोजनाएं',
    'properties_in_city' => ':city में संपत्तियां',
    'projects_in_state' => ':state में परियोजनाएं',
    'properties_in_state' => ':state में संपत्तियां',
    'sort_date_asc' => 'सबसे पुराना',
    'sort_date_desc' => 'सबसे नया',
    'sort_price_asc' => 'मूल्य (कम से अधिक)',
    'sort_price_desc' => 'मूल्य (अधिक से कम)',
    'sort_name_asc' => 'नाम (A-Z)',
    'sort_name_desc' => 'नाम (Z-A)',
    'area_unit_m2' => 'वर्ग मीटर',
    'area_unit_ft2' => 'वर्ग फुट',
    'area_unit_yd2' => 'वर्ग गज',
    'edit_property' => 'इस संपत्ति को संपादित करें',
    'edit_project' => 'इस परियोजना को संपादित करें',
    'edit_agent' => 'इस एजेंट को संपादित करें',
    'property_no_longer_available' => 'संपत्ति अब उपलब्ध नहीं: :name',
    'property_listing_expired' => 'इस संपत्ति की लिस्टिंग की अवधि समाप्त हो गई है',
    'property_listing_no_longer_available' => 'यह संपत्ति लिस्टिंग अब उपलब्ध नहीं है',
    'property_expired_title' => 'संपत्ति समाप्त: :name',
];
