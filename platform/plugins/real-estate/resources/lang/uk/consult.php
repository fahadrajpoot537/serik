<?php

return [
    'name' => 'Консультації',
    'edit' => 'Переглянути консультацію',
    'statuses' => [
        'read' => 'Прочитано',
        'unread' => 'Не прочитано',
    ],
    'phone' => 'Телефон',
    'ip_address' => 'IP адреса',
    'content' => 'Деталі',
    'consult_information' => 'Інформація про консультацію',
    'email' => [
        'header' => 'Email',
        'title' => 'Нова консультація з вашого сайту',
        'success' => 'Консультацію успішно надіслано!',
        'failed' => 'Не вдалося надіслати запит, спробуйте пізніше!',
    ],
    'form' => [
        'name' => [
            'required' => 'Ім\'я обов\'язкове',
        ],
        'email' => [
            'required' => 'Email обов\'язковий',
            'email' => 'Email адреса недійсна',
        ],
        'content' => [
            'required' => 'Повідомлення обов\'язкове',
        ],
    ],
    'consult_sent_from' => 'Цю інформацію про консультацію надіслано з',
    'time' => 'Час',
    'consult_id' => 'ID консультації',
    'form_name' => 'Ім\'я',
    'form_email' => 'Email',
    'form_phone' => 'Телефон',
    'mark_as_read' => 'Позначити як прочитане',
    'mark_as_unread' => 'Позначити як непрочитане',
    'new_consult_notice' => 'У вас <span class="bold">:count</span> нових консультацій',
    'view_all' => 'Переглянути всі',
    'project' => 'Проєкт',
    'property' => 'Об\'єкт',
    'custom_field' => [
        'name' => 'Користувацькі поля',
        'create' => 'Створити користувацьке поле',
        'type' => 'Тип',
        'required' => 'Обов\'язкове',
        'placeholder' => 'Заповнювач',
        'order' => 'Порядок',
        'options' => 'Опції',
        'option' => [
            'label' => 'Мітка',
            'value' => 'Значення',
            'add' => 'Додати нову опцію',
        ],
        'enums' => [
            'types' => [
                'text' => 'Текст',
                'number' => 'Число',
                'dropdown' => 'Випадаючий список',
                'checkbox' => 'Прапорець',
                'radio' => 'Радіокнопка',
                'textarea' => 'Текстова область',
                'date' => 'Дата',
                'datetime' => 'Дата та час',
                'time' => 'Час',
            ],
        ],
    ],
];
