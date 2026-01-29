/* vendor/umonkey/ufw1/assets/admin.js */ jQuery(function ($) {
    $(document).on('change', 'input.published', function (e) {
        var tr = $(this).closest('tr'),
            checked = $(this).is(':checked');

        if (checked) {
            tr.removeClass('unpublished').addClass('published');
        } else {
            tr.removeClass('published').addClass('unpublished');
        }

        $(this).blur();

        $.ajax({
            url: '/admin/nodes/publish',
            data: {id: $(this).attr('value'), published: checked ? 1 : 0},
            type: 'POST',
            dataType: 'json'
        }).done(handle_ajax);
    });

    $(document).on('change', 'table input[type=checkbox].deleted', function (e) {
        var value = $(this).is(':checked') ? 1 : 0,
            id = $(this).attr('value');

        $.ajax({
            url: '/admin/nodes/delete',
            data: {id: id, deleted: value},
            type: 'POST',
            dataType: 'json'
        }).done(handle_ajax);
    });
});


/* vendor/umonkey/ufw1/assets/async.js */ jQuery(function ($) {
    window.handle_ajax = function (res) {
        res = $.extend({
            code: null,
            message: null,
            redirect: null,
            refresh: null,
            callback: null,
            callback_args: null
        }, res);

        if (res.refresh) {
            window.location.reload();
        }

        if (res.redirect) {
            window.location.href = res.redirect;
            return;
        }

        if (res.message) {
            var m = $(".msgbox");
            m.text(res.message);
            m.show();
        }

        if (res.callback) {
            if (res.callback in window) {
                window[res.callback](res.callback_args);
            } else {
                console.log("callback handler not found", res.callback);
            }
        }
    };

    $(document).on("submit", "form.async", function (e) {
        e.preventDefault();
        var form = $(this);

        if (window.FormData === undefined) {
            alert("This function does not work in your old browser.");
            return;
        }

        $("body").addClass("wait");

        var buttons = form.find("buttons");
        var msgbox = form.find(".msgbox");
        var pgbar = form.find(".progressbar");

        if (pgbar.length == 0) {
            form.append("<div class='progressbar' style='display: none'><div class='label'></div><div class='done'></div></div>");
            pgbar = form.find(".progressbar");
        }

        buttons.prop("disabled", true);

        msgbox.hide();

        var fd = new FormData($(this)[0]);

        var show_progress = function (percent, loaded, total) {
            if ("console" in window) console.log("upload progress: " + percent + "%");

            if (total >= 100000) {
                var mbs = function (bytes) { return (Math.round(bytes / 1048576 * 100) / 100).toFixed(2); };

                var label = mbs(loaded) + " MB / " + mbs(total) + " MB";
                pgbar.find(".label").html(label);

                pgbar.find(".done").css("width", parseInt(percent) + "%");

                pgbar.show();
            }
        };

        var show_message = function (msg) {
            if (msgbox.length > 0) {
                msgbox.text(msg);
                msgbox.show();
            } else {
                alert(msg);
            }
        };

        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            cache: false,
            dataType: "json",
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                xhr.upload.onprogress = function (e) {
                    var pc = Math.round(e.loaded / e.total * 100);
                    show_progress(pc, e.loaded, e.total);
                };
                return xhr;
            }
        }).done(handle_ajax)
        .always(function () {
            $("body").removeClass("wait");
            pgbar.hide();
            buttons.prop("disabled", false);
        }).fail(function (xhr, status, message) {
            if (xhr.status == 404)
                show_message("Form handler not found.");
            else if (message == "Debug Output")
                show_message(xhr.responseText);
            else if (status == "error" && message == "")
                ;  // aborted, e.g. F5 pressed.
            else if (xhr.responseText)
                show_message("Request failed: " + xhr.responseText);
            else
                show_message(sfmt("Request failed: {0}\n\n{1}", message, xhr.responseText));
        });
    });

    $(document).on("click", "a.async", function (e) {
        e.preventDefault();

        var props = {
            'url': $(this).attr('href'),
            'type': $(this).hasClass('post') ? 'POST' : 'GET',
            'data': {
                'next': $(this).data('next'),
            }
        };

        var cnf = $(this).data('confirm');
        if (cnf != undefined && !confirm(cnf))
            return;

        $.ajax(props).done(handle_ajax);
    });

    $(document).on("change", ".autosubmit", function (e) {
        $(this).closest("form").submit();
    });
});


/* vendor/umonkey/ufw1/assets/dialogs.js */ window.dialogs = {};

window.dialogs.close = function () {
    $('.dialog, #block').hide();
};

jQuery(function ($) {
    $(document).on('click', '.dialog .btn.cancel', function (e) {
        e.preventDefault();
        dialogs.close();
    });
});


/* vendor/umonkey/ufw1/assets/file-fields.js */ jQuery(function ($) {
    $(document).on('click', 'a.thumbnail.pick', function (e) {
        e.preventDefault();
        $(this).blur();

        var _this = $(this);

        ufw_filepicker(function (res) {
            var html = sfmt('<a class="thumbnail" href="/node/{0}/download/large" data-fancybox="gallery">', res.id);
            html += sfmt('<input type="hidden" name="node[files][]" value="{0}"/>', res.id);
            html += sfmt('<img src="{0}" alt="" />', res.image);
            html += '<span class="file-delete"><i class="fas fa-trash-alt"></i></span>';
            html += '</a>';

            _this.before(html);
        });
    });

    $(document).on('click', 'a.thumbnail .file-delete', function (e) {
        $(this).closest('a.thumbnail').remove();
    });
});


/* vendor/umonkey/ufw1/assets/filepicker.js */ /**
 * Call this to display the file picker dialog.
 **/
window.ufw_filepicker = function (callback) {
    window.ufw_filepicker_callback = callback;

    var dlg = $('#node-upload');
    if (dlg.length == 0) {
        var html = '<form id="node-upload" class="async filepicker dialog" action="/admin/upload" method="post" style="display: none">';

        html += '<div class="form-group">';
        html += '<label>Выбери недавний файл</label>';
        html += '<div class="recent"></div>';
        html += '</div>';

        html += '<div class="form-group">';
        html += '<label>Или загрузи новый</label>';
        html += '<input class="form-control autosubmit" type="file" name="files[]" accept="image/*" multiple="multiple"/>';
        html += '</div>';

        html += '<div class="form-group">';
        html += '<label>Или вставь ссылку на файл</label>';
        html += '<input class="form-control wide" type="text" name="link" placeholder="https://..."/>';
        html += '</div>';

        html += '<div class="form-actions">';
        html += '<button class="btn btn-primary" type="submit">Загрузить</button>';
        html += '<button class="btn btn-default cancel" type="button">Отмена</button>';
        html += '</div>';

        html += '<div class="msgbox"></div>';

        $('body').append(html);
        dlg = $('#node-upload');
    }

    if ($("#block").length == 0)
        $("body").append("<div id='block'></div>");

    dlg.find('.recent').html('');
    dlg.find('.msgbox').hide();
    dlg[0].reset();

    $('#node-upload, #block').show();

    $.ajax({
        url: "/files/recent.json",
        type: "GET",
        dataType: "json"
    }).done(function (res) {
        if ('files' in res) {
            var items = res.files.map(function (f) {
                return sfmt("<a data-id='{0}' data-thumbnail='{2}' href='{3}' title='{1}' target='_blank'><img src='{2}'/></a>", f.id, f.name_html, f.thumbnail, f.link);
            });

            $("#node-upload .recent").html(items.join(""));
        } else {
            handle_ajax(res);
        }
    });
};

jQuery(function ($) {
    $(document).on('click', 'form.filepicker .recent a', function (e) {
        e.preventDefault();
        $('#block, form.filepicker').hide();

        ufw_filepicker_callback([{
            'id': $(this).data('id'),
            'image': $(this).data('thumbnail')
        }]);
    });
});


/* vendor/umonkey/ufw1/assets/photoalbum.js */ /**
 * Photoalbum animation.
 * Shows the more-less buttons.
 **/
jQuery(function ($) {
    var init = function () {
        $(".photoalbum").each(function () {
            var width = 0,
                max_width = $(this).width() - 35;

            $(this).find("a.image").each(function () {
                width += $(this).width() + 5;
                if (width >= max_width)
                    $(this).addClass("overflow");
            });

            var of = $(this).find("a.image.overflow");
            if (of.length > 0) {
                $(this).append("<div class='icon showmore'><i class='fas fa-chevron-circle-right'></i></div>");
                $(this).append("<div class='icon showless'><i class='fas fa-chevron-circle-left'></i></div>");
            }

            // show everything, without flickering
            $(this).fadeTo(100, 1.0);
        });
    };

    init();
    $(document).on('ufw:reload', init);

    $(document).on("click", ".photoalbum .showmore", function (e) {
        var album = $(this).closest(".photoalbum");
        album.toggleClass("open");
    });

    $(document).on("click", ".photoalbum .showless", function (e) {
        var album = $(this).closest(".photoalbum");
        album.toggleClass("open");
    });
});


/* vendor/umonkey/ufw1/assets/sitemap.js */ jQuery(function ($) {
    $(document).on("click", "#show_sitemap", function (e) {
        var c = $("#sitemap");
        if (c.length == 1) {
            e.preventDefault();
            c.toggle();
        }
    });

    $(document).on("click", ".toggle", function (e) {
        var sel = $(this).attr("data-toggle"),
            em = $(sel);
        if (em.length == 1) {
            e.preventDefault();
            $(this).blur();

            if (em.is(":visible")) {
                em.hide("fade", 100);
            } else {
                $(".toggled").hide("fade", 100);
                em.show("fade", 100);
            }

            var inp = em.find("input:first");
            if (inp.length > 0)
                inp.focus();
        }
    });

    // Close popups on click outside of them.
    $(document).on("click", function (e) {
        if ($(e.target).closest(".toggled").length == 0) {
            $(".toggled:visible").hide("fade", 100);
        }
    });
});


/* vendor/umonkey/ufw1/assets/spa.js */ /**
 * Quicker page load.
 *
 * Instead of reloading whole pages, loads them using XHR and replaces current
 * page contents.  Maintains navigation history.  Works only for local pages,
 * has a blacklist.
 *
 * After loading and displaying the new page, fires custom "ufw:reload" event,
 * so that initialization scripts could re-run, initialize controls etc.
 *
 * Only updates contents of the .spa-container block, be it body or something
 * smaller.  If no such block exists -- does nothing.
 *
 * TODO: Save current scroll position on navigating BACK.  Example: (1) open the blog
 * index, scroll to the middle, open a blog entry.  It scrolls to the top of the page,
 * which is good.  Now (2) navigate back.  It opens the blog index and scrolls to the
 * middle, where you left, which is great.  Now (3) navigate forward!  It scrolls to
 * the top again, ignoring the position where you really left that page before.
 *
 * Needs to call savePosition(), but doint that in the popstate event handler seems
 * to be wrong, ruins all navigation.
 *
 * TODO: maintain window title, currently not done at all.
 **/

/* global spa_link_filter */
/* eslint camelcase: 0 */

jQuery(function ($) {
    const SELECTOR = '.spa-container';

    var container = $(SELECTOR);

    if (container.length === 0) {
        console && console.log('spa: .spa-container not found, not installing.');
        return;
    }

    if (container.is('body') || container.closest('body').length === 0) {
        console && console.log('spa: .spa-container MUST be inside <body>, won\'t work in current setup.');
        return;
    }

    console && console.log('spa: ready.');

    /**
     * Log a message to the console.
     **/
    var log = function (message) {
        console && console.log('ufw/spa.js: ' + message);
    };

    /**
     * Scroll to the anchor, specified in the link.
     **/
    var scrollTo = function (link, scrollTop) {
        var parts = link.split('#', 2);

        if (parts.length === 1) {
            log('scrolling to ' + scrollTop);
            window.scroll({ top: scrollTop });
        } else {
            var l = $('#' + parts[1]);

            if (l.length === 1) {
                var y = l.offset().top;
                log('scrolling to ' + y);
                window.scroll({ top: y });
            } else {
                log('cannot scroll to', link);
            }
        }
    };

    /**
     * Save current position in history.
     **/
    var savePosition = function () {
        window.history.replaceState({
            link: window.location.href,
            scrollTop: $(window).scrollTop()
        }, '', window.location.href);
        log('scroll position saved: top=' + $(window).scrollTop() + ', link=' + window.location.href);
    };

    /**
     * Update page title with the one from HTML.
     **/
    var update_title = function (html) {
        var m = html.match(/<title>(.+?)<\/title>/);
        if (m !== null) {
            window.document.title = m[1];
        } else {
            log('window title not found.');
        }
    };

    /**
     * Open the specified link.
     *
     * 1) Reloads the page if necessary (unless in-page navigation).
     * 2) Scrolls to the specified position (if an anchor is used).
     * 3) Calls the callback function.
     *
     * Does NO control on what the link is -- that belongs to the calling side,
     * namely the click event handler.  This assumes that the link IS good to work with.
     *
     * TODO: failure handling, e.g., page not found.
     *
     * @param {string} link      - Page to load.
     * @param {int}    scrollTop - Position to scroll to.  Only defined if we're navigating the history, zero for new pages.
     * @param {func}   callback  - Function to call afterwards.
     **/
    var openURL = function (link, scrollTop, callback) {
        // Start spinning.
        var bars = $('header .bars i');
        var cls = bars.attr('class');
        bars.attr('class', 'fas fa-spinner fa-spin');

        log('openURL: top=' + scrollTop + ', link=' + link);

        // Local anchor.
        if (link[0] === '#') {
            scrollTo(link, scrollTop);

            if (callback) {
                callback();
            }

            return;
        }

        // Load contents.
        $.ajax({
            url: link,
            dataType: 'html',
            type: 'GET'
        }).done(function (res) {
            var d = $('<div>').append($.parseHTML(res)).find(SELECTOR);

            if (d.length === 0) {
                log('no contents, falling back to page reload');
                window.location.href = link;
            } else {
                update_title(res);

                $(SELECTOR).replaceWith(d);
                $(document).trigger('ufw:reload');

                scrollTo(link, scrollTop);

                if (callback) {
                    callback();
                }
            }
        }).always(function () {
            bars.attr('class', cls);
        }).fail(function () {
            log('error loading page.');
            alert('Error loading page.');

            if (callback) {
                callback();
            }
        });
    };

    /**
     * Handle link clicks.
     *
     * Ignores external links.
     * Ignores some special links, like photos in an album -- they pop up.
     **/
    $(document).on('click', 'a', function (e) {
        if (e.ctrlKey || e.shiftKey) {
            return;
        }

        // First of all, save current scroll position.
        //
        // This is needed even if we don't load the new page.  For example,
        // if a fancybox link is clicked -- there's a pop-up, which updates
        // the location.hash, and closes on navigating back.  Upon that navigation
        // we need to maintain the scroll position.  We sure don't want to
        // scroll to top.
        savePosition();

        var link = $(this).attr('href');

        // External link, pass.
        if (link.indexOf('//') >= 0) {
            return;
        }

        // New tab, pass.
        if ($(this).attr('target')) {
            return;
        }

        // No reload target, malformed page.
        if ($(SELECTOR).length === 0) {
            return;
        }

        // Fancybox pop-up, no need to load.
        if ($(this).is('[data-src]')) {
            return;
        }

        // White list pattern.
        if (typeof spa_link_filter === 'function') {
            if (!spa_link_filter($(this))) {
                log('link blacklisted by spa_link_filter');
                return;
            }
        }

        openURL(link, 0, function () {
            window.history.pushState({
                link: link,
                scrollTop: $(window).scrollTop()
            }, '', link);
        });

        e.preventDefault();
    });

    /**
     * Handle history navigation.
     *
     * This is fired on both forward and backwards navigation.
     *
     * @param {Object} e -- the NEW history item.
     **/
    window.addEventListener('popstate', function (e) {
        // savePosition();

        var state = $.extend({
            link: null,
            scrollTop: 0
        }, e.state ? e.state : {});

        log('popstate event, scrollTop=' + state.scrollTop + ', link=' + state.link);

        if (state.link !== null) {
            openURL(state.link, state.scrollTop);
        } else {
            openURL(document.location.href);
        }
    });

    /**
     * Tell some clever browsers that we don't need them maintaining the scroll position.
     * https://stackoverflow.com/q/10742422/371526
     **/
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
});


/* vendor/umonkey/ufw1/assets/util.js */ if (!("onerror" in window)) {
    window.onerror = function (msg, url, line, col, error) {
      alert("Error: " + msg + "\nLine: " + line + ", col: " + col + "\nurl: " + url);
    };
}


function sfmt(format) {
    var args = Array.prototype.slice.call(arguments, 1);
    return format.replace(/{(\d+)}/g, function (match, number) {
        return typeof args[number] == "undefined" ? match : args[number];
    });
}


// Parse query string, put to location.queryString.
(function () {
    location.queryString = {};
    location.search.substr(1).split("&").forEach(function (pair) {
        if (pair === "") return;
        var parts = pair.split("=");
        location.queryString[parts[0]] = parts[1] &&
            decodeURIComponent(parts[1].replace(/\+/g, " "));
    });
})();


/* vendor/umonkey/ufw1/assets/wiki-editor.js */ jQuery(function ($) {
    window.reload_files = function () {
        $.ajax({
            url: '/wiki/recent-files.json',
            type: 'GET',
            dataType: 'json'
        }).done(function (res) {
            if ('files' in res) {
                var items = res.files.map(function (f) {
                    return sfmt("<a data-id='{0}' href='/wiki?name=File:{0}' title='{1}' target='_blank'><img src='/node/{0}/download/small'/></a>", f.id, f.name_html);
                });

                var html = items.join('');

                $('.wiki_edit .tiles .wrap').html(items.join(''));
            } else {
                handle_ajax(res);
            }
        });
    };

    $(document).on('click', '.wiki_edit .toolbar button', function (e) {
        var name, form;

        $(this).blur();

        name = $(this).attr('name');
        form = $('form.wikisource');

        if (name == 'save') {
            form.submit();
            e.preventDefault();
        }

        else if (name == 'cancel') {
            e.preventDefault();
            window.location.href = $('.wikiedit').data('back');
        }

        else if (name == 'toc') {
            e.preventDefault();
            wiki_insert_text("<div id=\"toc\"></div>");
        }

        else if (name == 'upload') {
            $('.wiki_edit .files').toggle();
            $(this).toggleClass('active');

            if ($('.wiki_edit .files input[name=query]').is(':visible')) {
                $('.wiki_edit .files input[name=query]').focus();
                reload_files();
            }
        }

        else if ($(this).data('link')) {
            e.preventDefault();
            window.open($(this).data('link'), '_blank');
        }
    });

    $(document).on('click', '.wiki_edit .tiles a[data-id]', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        wiki_insert_text("[[image:" + id + "]]\n");

        var ta = $('textarea.wiki')[0];
        ta.selectionStart = ta.selectionEnd;
    });

    $(document).on('change', '.wiki_edit input#uploadctl', function (e) {
        $(this).closest('form').submit();
    });
});


/* vendor/umonkey/ufw1/assets/wiki-toolbar.js */ jQuery(function ($) {
    $(document).on("click", "a.tool", function (e) {
        var dsel = $(this).attr("data-dialog");
        if (dsel) {
            $(dsel).dialog({
                autoOpen: true,
                modal: true,
                open: function () {
                    if ($(this).is("form"))
                        $(this)[0].reset();  // clean up the fields
                    $(this).find(".msgbox").hide();
                }
            });
            e.preventDefault();
        }

        var action = $(this).attr("data-action");
        if (action == "map") {
            $("#dlgMap").show();
            e.preventDefault();
        }
    });

    $(document).on("change", "#filePhoto", function (e) {
        $(this).closest("form").submit();
    });
});


/* vendor/umonkey/ufw1/assets/wiki.js */ window.wiki_insert_text = function (text) {
    var ta = $("textarea")[0],
        tv = ta.value,
        ss = ta.selectionStart,
        se = ta.selectionEnd,
        tt = tv.substring(ss, se);

    var ntext = tv.substring(0, ss) + text + tv.substring(se);
    ta.value = ntext;
    ta.selectionStart = ss; // ss + text.length;
    ta.selectionEnd = ss + text.length;
    ta.focus();
};


jQuery(function ($) {
    var init = function () {
        $('.formatted a.external').each(function (em) {
            $(this).attr('target', '_blank');
        });
    };

    init();
    $(document).on('ufw:reload', init);
});


/**
 * Edit page sections.
 **/
jQuery(function ($) {
    var update = function () {
        var link = $("link[rel=edit]:first");
        if (link.length == 0) {
            return;
        }

        var base = link.attr("href");

        $(".formatted h1, .formatted h2, .formatted h3, .formatted h4, .formatted h5").each(function () {
            var text = $(this).text();
            var link = base + "&section=" + encodeURI(text);
            $(this).append("<span class='wiki-section-edit'> [ <a href='" + link + "'>редактировать</a> ]</span>");
        });
    };

    update();
    $(document).on('ufw:reload', update);
});


window.editor_insert = function (text)
{
    var ta = $("textarea")[0];

    var v = ta.value,
        s = ta.selectionStart,
        e = ta.selectionEnd;

    var ntext = v.substring(0, s) + text + v.substring(e);
    ta.value = ntext;
    ta.selectionStart = e + text.length;
    ta.selectionEnd = e + text.length;

    $("#block, .dialog").hide();
    $("textarea").focus();
}


/* themes/land/js/100-hotkeys.js */ jQuery(function ($) {
    $(document).on('keydown', function (e) {
        if (e.ctrlKey && e.keyCode === 69) { // 'e'
            var ctl;

            ctl = $('#admin_bar a.edit');
            if (ctl.length > 0) {
                window.location.href = ctl.attr('href');
                return;
            }

            ctl = $('link[rel=edit]');
            if (ctl.length === 1) {
                window.location.href = ctl.attr('href');
                return;
            }
        }

        if (e.ctrlKey && e.key === 'D') { // 'd'
            e.preventDefault();
            var link = window.location.href;
            if (window.location.search) {
                link += '&debug=tpl';
            } else {
                link += '?debug=tpl';
            }
            window.location.href = link;
        }
    });

    $(document).on('keydown', 'form', function (e) {
        if (e.ctrlKey && e.keyCode === 13) {
            var btn = $(this).find('.toolbar .btn:visible');
            if (btn.length == 0) {
                btn = $(this).find('.btn:visible');
            }
            if (btn.length > 0) {
                btn.eq(0).click();
            }
        }
    });

    $(document).on('keydown', 'textarea.markdown', function (e) {
        var v, s, end, src;

        // External markdown links.
        if (e.altKey && (e.key === '[' || e.key === 'х')) {
            v = this.value;
            s = this.selectionStart;
            end = this.selectionEnd;
            var text = v.substring(0, s) + '[' + v.substring(s, end) + ']()' + v.substring(end);
            this.value = text;
            this.selectionStart = end + 3;
            this.selectionEnd = end + 3;
        }

        // Make itemized list from selected lines
        if (e.altKey && e.key === '-') {
            v = this.value;
            s = this.selectionStart;
            end = this.selectionEnd;

            src = v.substring(s, end);

            var lines = src.match(/[^\r\n]+/g);
            for (var i in lines) {
                var line = lines[i];
                line = '- ' + line.replace(/^\s+|\s+$/, '');
                while (line.substring(0, 4) === '- - ') {
                    line = line.substring(2);
                }
                lines[i] = line;
            }

            lines = lines.join('\n') + '\n';
            var dst = v.substring(0, s) + lines + v.substring(end);

            this.value = dst;
            this.selectionStart = s + lines.length;
            this.selectionEnd = s + lines.length;
        }
    });

    $(document).on('keydown', 'textarea.wiki', function (e) {
        var v, s, end, x, k;

        // Make wiki link from selection.
        if (e.altKey && (e.key === ']' || e.key === 'ъ' || e.key === 'Ъ')) {
            // TODO: load from outside.
            var fixmap = {
                'нацпарк': 'Себежский национальный парк',
                'нацпарка': 'Себежский национальный парк',
                'национального парка': 'Себежский национальный парк'
            };

            v = this.value;
            s = this.selectionStart;
            end = this.selectionEnd;
            x = v.substring(s, end);

            // Autocorrect things.
            var _x = x.toLowerCase();
            for (k in fixmap) {
                if (k === _x) {
                    x = fixmap[k] + '|' + x;
                    break;
                }
            }

            // Отдельный случай для годов.
            x = x.replace(/^(\d{4}) год(|а|у|ом)$/, '$1 год|' + x);
            x = x.replace(/^(\d{4})$/, '$1 год|$1');

            // Добавляем текст с заглавной буквы.
            // [[коза]] => [[Коза|коза]]
            if (x.indexOf('|') < 0) {
                var title = x[0].toUpperCase() + x.substr(1);
                if (title !== x) {
                    x = title + '|' + x;
                }
            }

            var text = v.substring(0, s) + '[[' + x + ']]' + v.substring(end);
            this.value = text;

            if (x.indexOf('|') < 0) {
                this.selectionStart = s + 2;
                this.selectionEnd = s + x.length + 2;
            } else {
                this.selectionStart = s + 2;
                this.selectionEnd = s + 2 + x.indexOf('|');
            }
        }

        if (e.altKey && (e.key === '.' || e.key === 'ю')) {
            v = this.value;
            s = this.selectionStart;
            end = this.selectionEnd;

            var src = v.substring(s, end);
            var dst = v.substring(0, s) + '«' + src + '»' + v.substring(end);

            this.value = dst;
            this.selectionStart = s + src.length + 2;
            this.selectionEnd = s + src.length + 2;
        }
    });

    $(document).on('keydown', function (e) {
        if (e.keyCode === 191) {
            var a = $(document.activeElement);
            if (!a.is('input.search') && !a.is('input') && !a.is('textarea')) {
                console.log(e.keyCode);
                e.preventDefault();
                $('input.search:first').focus();
                return false;
            }
        }
    });
});


/* themes/land/js/200-header.js */ /**
 * Header bars reaction.
 **/
jQuery(function ($) {
    $(document).on('click', 'header .bars a', function (e) {
        e.preventDefault();

        $('header .menu').toggle();
    });
});


/* themes/land/js/600-admin.js */ /* global ufw_filepicker, sfmt, editor_insert */

jQuery(function ($) {
    $(document).on('click', '.edit-buttons button.upload', function (e) {
        e.preventDefault();

        var ta = $(this).closest('.form-group').find('textarea');

        ufw_filepicker(function (res) {
            var code = res.map(function (em) {
                console.log(em);
                return sfmt('[[image:{0}]]', em.id);
            });

            var html = code.join('\n');
            editor_insert(html, ta[0]);
        });
    });
});


/* themes/land/js/600-chat-helper.js */ /* global VK */

jQuery(function ($) {
    $(document).on('click', '#vk_community_messages', function (e) {
        e.preventDefault();

        console && console.log('loading openapi.js ...');

        $.getScript('https://vk.com/js/api/openapi.js?160', function () {
            // $('#chat-widget').replaceWith('<div id="vk_community_messages"></div>');

            VK.Widgets.CommunityMessages('vk_community_messages', 69241389, {
                tooltipButtonText: 'Есть вопрос?',
                expanded: '1'
            });
        });
    });
});


/* themes/land/js/600-counters.js */ /**
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


/* themes/land/js/600-disqus.js */ /**
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


/* themes/land/js/600-edit-fileinfo.js */ /**
 * Display file info on edit.
 **/
jQuery(function ($) {
    var show_form = function (f) {
        var form = $('form.fileinfo');
        form.find('.fsrc').attr('src', f.preview);
        form.find('.fid').val(f.id);
        form.find('.ftitle').val(f.title);
        form.find('.fdesc').val(f.description);
        form.find('.fcaption').val(f.caption);
        form.show();
    };

    var show_image = function (id) {
        $.ajax({
            url: '/node/' + id + '/data.json',
            type: 'GET',
            dataType: 'json'
        }).done(function (res) {
            show_form(res.file);
        }).fail(function () {
            console.log('error loading file ' + id + ' info.');
            $('form.fileinfo').hide();
        });
    };

    var detect_image = function () {
        var ctl, text, sel, m;

        ctl = $('textarea.wiki');
        if (ctl.length == 0) {
            return;
        }

        text = ctl.val();
        sel = ctl[0].selectionStart;

        while (sel >= 0) {
            if (text[sel] == ' ') {
                break;
            }

            if (text.substr(sel, 8) == '[[image:') {
                m = parseInt(text.substr(sel + 8, 10));
                show_image(m);
                return;
            }

            sel--;
        }
    };

    $(document).on('mouseup', 'textarea.wiki', detect_image);
});


/* themes/land/js/600-home.js */ jQuery(function ($) {
    $(document).on('click', '.scrolldown', function (e) {
        e.preventDefault();

        $([document.documentElement, document.body]).animate({
            scrollTop: $('main').offset().top
        }, 500);
    });
});


/* themes/land/js/600-maps.js */ /**
 * Map loader.
 *
 * Loads maps specific scripts if there's a map on the page.
 **/

/* global ufw_map */

jQuery(function ($) {
    var maps = $('div.map[data-items]');
    if (maps.length > 0) {
        $('head').append($('<link rel="stylesheet" type="text/css" />').attr('href', '/css/maps.min.css'));

        $.getScript('/js/maps.min.js', function () {
            maps.each(function () {
                ufw_map($(this));
            });
        });
    }
});


/* themes/land/js/600-pictures.js */ 

/* themes/land/js/600-rewrites.js */ /**
 * URL Rewrite editor animation.
 **/
jQuery(function ($) {
    var tmr = null;

    $(document).on('input', '#rewrite_search', function (e) {
        var ctl = $(this);

        if (tmr) {
            clearTimeout(tmr);
            tmr = null;
        }

        tmr = setTimeout(function () {
            tmr = null;

            $.ajax({
                url: '/admin/rewrite',
                data: {query: ctl.val()},
                type: 'GET'
            }).done(function (res) {
                var table = $('<div>').append($.parseHTML(res)).find('#results');
                $('#results').replaceWith(table);
            });
        }, 200);
    });
});


/* themes/land/js/900-file-picker.js */ /**
 * Call this to display the file picker dialog.
 **/

/* global sfmt, ufw_filepicker_callback, handle_ajax */

window.ufw_filepicker = function (callback) {
    window.ufw_filepicker_callback = callback;

    window.ufw_filepicker_callback_caller = function (res) {
        $('#block, form.filepicker').hide();
        ufw_filepicker_callback(res);
    };

    var dlg = $('#node-upload');
    if (dlg.length === 0) {
        var html = '<form id="node-upload" class="async filepicker dialog" action="/admin/upload" method="post" style="display: none">';

        html += '<div class="form-group">';
        html += '<label>Выбери недавний файл</label>';
        html += '<div class="recent"></div>';
        html += '</div>';

        html += '<div class="form-group">';
        html += '<label>Или загрузи новый</label>';
        html += '<input class="form-control autosubmit" type="file" name="files[]" accept="image/*" multiple="multiple"/>';
        html += '</div>';

        html += '<div class="form-group">';
        html += '<label>Или вставь ссылку на файл</label>';
        html += '<input class="form-control wide" type="text" name="link" placeholder="https://..."/>';
        html += '</div>';

        html += '<div class="form-actions">';
        html += '<button class="btn btn-primary" type="submit">Загрузить</button>';
        html += '<button class="btn btn-default cancel" type="button">Отмена</button>';
        html += '</div>';

        html += '<div class="msgbox"></div>';

        $('body').append(html);
        dlg = $('#node-upload');
    }

    if ($('#block').length === 0) {
        $('body').append('<div id="block"></div>');
    }

    dlg.find('.recent').html('');
    dlg.find('.msgbox').hide();
    dlg[0].reset();

    $('#node-upload, #block').show();

    $.ajax({
        url: '/files/recent.json',
        type: 'GET',
        dataType: 'json'
    }).done(function (res) {
        if ('files' in res) {
            var items = res.files.map(function (f) {
                return sfmt("<a data-id='{0}' data-thumbnail='{2}' href='{3}' title='{1}' target='_blank'><img src='{2}'/></a>", f.id, f.name_html, f.thumbnail, f.link);
            });

            $('#node-upload .recent').html(items.join(''));
        } else {
            handle_ajax(res);
        }
    });
};

jQuery(function ($) {
    $(document).on('click', 'form.filepicker .recent a', function (e) {
        e.preventDefault();
        $('#block, form.filepicker').hide();

        ufw_filepicker_callback({
            'id': $(this).data('id'),
            'image': $(this).data('thumbnail')
        });
    });
});


/* themes/land/js/900-spa.js */ window.spa_link_filter = function (link) {
    if (link.closest('header .bars').length) {
        return false;
    }

    return true;
};
