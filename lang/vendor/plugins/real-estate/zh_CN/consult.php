<?php

return [
    'name' => '咨询',
    'edit' => '查看咨询',
    'statuses' => [
        'read' => '已读',
        'unread' => '未读',
    ],
    'phone' => '电话',
    'ip_address' => 'IP地址',
    'content' => '详情',
    'consult_information' => '咨询信息',
    'email' => [
        'header' => '邮箱',
        'title' => '来自您网站的新咨询',
        'success' => '发送咨询成功!',
        'failed' => '目前无法发送请求,请稍后再试!',
    ],
    'form' => [
        'name' => [
            'required' => '姓名为必填项',
        ],
        'email' => [
            'required' => '邮箱为必填项',
            'email' => '邮箱地址无效',
        ],
        'content' => [
            'required' => '留言为必填项',
        ],
    ],
    'consult_sent_from' => '此咨询信息发送自',
    'time' => '时间',
    'consult_id' => '咨询ID',
    'form_name' => '姓名',
    'form_email' => '邮箱',
    'form_phone' => '电话',
    'mark_as_read' => '标记为已读',
    'mark_as_unread' => '标记为未读',
    'new_consult_notice' => '您有 <span class="bold">:count</span> 条新咨询',
    'view_all' => '查看全部',
    'project' => '项目',
    'property' => '房产',
    'custom_field' => [
        'name' => '自定义字段',
        'create' => '创建自定义字段',
        'type' => '类型',
        'required' => '必填',
        'placeholder' => '占位符',
        'order' => '排序',
        'options' => '选项',
        'option' => [
            'label' => '标签',
            'value' => '值',
            'add' => '添加新选项',
        ],
        'enums' => [
            'types' => [
                'text' => '文本',
                'number' => '数字',
                'dropdown' => '下拉菜单',
                'checkbox' => '复选框',
                'radio' => '单选框',
                'textarea' => '文本域',
                'date' => '日期',
                'datetime' => '日期时间',
                'time' => '时间',
            ],
        ],
    ],
];
