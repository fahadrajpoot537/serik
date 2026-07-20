<?php

return [
    'name' => 'อสังหาริมทรัพย์',
    'settings' => 'การตั้งค่า',
    'login_form' => 'แบบฟอร์มเข้าสู่ระบบ',
    'register_form' => 'แบบฟอร์มสมัครสมาชิก',
    'forgot_password_form' => 'แบบฟอร์มลืมรหัสผ่าน',
    'reset_password_form' => 'แบบฟอร์มรีเซ็ตรหัสผ่าน',
    'consult_form' => 'แบบฟอร์มปรึกษา',
    'review_form' => 'แบบฟอร์มรีวิว',
    'property_translations' => 'การแปลอสังหาริมทรัพย์',
    'project_translations' => 'การแปลโครงการ',
    'theme_options' => [
        'slug_name' => 'URL อสังหาริมทรัพย์',
        'slug_description' => 'ปรับแต่ง slug ที่ใช้สำหรับหน้าอสังหาริมทรัพย์ ระวังเมื่อแก้ไขเพราะอาจส่งผลต่อ SEO และประสบการณ์ผู้ใช้ หากมีปัญหา คุณสามารถรีเซ็ตเป็นค่าเริ่มต้นได้โดยพิมพ์ค่าเริ่มต้นหรือเว้นว่างไว้',
        'page_slug_name' => 'slug หน้า :page',
        'page_slug_description' => 'จะดูเหมือน :slug เมื่อคุณเข้าถึงหน้า ค่าเริ่มต้นคือ :default',
        'page_slug_already_exists' => 'slug หน้า :slug ถูกใช้งานแล้ว กรุณาเลือกอันอื่น',
        'page_slugs' => [
            'projects_city' => 'โครงการตามเมือง',
            'projects_state' => 'โครงการตามรัฐ',
            'properties_city' => 'อสังหาริมทรัพย์ตามเมือง',
            'properties_state' => 'อสังหาริมทรัพย์ตามรัฐ',
            'login' => 'เข้าสู่ระบบ',
            'register' => 'สมัครสมาชิก',
            'reset_password' => 'รีเซ็ตรหัสผ่าน',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => 'ชื่อ:',
        'field_email' => 'อีเมล:',
        'field_phone' => 'เบอร์โทร:',
        'field_subject' => 'หัวข้อ:',
        'field_content' => 'เนื้อหา:',
        'field_address' => 'ที่อยู่:',
        'field_package' => 'แพ็คเกจ:',
        'field_price' => 'ราคา:',
        'field_total' => 'รวม:',
        'field_account' => 'บัญชี:',

        // Common greetings and closings
        'greeting_admin' => 'สวัสดีผู้ดูแลระบบ!',
        'greeting_hello' => 'สวัสดี',
        'greeting_dear' => 'เรียน',
        'greeting_dear_admin' => 'เรียนผู้ดูแลระบบ',
        'closing_regards' => 'ด้วยความเคารพ',
        'closing_thank_you' => 'ขอบคุณ',
        'closing_best_regards' => 'ด้วยความเคารพอย่างสูง',

        // Common actions
        'action_view_property' => 'ดูอสังหาริมทรัพย์',
        'action_verify_now' => 'ยืนยันตอนนี้',
        'action_reset_password' => 'รีเซ็ตรหัสผ่าน',

        // Common terms
        'credits' => 'เครดิต',
        'per_credit' => '/เครดิต',
        'save_amount' => '(ประหยัด :amount)',
        'for_credits' => 'สำหรับ :count เครดิต',

        // New pending property email
        'new_pending_property_message' => 'อสังหาริมทรัพย์ใหม่ที่สร้างโดย :author ":name" กำลังรออนุมัติ',

        // Property approved email
        'property_approved_greeting' => 'สวัสดี :name',
        'property_approved_message' => 'อสังหาริมทรัพย์ของคุณ ":property_name" ได้รับการอนุมัติสำเร็จบน :site_name',
        'property_approved_access' => 'ตอนนี้คุณสามารถเข้าใช้งานเว็บไซต์และจัดการอสังหาริมทรัพย์ของคุณได้',
        'property_approved_view_edit' => 'เพื่อดูหรือแก้ไขอสังหาริมทรัพย์ของคุณ กรุณาคลิกลิงก์นี้:',

        // Property rejected email
        'property_rejected_greeting' => 'สวัสดี :name',
        'property_rejected_message' => 'อสังหาริมทรัพย์ของคุณ ":property_name" ถูกปฏิเสธบน :site_name',
        'property_rejected_reason' => 'เหตุผลในการปฏิเสธมีดังนี้:',
        'property_rejected_contact' => 'หากคุณเชื่อว่าการตัดสินใจนี้ผิดพลาด กรุณาติดต่อทีมสนับสนุนของเราที่ :email',
        'property_rejected_view_edit' => 'เพื่อดูหรือแก้ไขอสังหาริมทรัพย์ของคุณ กรุณาคลิกลิงก์นี้:',

        // Account registered email
        'account_registered_message' => 'มีบัญชีใหม่ลงทะเบียน:',

        // Account approved email
        'account_approved_title' => 'บัญชีได้รับการอนุมัติ',
        'account_approved_greeting' => 'เรียน :name',
        'account_approved_congratulations' => 'ยินดีด้วย! บัญชีของคุณบน :site_name ได้รับการอนุมัติแล้ว',
        'account_approved_login' => 'ตอนนี้คุณสามารถเข้าสู่ระบบและเริ่มใช้งานบริการของเราได้ หากมีคำถาม สามารถติดต่อทีมสนับสนุนของเราได้',
        'account_approved_thanks' => 'ขอบคุณที่เข้าร่วม :site_name!',

        // Account rejected email
        'account_rejected_title' => 'บัญชีถูกปฏิเสธ',
        'account_rejected_greeting' => 'เรียน :name',
        'account_rejected_regret' => 'เราเสียใจที่ต้องแจ้งให้ทราบว่าบัญชีของคุณบน :site_name ถูกปฏิเสธ',
        'account_rejected_reason' => 'เหตุผลในการปฏิเสธ:',
        'account_rejected_contact' => 'หากมีคำถามหรือเชื่อว่านี่เป็นข้อผิดพลาด กรุณาติดต่อทีมสนับสนุนของเรา',
        'account_rejected_thanks' => 'ขอบคุณสำหรับความเข้าใจ',

        // Confirm email
        'confirm_email_greeting' => 'สวัสดี!',
        'confirm_email_verify' => 'กรุณายืนยันที่อยู่อีเมลของคุณเพื่อเข้าถึงเว็บไซต์นี้ คลิกปุ่มด้านล่างเพื่อยืนยันอีเมลของคุณ',
        'confirm_email_trouble' => 'หากคุณมีปัญหาในการคลิกปุ่ม "ยืนยันตอนนี้" ให้คัดลอกและวาง URL ด้านล่างในเว็บเบราว์เซอร์ของคุณ:',

        // Password reminder email
        'password_reminder_greeting' => 'สวัสดี!',
        'password_reminder_request' => 'คุณได้รับอีเมลนี้เพราะเราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ',
        'password_reminder_no_action' => 'หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน ไม่จำเป็นต้องดำเนินการใด ๆ เพิ่มเติม',
        'password_reminder_trouble' => 'หากคุณมีปัญหาในการคลิกปุ่ม "รีเซ็ตรหัสผ่าน" ให้คัดลอกและวาง URL ด้านล่างในเว็บเบราว์เซอร์ของคุณ:',

        // Payment receipt email
        'payment_receipt_greeting' => 'สวัสดี :name',
        'payment_receipt_title' => 'ใบเสร็จการชำระเงินสำหรับการซื้อของคุณ:',

        // Payment received email (admin)
        'payment_received_message' => 'ได้รับการชำระเงินจาก :name',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name ได้รับเครดิตฟรี',

        // Notice email (consult form)
        'notice_title' => 'คำขอปรึกษาใหม่',
        'notice_message' => 'มีคำปรึกษาใหม่จาก :property_name',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => 'ขอบคุณสำหรับคำขอปรึกษาของคุณ!',
        'sender_confirmation_greeting' => 'เรียน :name',
        'sender_confirmation_thank_you' => 'ขอบคุณที่ติดต่อเรา! เราได้รับคำขอปรึกษาของคุณแล้ว และทีมงานของเราจะตรวจสอบในเร็ว ๆ นี้ ตัวแทนของเราจะติดต่อกลับโดยเร็วที่สุด',
        'sender_confirmation_submission_details' => 'นี่คือรายละเอียดของการส่งของคุณ:',
        'sender_confirmation_additional_info' => 'หากคุณมีข้อมูลเพิ่มเติมหรือคำถาม สามารถตอบกลับอีเมลนี้ได้',
        'sender_confirmation_appreciate' => 'เราขอขอบคุณสำหรับความสนใจ และจะติดต่อกลับในเร็ว ๆ นี้!',
    ],
    'contact_for_price' => 'ติดต่อ',
    'contact_for_price' => 'ติดต่อ',
    'yes' => 'ใช่',
    'no' => 'ไม่ใช่',
    'projects' => 'โครงการ',
    'properties' => 'อสังหาริมทรัพย์',
    'agents' => 'ตัวแทน',
    'projects_in_city' => 'โครงการใน :city',
    'properties_in_city' => 'อสังหาริมทรัพย์ใน :city',
    'projects_in_state' => 'โครงการใน :state',
    'properties_in_state' => 'อสังหาริมทรัพย์ใน :state',
    'sort_date_asc' => 'เก่าที่สุด',
    'sort_date_desc' => 'ใหม่ที่สุด',
    'sort_price_asc' => 'ราคา (ต่ำไปสูง)',
    'sort_price_desc' => 'ราคา (สูงไปต่ำ)',
    'sort_name_asc' => 'ชื่อ (A-Z)',
    'sort_name_desc' => 'ชื่อ (Z-A)',
    'area_unit_m2' => 'ตร.ม.',
    'area_unit_ft2' => 'ตร.ฟุต',
    'area_unit_yd2' => 'ตร.หลา',
    'edit_property' => 'แก้ไขอสังหาริมทรัพย์นี้',
    'edit_project' => 'แก้ไขโครงการนี้',
    'edit_agent' => 'แก้ไขตัวแทนนี้',
    'property_no_longer_available' => 'อสังหาริมทรัพย์ไม่พร้อมใช้งานอีกต่อไป: :name',
    'property_listing_expired' => 'รายการอสังหาริมทรัพย์นี้หมดอายุแล้ว',
    'property_listing_no_longer_available' => 'รายการอสังหาริมทรัพย์นี้ไม่พร้อมใช้งานอีกต่อไป',
    'property_expired_title' => 'อสังหาริมทรัพย์หมดอายุ: :name',
];
