<?php

return [
    'wiki' => [
        'title' => 'Вики',
        'description' => 'запись в блоге, можно комментировать',
        'new_title' => 'Добавление заметки',
        'edit_title' => 'Редактирование заметки',
        'edit_roles' => ['admin'],
        'order' => 'created DESC',
        'fields' => [
            'name' => [
                'label' => 'Заголовок',
                'type' => 'textline',
                'required' => true,
            ],
            'source' => [
                'type' => 'textarea',
                'rows' => 10,
                'required' => true,
                'class' => 'markdown',
                'help' => 'Можно использовать <a href="http://ilfire.ru/kompyutery/shpargalka-po-sintaksisu-markdown-markdaun-so-vsemi-samymi-populyarnymi-tegami/" target="blank">форматирование Markdown</a>.',
                'buttons' => ['upload' => 'fas fa-image'],
            ],
            'published' => [
                'type' => 'checkbox',
                'label' => 'опубликовать заметку',
            ],
            'deleted' => [
                'type' => 'checkbox',
                'label' => 'удалить заметку',
            ],
        ],
    ],

    'file' => [
        'edit_title' => 'Редактирование файла',
        'fields' => [
            'preview' => [
                'type' => 'thumbnail',
            ],
            'name' => [
                'label' => 'Название файла',
                'type' => 'textline',
                'required' => true,
            ],
            'caption' => [
                'label' => 'Подпись для встраивания',
                'type' => 'textarea',
            ],
            'kind' => [
                'label' => 'Тип содержимого',
                'type' => 'select',
                'options' => [
                    'photo' => 'фотография',
                    'video' => 'видео',
                    'audio' => 'звук',
                    'other' => 'другое',
                ],
            ],
            'mime_type' => [
                'label' => 'Тип MIME',
                'type' => 'textline',
                'required' => true,
            ],
            'files' => [
                'label' => 'Варианты файла',
                'type' => 'fileparts',
            ],
            'published' => [
                'type' => 'hidden',
            ],
            'deleted' => [
                'type' => 'checkbox',
                'label' => 'удалить файл',
            ],
        ],
    ],

    'user' => [
        'new_title' => 'Добавление пользователя',
        'edit_title' => 'Редактирование профиля пользователя',
        'fields' => [
            'name' => [
                'label' => 'Фамилия, имя',
                'type' => 'textline',
                'required' => true,
                'placeholder' => 'Сусанин Иван',
            ],
            'email' => [
                'label' => 'Email',
                'type' => 'textline',
                'required' => true,
            ],
            'phone' => [
                'label' => 'Номер телефона',
                'type' => 'textline',
            ],
            'role' => [
                'label' => 'Роль в работе сайта',
                'type' => 'select',
                'options' => [
                    'nobody' => 'никто',
                    'user' => 'пользователь',
                    'editor' => 'редактор',
                    'admin' => 'администратор',
                ],
            ],
            'published' => [
                'label' => 'разрешить доступ',
                'type' => 'checkbox',
            ],
        ],
    ],
];
