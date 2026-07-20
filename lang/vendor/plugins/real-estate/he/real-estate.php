<?php

return [
    'name' => 'נדל"ן',
    'settings' => 'הגדרות',
    'login_form' => 'טופס התחברות',
    'register_form' => 'טופס הרשמה',
    'forgot_password_form' => 'טופס שכחתי סיסמה',
    'reset_password_form' => 'טופס איפוס סיסמה',
    'consult_form' => 'טופס ייעוץ',
    'review_form' => 'טופס ביקורת',
    'property_translations' => 'תרגומי נכסים',
    'project_translations' => 'תרגומי פרויקטים',
    'theme_options' => [
        'slug_name' => 'כתובות URL של נדל"ן',
        'slug_description' => 'התאם אישית את ה-slugs המשמשים לדפי נדל"ן. היזהר בעת שינוי מכיוון שזה עלול להשפיע על SEO וחוויית משתמש. אם משהו משתבש, תוכל לאפס לברירת המחדל על ידי הקלדת הערך המ default או השארתו ריק.',
        'page_slug_name' => 'slug של דף :page',
        'page_slug_description' => 'זה ייראה כך :slug כאשר תגש לדף. ערך ברירת המחדל הוא :default.',
        'page_slug_already_exists' => 'slug הדף :slug כבר בשימוש. אנא בחר אחר.',
        'page_slugs' => [
            'projects_city' => 'פרויקטים לפי עיר',
            'projects_state' => 'פרויקטים לפי מחוז',
            'properties_city' => 'נכסים לפי עיר',
            'properties_state' => 'נכסים לפי מחוז',
            'login' => 'התחברות',
            'register' => 'הרשמה',
            'reset_password' => 'איפוס סיסמה',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'שם:',
        'field_email' => 'אימייל:',
        'field_phone' => 'טלפון:',
        'field_subject' => 'נושא:',
        'field_content' => 'תוכן:',
        'field_address' => 'כתובת:',
        'field_package' => 'חבילה:',
        'field_price' => 'מחיר:',
        'field_total' => 'סה"כ:',
        'field_account' => 'חשבון:',

        // Common greetings and closings
        'greeting_admin' => 'שלום מנהל!',
        'greeting_hello' => 'שלום',
        'greeting_dear' => 'יקר',
        'greeting_dear_admin' => 'מנהל יקר,',
        'closing_regards' => 'בברכה,',
        'closing_thank_you' => 'תודה',
        'closing_best_regards' => 'בברכה רבה,',

        // Common actions
        'action_view_property' => 'צפה בנכס',
        'action_verify_now' => 'אמת עכשיו',
        'action_reset_password' => 'אפס סיסמה',

        // Common terms
        'credits' => 'נקודות',
        'per_credit' => '/נקודה',
        'save_amount' => '(חסוך :amount)',
        'for_credits' => 'עבור :count נקודות',

        // New pending property email
        'new_pending_property_message' => 'נכס חדש שנוצר על ידי :author ":name" ממתין לאישור.',

        // Property approved email
        'property_approved_greeting' => 'שלום :name,',
        'property_approved_message' => 'הנכס שלך ":property_name" אושר בהצלחה ב-:site_name.',
        'property_approved_access' => 'כעת תוכל לגשת לאתר ולנהל את הנכס שלך.',
        'property_approved_view_edit' => 'כדי לצפות או לערוך את הנכס שלך, אנא לחץ על קישור זה:',

        // Property rejected email
        'property_rejected_greeting' => 'שלום :name,',
        'property_rejected_message' => 'הנכס שלך ":property_name" נדחה ב-:site_name.',
        'property_rejected_reason' => 'הסיבה לדחייה היא כדלקמן:',
        'property_rejected_contact' => 'אם אתה מאמין שהחלטה זו התקבלה בטעות, אנא פנה לצוות התמיכה שלנו ב-:email.',
        'property_rejected_view_edit' => 'כדי לצפות או לערוך את הנכס שלך, אנא לחץ על קישור זה:',

        // Account registered email
        'account_registered_message' => 'חשבון חדש נרשם:',

        // Account approved email
        'account_approved_title' => 'החשבון אושר',
        'account_approved_greeting' => 'יקר :name,',
        'account_approved_congratulations' => 'מזל טוב! החשבון שלך ב-:site_name אושר.',
        'account_approved_login' => 'כעת תוכל להתחבר ולהתחיל להשתמש בשירותים שלנו. אם יש לך שאלות, אל תהסס לפנות לצוות התמיכה שלנו.',
        'account_approved_thanks' => 'תודה שהצטרפת ל-:site_name!',

        // Account rejected email
        'account_rejected_title' => 'החשבון נדחה',
        'account_rejected_greeting' => 'יקר :name,',
        'account_rejected_regret' => 'אנו מצטערים להודיע לך שהחשבון שלך ב-:site_name נדחה.',
        'account_rejected_reason' => 'סיבת הדחייה:',
        'account_rejected_contact' => 'אם יש לך שאלות או שאתה מאמין שזו טעות, אנא פנה לצוות התמיכה שלנו.',
        'account_rejected_thanks' => 'תודה על ההבנה שלך.',

        // Confirm email
        'confirm_email_greeting' => 'שלום!',
        'confirm_email_verify' => 'אנא אמת את כתובת האימייל שלך כדי לגשת לאתר זה. לחץ על הכפתור למטה כדי לאמת את האימייל שלך.',
        'confirm_email_trouble' => 'אם אתה נתקל בבעיה בלחיצה על הכפתור "אמת עכשיו", העתק והדבק את כתובת ה-URL למטה בדפדפן האינטרנט שלך:',

        // Password reminder email
        'password_reminder_greeting' => 'שלום!',
        'password_reminder_request' => 'אתה מקבל אימייל זה מכיוון שקיבלנו בקשה לאיפוס סיסמה עבור החשבון שלך.',
        'password_reminder_no_action' => 'אם לא ביקשת איפוס סיסמה, אין צורך בפעולה נוספת.',
        'password_reminder_trouble' => 'אם אתה נתקל בבעיה בלחיצה על הכפתור "אפס סיסמה", העתק והדבק את כתובת ה-URL למטה בדפדפן האינטרנט שלך:',

        // Payment receipt email
        'payment_receipt_greeting' => 'שלום :name,',
        'payment_receipt_title' => 'קבלת תשלום עבור הרכישה שלך:',

        // Payment received email (admin)
        'payment_received_message' => 'התקבל תשלום מ-:name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name תבע נקודות זכות בחינם.',

        // Notice email (consult form)
        'notice_title' => 'בקשת ייעוץ חדשה',
        'notice_message' => 'יש ייעוץ חדש מ-:property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'תודה על בקשת הייעוץ שלך!',
        'sender_confirmation_greeting' => 'יקר :name,',
        'sender_confirmation_thank_you' => 'תודה שפנית אלינו! קיבלנו את בקשת הייעוץ שלך והצוות שלנו יבדוק אותה בקרוב. אחד הנציגים שלנו ייצור איתך קשר בהקדם האפשרי.',
        'sender_confirmation_submission_details' => 'להלן הפרטים של ההגשה שלך:',
        'sender_confirmation_additional_info' => 'אם יש לך מידע נוסף או שאלות, אל תהסס להשיב לאימייל זה.',
        'sender_confirmation_appreciate' => 'אנו מעריכים את העניין שלך ונהיה בקשר בקרוב!',
    ],
];
