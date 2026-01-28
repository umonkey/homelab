<?php

return [
    ['@^/admin/log$@', '*', 'App_Handlers_Log'],
    ['@^/admin/upload$@', 'POST', 'App_Admin_Upload'],
    ['@^/admin/?$@', 'GET', 'App_Admin_List'],
    ['@^/admin/([a-z]+)$@', '*', 'App_Admin_Docs'],
    ['@^/admin/([a-z]+)/(\d+)/edit$@', '*', 'App_Admin_Edit'],
    ['@^/admin/([a-z]+)/add$@', '*', 'App_Admin_Create'],
    ['@^/eval$@', '*', 'App_Handlers_Eval'],

    ['@^/login$@', '*', 'App_Login_Show'],

    ['@^/([a-z]+)s/(\d+)/edit$@', 'GET', 'App_Admin_EditRedir'],

    ['@^/admin/gallery/preview$@', 'GET', 'App_Gallery_Preview'],

    // Главная страница.
    ['@^/$@', 'GET', 'App_Home'],

    // Управление выставками.
    ['@^/exhibitions$@', 'GET', 'App_Exhibitions'],
    ['@^/(exhibition)s/(\d+)$@', 'GET', 'App_Show'],

    // Управление экспонатами.
    ['@^/(object)s$@', 'GET', 'App_Show'],
    ['@^/(object)s/(\d+)$@', 'GET', 'App_Show'],

    // Управление экскурсиями.
    ['@^/(excursion)s$@', 'GET', 'App_Show'],
    ['@^/(excursion)s/(\d+)$@', 'GET', 'App_Show'],

    // Статьи.
    ['@^/(article)s$@', 'GET', 'App_Show'],
    ['@^/(article)s/(\d+)$@', 'GET', 'App_Show'],

    // События.
    ['@^/events$@', 'GET', 'App_Events'],
    ['@^/events/(\d+)$@', 'GET', 'App_Event'],

    // Дневник.
    ['@^/(blog)$@', 'GET', 'App_Show'],
    ['@^/(blog)/(\d+)$@', 'GET', 'App_Show'],

    // Архив документов.
    ['@^/(archive)$@', 'GET', 'App_Show'],
    ['@^/(archive)/(\d+)$@', 'GET', 'App_Show'],

    // Места.
    ['@^/places$@', 'GET', 'App_Map'],
    ['@^/(place)s/(\d+)$@', 'GET', 'App_Show'],

    // Фотографии.
    ['@^/photo/submit$@', '*', 'App_Photo_Submit'],
    ['@^/photo/submit/(\d+)/step(\d)$@', '*', 'App_Photo_Submit'],
    ['@^/photo/(\d+)$@', '*', 'App_Photo_Show'],
    ['@^/photo$@', '*', 'App_Photo_List'],
    ['@^/admin/photo/(\d+)/share$@', '*', 'App_Photo_Share'],
    ['@^/admin/photo/(\d+)/delete$@', 'POST', 'App_Photo_Delete'],
    ['@^/admin/photo/(\d+)/(old|new)/edit$@', '*', 'App_Photo_Edit'],

    // Уменьшенные файлы для админки.
    ['@^/files/(\d+)/(small\.jpg)@', 'GET', 'App_Files_Thumbnail'],
    ['@^/files/remote/([0-9a-f]+)\.jpg@', 'GET', 'App_Files_Remote'],

    ['@^/mail$@', '*', 'App_Mail'],

    ['@^/captcha\.png$@', 'GET', 'App_Captcha_Show'],
    ['@^/info\.php$@', 'GET', 'App_Info'],

    ['@^/sitemap\.xml$@', 'GET', 'App_Sitemap'],
    ['@^/(static/.+)$@', 'GET', 'Framework_StaticFileHandler'],
];
