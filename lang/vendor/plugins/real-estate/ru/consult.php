<?php

return [
    'name' => 'Consults',
    'edit' => 'Посмотреть консультацию',
    'statuses' => [
        'read' => 'Read',
        'unread' => 'Unread',
    ],
    'phone' => 'Phone',
    'ip_address' => 'IP-адрес',
    'content' => 'Details',
    'consult_information' => 'Проконсультируйтесь с информацией',
    'email' => [
        'header' => 'Email',
        'title' => 'Новая консультация с вашего сайта',
        'success' => 'Отправить консультацию успешно!',
        'failed' => 'Не удалось отправить запрос в этот раз. Повторите попытку позже!',
    ],
    'form' => [
        'name' => [
            'required' => 'Требуется имя',
        ],
        'email' => [
            'required' => 'Требуется электронная почта',
            'email' => 'Адрес электронной почты недействителен',
        ],
        'content' => [
            'required' => 'Требуется сообщение',
        ],
    ],
    'consult_sent_from' => 'Эта консультационная информация, отправленная из',
    'time' => 'Time',
    'consult_id' => 'Проконсультируйтесь с идентификатором',
    'form_name' => 'Name',
    'form_email' => 'Email',
    'form_phone' => 'Phone',
    'mark_as_read' => 'Отметить как прочитанное',
    'mark_as_unread' => 'Отметить как непрочитанное',
    'new_consult_notice' => 'У вас <span class="bold">:count</span> новых консультаций',
    'view_all' => 'Посмотреть все',
    'project' => 'Project',
    'property' => 'Property',
    'custom_field' => [
        'name' => 'Пользовательские поля',
        'create' => 'Создать настраиваемое поле',
        'type' => 'Type',
        'required' => 'Required',
        'placeholder' => 'Placeholder',
        'order' => 'Order',
        'options' => 'Options',
        'option' => [
            'label' => 'Label',
            'value' => 'Value',
            'add' => 'Добавить новую опцию',
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
                'datetime' => 'Дата время',
                'time' => 'Time',
            ],
        ],
    ],
];
