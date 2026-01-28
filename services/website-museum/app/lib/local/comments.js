jQuery(function ($) {
    var dt = $("#disqus_thread");
    if (dt.length) {
        window.disqus_config = function () {
            this.page.url = dt.attr("data-url");
            this.page.identifier = dt.attr("data-id");
        };

        var d = document,
            s = d.createElement('script');

        s.src = 'https://seb-museum.disqus.com/embed.js';
        s.setAttribute('data-timestamp', +new Date());
        (d.head || d.body).appendChild(s);
    }
});
