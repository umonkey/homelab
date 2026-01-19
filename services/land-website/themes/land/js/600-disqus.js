/**
 * Загрузка комментариев к записи.
 *
 * По ходу навигации используем DISQUS.reset:
 * https://help.disqus.com/en/articles/1717163-using-disqus-on-ajax-sites
 **/
jQuery(function ($) {
    var w = window;

    var disqus_check = function (callback) {
        var thr = $('#disqus_thread');
        if (thr.length == 1) {
            var did = thr.data('id');
            callback(did);
        } else {
            console && console.log('disqus comments disabled ');
        }
    };

    var disqus_load = function () {
        disqus_check(function (disqus_id) {
            w.disqus_shortname = 'umonkey-land';
            w.disqus_identifier = disqus_id;

            $.getScript('https://umonkey-land.disqus.com/embed.js', function () {
                console && console.log('disqus comments loaded');
            });
        });
    };

    var disqus_reload = function () {
        if (typeof DISQUS === 'undefined') {
            return disqus_load();
        } else {
            disqus_check(function (disqus_id) {
                DISQUS.reset({
                    reload: true,
                    config: function () {
                        this.page.identifier = disqus_id;
                    }
                });
            });
        }
    };

    disqus_load();
    $(document).on('ufw:reload', disqus_reload);
});
