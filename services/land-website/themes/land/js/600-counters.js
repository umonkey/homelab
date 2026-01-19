/**
 * Принудительное обновление счётчика при внутренней навигации.
 *
 * Инструкция здесь: https://yandex.ru/support/metrica/code/ajax-flash.html
 **/
jQuery(function ($) {
    $(document).on('ufw:reload', function () {
        if (typeof ym !== 'undefined') {
            ym(14608519, 'hit', window.location.href);
        }
    });
});
