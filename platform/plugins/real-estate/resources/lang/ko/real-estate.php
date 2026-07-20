<?php

return [
    'name' => '부동산',
    'settings' => 'Settings',
    'login_form' => '로그인 양식',
    'register_form' => '등록 양식',
    'forgot_password_form' => '비밀번호 분실 양식',
    'reset_password_form' => '비밀번호 재설정 양식',
    'consult_form' => '문의 양식',
    'review_form' => '검토 양식',
    'property_translations' => '속성 번역',
    'project_translations' => '프로젝트 번역',
    'theme_options' => [
        'slug_name' => '부동산 URL',
        'slug_description' => '부동산 페이지에 사용되는 슬러그를 사용자 정의합니다. SEO와 사용자 경험에 영향을 미칠 수 있으므로 수정할 때 주의하세요. 문제가 발생하면 기본값을 입력하거나 비워 두어 기본값으로 재설정할 수 있습니다.',
        'page_slug_name' => ':page 페이지 슬러그',
        'page_slug_description' => '페이지에 접속하면 :slug처럼 보일 것입니다. 기본값은 :default입니다.',
        'page_slug_already_exists' => ':slug 페이지 슬러그가 이미 사용 중입니다. 다른 것을 선택하세요.',
        'page_slugs' => [
            'projects_city' => '도시별 프로젝트',
            'projects_state' => '주별 프로젝트',
            'properties_city' => '도시별 부동산',
            'properties_state' => '주별 부동산',
            'login' => 'Login',
            'register' => 'Register',
            'reset_password' => '비밀번호 재설정',
        ],
    ],
    'email_templates' => [
        // Common field labels
        'field_name' => '이름:',
        'field_email' => '이메일:',
        'field_phone' => '핸드폰:',
        'field_subject' => '주제:',
        'field_content' => '콘텐츠:',
        'field_address' => '주소:',
        'field_package' => '패키지:',
        'field_price' => '가격:',
        'field_total' => '총:',
        'field_account' => '계정:',

        // Common greetings and closings
        'greeting_admin' => '안녕하세요 관리자!',
        'greeting_hello' => 'Hello',
        'greeting_dear' => 'Dear',
        'greeting_dear_admin' => '친애하는 관리자 여러분,',
        'closing_regards' => '문안 인사,',
        'closing_thank_you' => '감사합니다',
        'closing_best_regards' => '감사합니다.',

        // Common actions
        'action_view_property' => '속성 보기',
        'action_verify_now' => '지금 확인',
        'action_reset_password' => '비밀번호 재설정',

        // Common terms
        'credits' => 'credits',
        'per_credit' => '/신용 거래',
        'save_amount' => '(:amount 저장)',
        'for_credits' => ':count 크레딧',

        // New pending property email
        'new_pending_property_message' => ':author ":name"이(가) 생성한 새 속성이 승인 대기 중입니다.',

        // Property approved email
        'property_approved_greeting' => ':name 님, 안녕하세요.',
        'property_approved_message' => '귀하의 속성 ":property_name"이(가) :site_name에 성공적으로 승인되었습니다.',
        'property_approved_access' => '이제 웹사이트에 접속하여 자산을 관리할 수 있습니다.',
        'property_approved_view_edit' => '귀하의 자산을 보거나 편집하려면 다음 링크를 클릭하십시오:',

        // Property rejected email
        'property_rejected_greeting' => ':name 님, 안녕하세요.',
        'property_rejected_message' => '귀하의 속성 ":property_name"이(가) :site_name에 성공적으로 거부되었습니다.',
        'property_rejected_reason' => '거절 이유는 다음과 같습니다.',
        'property_rejected_contact' => '이 결정이 잘못되었다고 생각되면 지원팀(:email)에 문의하세요.',
        'property_rejected_view_edit' => '귀하의 자산을 보거나 편집하려면 다음 링크를 클릭하십시오:',

        // Account registered email
        'account_registered_message' => '새 계정이 등록되었습니다:',

        // Account approved email
        'account_approved_title' => '계정이 승인되었습니다',
        'account_approved_greeting' => '친애하는 :name 님,',
        'account_approved_congratulations' => '축하해요! :site_name의 귀하의 계정이 승인되었습니다.',
        'account_approved_login' => '이제 로그인하여 서비스 이용을 시작할 수 있습니다. 궁금한 점이 있으면 언제든지 지원팀에 문의하세요.',
        'account_approved_thanks' => ':site_name에 참여해 주셔서 감사합니다!',

        // Account rejected email
        'account_rejected_title' => '계정이 거부됨',
        'account_rejected_greeting' => '친애하는 :name님,',
        'account_rejected_regret' => ':site_name의 귀하의 계정이 거부되었음을 알려드리게 되어 유감입니다.',
        'account_rejected_reason' => '거부 이유:',
        'account_rejected_contact' => '질문이 있거나 이것이 오류라고 생각되면 지원팀에 문의하세요.',
        'account_rejected_thanks' => '이해해 주셔서 감사합니다.',

        // Confirm email
        'confirm_email_greeting' => '안녕하세요!',
        'confirm_email_verify' => '이 웹사이트에 액세스하려면 이메일 주소를 확인하세요. 이메일을 확인하려면 아래 버튼을 클릭하세요.',
        'confirm_email_trouble' => '"지금 확인" 버튼을 클릭하는 데 문제가 있는 경우 아래 URL을 복사하여 웹 브라우저에 붙여넣으세요.',

        // Password reminder email
        'password_reminder_greeting' => '안녕하세요!',
        'password_reminder_request' => '귀하의 계정에 대한 비밀번호 재설정 요청이 접수되었기 때문에 이 이메일을 보내드립니다.',
        'password_reminder_no_action' => '비밀번호 재설정을 요청하지 않은 경우 추가 조치가 필요하지 않습니다.',
        'password_reminder_trouble' => '"비밀번호 재설정" 버튼을 클릭하는 데 문제가 있는 경우 아래 URL을 복사하여 웹 브라우저에 붙여넣으세요.',

        // Payment receipt email
        'payment_receipt_greeting' => '안녕하세요 :name님,',
        'payment_receipt_title' => '구매에 대한 결제 영수증:',

        // Payment received email (admin)
        'payment_received_message' => ':name에서 결제 완료',

        // Free credit claimed email
        'free_credit_claimed_message' => ':name님이 무료 크레딧을 요청했습니다.',

        // Notice email (consult form)
        'notice_title' => '새로운 컨설트 요청',
        'notice_message' => ':property_name님의 새로운 문의가 있습니다.',

        // Sender confirmation email (consult form)
        'sender_confirmation_title' => '귀하의 상담 요청에 감사드립니다!',
        'sender_confirmation_greeting' => '친애하는 :name 님,',
        'sender_confirmation_thank_you' => '문의해 주셔서 감사합니다! 귀하의 상담 요청이 접수되었으며 담당 팀에서 곧 이를 검토하겠습니다. 당사 담당자 중 한 명이 최대한 빨리 연락을 드릴 것입니다.',
        'sender_confirmation_submission_details' => '제출 세부정보는 다음과 같습니다.',
        'sender_confirmation_additional_info' => '추가 정보나 질문이 있는 경우 언제든지 이 이메일에 회신해 주세요.',
        'sender_confirmation_appreciate' => '귀하의 관심에 감사드리며 곧 연락드리겠습니다!',
    ],
];
