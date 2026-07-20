<?php

return [
    'name' => 'Consults',
    'edit' => '상담 보기',
    'statuses' => [
        'read' => 'Read',
        'unread' => 'Unread',
    ],
    'phone' => 'Phone',
    'ip_address' => 'IP 주소',
    'content' => 'Details',
    'consult_information' => '상담정보',
    'email' => [
        'header' => 'Email',
        'title' => '사이트의 새로운 문의',
        'success' => '성공적으로 컨설트를 보냈습니다!',
        'failed' => '지금은 요청을 보낼 수 없습니다. 나중에 다시 시도해 주세요!',
    ],
    'form' => [
        'name' => [
            'required' => '이름은 필수입니다',
        ],
        'email' => [
            'required' => '이메일은 필수입니다',
            'email' => '이메일 주소가 유효하지 않습니다.',
        ],
        'content' => [
            'required' => '메시지가 필요합니다',
        ],
    ],
    'consult_sent_from' => '이 문의 정보는 다음에서 전송되었습니다.',
    'time' => 'Time',
    'consult_id' => '상담 ID',
    'form_name' => 'Name',
    'form_email' => 'Email',
    'form_phone' => 'Phone',
    'mark_as_read' => '읽음으로 표시',
    'mark_as_unread' => '읽지 않은 상태로 표시',
    'new_consult_notice' => '<span class="bold">:count</span>개의 새로운 문의가 있습니다.',
    'view_all' => '모두 보기',
    'project' => 'Project',
    'property' => 'Property',
    'custom_field' => [
        'name' => '사용자 정의 필드',
        'create' => '사용자 정의 필드 만들기',
        'type' => 'Type',
        'required' => 'Required',
        'placeholder' => 'Placeholder',
        'order' => 'Order',
        'options' => 'Options',
        'option' => [
            'label' => 'Label',
            'value' => 'Value',
            'add' => '새 옵션 추가',
        ],
        'enums' => [
            'types' => [
                'text' => 'Text',
                'number' => 'Number',
                'dropdown' => 'Dropdown',
                'checkbox' => 'Checkbox',
                'radio' => 'Radio',
                'textarea' => 'Textarea',
                'date' => 'Date',
                'datetime' => '날짜 시간',
                'time' => 'Time',
            ],
        ],
    ],
];
