<?php

return [
    'name' => '諮詢',
    'edit' => '查看諮詢',
    'statuses' => [
        'read' => '已讀',
        'unread' => '未讀',
    ],
    'phone' => '電話',
    'ip_address' => 'IP 地址',
    'content' => '詳情',
    'consult_information' => '諮詢資訊',
    'email' => [
        'header' => '電郵',
        'title' => '來自您網站的新諮詢',
        'success' => '諮詢提交成功!',
        'failed' => '目前無法發送請求,請稍後再試!',
    ],
    'form' => [
        'name' => [
            'required' => '姓名為必填項',
        ],
        'email' => [
            'required' => '電郵為必填項',
            'email' => '電郵地址無效',
        ],
        'content' => [
            'required' => '訊息為必填項',
        ],
    ],
    'consult_sent_from' => '此諮詢資訊來自',
    'time' => '時間',
    'consult_id' => '諮詢編號',
    'form_name' => '姓名',
    'form_email' => '電郵',
    'form_phone' => '電話',
    'mark_as_read' => '標記為已讀',
    'mark_as_unread' => '標記為未讀',
    'new_consult_notice' => '您有 <span class="bold">:count</span> 個新諮詢',
    'view_all' => '查看全部',
    'project' => '項目',
    'property' => '物業',
    'custom_field' => [
        'name' => '自訂欄位',
        'create' => '建立自訂欄位',
        'type' => '類型',
        'required' => '必填',
        'placeholder' => '提示文字',
        'order' => '排序',
        'options' => '選項',
        'option' => [
            'label' => '標籤',
            'value' => '值',
            'add' => '新增選項',
        ],
        'enums' => [
            'types' => [
                'text' => '文字',
                'number' => '數字',
                'dropdown' => '下拉選單',
                'checkbox' => '核取方塊',
                'radio' => '單選按鈕',
                'textarea' => '多行文字',
                'date' => '日期',
                'datetime' => '日期時間',
                'time' => '時間',
            ],
        ],
    ],
];
