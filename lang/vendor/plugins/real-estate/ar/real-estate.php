<?php

return [
    'name' => 'العقارات',
    'settings' => 'الإعدادات',
    'login_form' => 'نموذج تسجيل الدخول',
    'register_form' => 'نموذج التسجيل',
    'forgot_password_form' => 'نموذج نسيان كلمة المرور',
    'reset_password_form' => 'نموذج إعادة تعيين كلمة المرور',
    'consult_form' => 'نموذج الاستشارة',
    'review_form' => 'نموذج المراجعة',
    'property_translations' => 'ترجمات العقارات',
    'project_translations' => 'ترجمات المشاريع',
    'theme_options' => [
        'slug_name' => 'روابط العقارات',
        'slug_description' => 'تخصيص الروابط المستخدمة لصفحات العقارات. كن حذراً عند التعديل لأنه قد يؤثر على تحسين محركات البحث وتجربة المستخدم. إذا حدث خطأ، يمكنك إعادة التعيين إلى الافتراضي بكتابة القيمة الافتراضية أو تركها فارغة.',
        'page_slug_name' => 'رابط صفحة :page',
        'page_slug_description' => 'سيبدو مثل :slug عند الوصول إلى الصفحة. القيمة الافتراضية هي :default.',
        'page_slug_already_exists' => 'رابط الصفحة :slug مستخدم بالفعل. يرجى اختيار رابط آخر.',
        'page_slugs' => [
            'projects_city' => 'المشاريع حسب المدينة',
            'projects_state' => 'المشاريع حسب الولاية',
            'properties_city' => 'العقارات حسب المدينة',
            'properties_state' => 'العقارات حسب الولاية',
            'login' => 'تسجيل الدخول',
            'register' => 'التسجيل',
            'reset_password' => 'إعادة تعيين كلمة المرور',
        ],
    ],
    'email_templates' => [
        // حقول شائعة
        'field_name' => 'الاسم:',
        'field_email' => 'البريد الإلكتروني:',
        'field_phone' => 'الهاتف:',
        'field_subject' => 'الموضوع:',
        'field_content' => 'المحتوى:',
        'field_address' => 'العنوان:',
        'field_package' => 'الباقة:',
        'field_price' => 'السعر:',
        'field_total' => 'المجموع:',
        'field_account' => 'الحساب:',

        // التحيات والخاتمة الشائعة
        'greeting_admin' => 'مرحباً أيها المسؤول!',
        'greeting_hello' => 'مرحباً',
        'greeting_dear' => 'عزيزي',
        'greeting_dear_admin' => 'عزيزي المسؤول،',
        'closing_regards' => 'مع تحيات،',
        'closing_thank_you' => 'شكراً لك',
        'closing_best_regards' => 'مع أطيب التحيات،',

        // الإجراءات الشائعة
        'action_view_property' => 'عرض العقار',
        'action_verify_now' => 'تحقق الآن',
        'action_reset_password' => 'إعادة تعيين كلمة المرور',

        // المصطلحات الشائعة
        'credits' => 'أرصدة',
        'per_credit' => '/رصيد',
        'save_amount' => '(وفر :amount)',
        'for_credits' => 'مقابل :count رصيد',

        // بريد العقار الجديد المعلق
        'new_pending_property_message' => 'تم إنشاء عقار جديد بواسطة :author ":name" وهو بانتظار الموافقة.',

        // بريد الموافقة على العقار
        'property_approved_greeting' => 'مرحباً :name،',
        'property_approved_message' => 'تمت الموافقة على عقارك ":property_name" بنجاح على :site_name.',
        'property_approved_access' => 'يمكنك الآن الوصول إلى الموقع وإدارة عقارك.',
        'property_approved_view_edit' => 'لعرض أو تحرير عقارك، يرجى النقر على هذا الرابط:',

        // بريد رفض العقار
        'property_rejected_greeting' => 'مرحباً :name،',
        'property_rejected_message' => 'تم رفض عقارك ":property_name" على :site_name.',
        'property_rejected_reason' => 'سبب الرفض كما يلي:',
        'property_rejected_contact' => 'إذا كنت تعتقد أن هذا القرار خاطئ، يرجى الاتصال بفريق الدعم لدينا على :email.',
        'property_rejected_view_edit' => 'لعرض أو تحرير عقارك، يرجى النقر على هذا الرابط:',

        // بريد تسجيل الحساب
        'account_registered_message' => 'تم تسجيل حساب جديد:',

        // بريد الموافقة على الحساب
        'account_approved_title' => 'تمت الموافقة على الحساب',
        'account_approved_greeting' => 'عزيزي :name،',
        'account_approved_congratulations' => 'تهانينا! تمت الموافقة على حسابك في :site_name.',
        'account_approved_login' => 'يمكنك الآن تسجيل الدخول والبدء في استخدام خدماتنا. إذا كان لديك أي أسئلة، لا تتردد في التواصل مع فريق الدعم لدينا.',
        'account_approved_thanks' => 'شكراً لانضمامك إلى :site_name!',

        // بريد رفض الحساب
        'account_rejected_title' => 'تم رفض الحساب',
        'account_rejected_greeting' => 'عزيزي :name،',
        'account_rejected_regret' => 'نأسف لإبلاغك أن حسابك في :site_name قد تم رفضه.',
        'account_rejected_reason' => 'سبب الرفض:',
        'account_rejected_contact' => 'إذا كان لديك أي أسئلة أو تعتقد أن هذا خطأ، يرجى الاتصال بفريق الدعم لدينا.',
        'account_rejected_thanks' => 'شكراً لتفهمك.',

        // بريد تأكيد البريد الإلكتروني
        'confirm_email_greeting' => 'مرحباً!',
        'confirm_email_verify' => 'يرجى التحقق من عنوان بريدك الإلكتروني للوصول إلى هذا الموقع. انقر على الزر أدناه للتحقق من بريدك الإلكتروني.',
        'confirm_email_trouble' => 'إذا كنت تواجه مشكلة في النقر على زر "تحقق الآن"، انسخ الرابط أدناه والصقه في متصفح الويب الخاص بك:',

        // بريد تذكير كلمة المرور
        'password_reminder_greeting' => 'مرحباً!',
        'password_reminder_request' => 'تلقيت هذا البريد الإلكتروني لأننا تلقينا طلباً لإعادة تعيين كلمة المرور لحسابك.',
        'password_reminder_no_action' => 'إذا لم تطلب إعادة تعيين كلمة المرور، فلا حاجة لاتخاذ أي إجراء.',
        'password_reminder_trouble' => 'إذا كنت تواجه مشكلة في النقر على زر "إعادة تعيين كلمة المرور"، انسخ الرابط أدناه والصقه في متصفح الويب الخاص بك:',

        // بريد إيصال الدفع
        'payment_receipt_greeting' => 'مرحباً :name،',
        'payment_receipt_title' => 'إيصال الدفع لعملية الشراء الخاصة بك:',

        // بريد استلام الدفع (للمسؤول)
        'payment_received_message' => 'تم استلام دفعة من :name',

        // بريد المطالبة بالرصيد المجاني
        'free_credit_claimed_message' => 'قام :name بالمطالبة بالأرصدة المجانية.',

        // بريد الإشعار (نموذج الاستشارة)
        'notice_title' => 'طلب استشارة جديد',
        'notice_message' => 'يوجد استشارة جديدة من :property_name',

        // بريد تأكيد المرسل (نموذج الاستشارة)
        'sender_confirmation_title' => 'شكراً لطلب الاستشارة الخاص بك!',
        'sender_confirmation_greeting' => 'عزيزي :name،',
        'sender_confirmation_thank_you' => 'شكراً لتواصلك معنا! لقد تلقينا طلب الاستشارة الخاص بك وسيقوم فريقنا بمراجعته قريباً. سيتواصل معك أحد ممثلينا في أقرب وقت ممكن.',
        'sender_confirmation_submission_details' => 'فيما يلي تفاصيل طلبك:',
        'sender_confirmation_additional_info' => 'إذا كان لديك أي معلومات إضافية أو أسئلة، لا تتردد في الرد على هذا البريد الإلكتروني.',
        'sender_confirmation_appreciate' => 'نقدر اهتمامك وسنكون على تواصل قريباً!',
    ],
    'contact_for_price' => 'اتصل',
    'contact_for_price' => 'اتصل',
    'yes' => 'نعم',
    'no' => 'لا',
    'projects' => 'المشاريع',
    'properties' => 'العقارات',
    'agents' => 'الوكلاء',
    'projects_in_city' => 'مشاريع في :city',
    'properties_in_city' => 'عقارات في :city',
    'projects_in_state' => 'مشاريع في :state',
    'properties_in_state' => 'عقارات في :state',
    'sort_date_asc' => 'الأقدم',
    'sort_date_desc' => 'الأحدث',
    'sort_price_asc' => 'السعر (من الأقل إلى الأعلى)',
    'sort_price_desc' => 'السعر (من الأعلى إلى الأقل)',
    'sort_name_asc' => 'الاسم (أ-ي)',
    'sort_name_desc' => 'الاسم (ي-أ)',
    'area_unit_m2' => 'م²',
    'area_unit_ft2' => 'قدم2',
    'area_unit_yd2' => 'ياردة2',
    'edit_property' => 'تحرير هذا العقار',
    'edit_project' => 'تحرير هذا المشروع',
    'edit_agent' => 'تحرير هذا الوكيل',
    'property_no_longer_available' => 'العقار غير متاح بعد الآن: :name',
    'property_listing_expired' => 'انتهت صلاحية إعلان العقار هذا',
    'property_listing_no_longer_available' => 'إعلان العقار هذا لم يعد متاحًا',
    'property_expired_title' => 'العقار منتهي الصلاحية: :name',
];
