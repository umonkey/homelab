/**
 * Asynchronous forms with upload progress.
 *
 * Supports: form.async_files, form input.autosubmit, a.async,
 * form.async
 **/
jQuery(function ($) {
    var myalert = function (msg) {
        var msgbox = $("#msgbox");
        if (msgbox.length == 1) {
            msgbox.html(msg);
            msgbox.show();
        } else {
            alert(msg);
        }
    };

    window.handle_json = function (res) {
        res = $.extend({
            message: null,
            confirm: null,
            next: null,
            redirect: null,
            tab_open: null,
            refresh: false,
            trigger: null,
            add_class: null,
            reset: false,
            call: null,
            call_args: {}
        }, res);

        var blur = true;

        if (res.add_class)
            $("body").addClass(res.add_class);

        if (res.message)
            myalert(res.message);

        if (res.trigger)
            $(document).trigger(res.trigger);

        // WTF, there has to be a better way.
        if (res.reset)
            $(".reset").val("");

        if (res.confirm && res.next) {
            if (!confirm(res.confirm)) {
                $("body").removeClass("wait");
                return;
            }

            $.ajax({
                url: res.next,
                type: "GET",
                dataType: "json"
            }).done(function (res) {
                handle_json(res);
            }).fail(handle_ajax_failure);

            return;
        }

        else if (res.refresh) {
            window.location.reload();
            blur = false;
        } else if (res.redirect) {
            window.location.href = res.redirect;
            blur = false;
        } else if (res.tab_open) {
            window.open(res.tab_open, "_blank");
        }

        if (blur)
            $("body").removeClass("wait");

        if (res.call && res.call_args)
            window[res.call](res.call_args);
        else if (res.call)
            window[res.call]();
    };

    window.handle_ajax_failure = function (xhr, status, message) {
        if (xhr.status == 404)
            myalert("Form handler not found.");
        else if (message == "Debug Output")
            myalert(xhr.responseText);
        else if (status == "error" && message == "")
            ;  // aborted, e.g. F5 pressed
        else if (xhr.responseText)
            myalert("Request failed." + xhr.responseText);
        else
            myalert("Request failed: " + message + "\n\n" + xhr.responseText);

        $("body").removeClass("wait");
    };

    $(document).on("submit", "form.async", function (e) {
        var wait = $(this).hasClass("wait");
        if (wait)
            $("body").addClass("wait");

        $("#msgbox").hide();

        if (window.FormData === undefined) {
            // myalert("File uploads not supported by your browser.");
        } else {
            var fd = new FormData($(this)[0]);
            fd.append("_random", Math.random());

            var show_progress = function (percent, loaded, total) {
                if (total >= 102400) {
                    var mbs = function (bytes) { return Math.round(bytes / 1048576 * 100) / 100; };
                    var label = mbs(loaded) + " MB / " + mbs(total) + " MB";
                    $(".progressbar .label").html(label);

                    $(".progressbar .done").css("width", parseInt(percent) + "%");
                    $(".progressbar").show();
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
            }).done(function (res) {
                handle_json(res);
            }).always(function () {
                $(".progressbar").hide();
            }).fail(handle_ajax_failure);

            e.preventDefault();
        }
    });

    $(document).on("change", "form input.autosubmit", function (e) {
        $(this).closest("form").submit();
    });

    $(document).on("click", "a.async", function (e) {
        e.preventDefault();
        $(this).blur();

        var wait = $(this).closest(".wait").length > 0;
        if (wait)
            $("body").addClass("wait");

        $.ajax({
            url: $(this).attr("href"),
            dataType: "json",
            type: $(this).is(".post") ? "POST" : "GET"
        }).done(function (res) {
            handle_json(res);
        }).fail(handle_ajax_failure);
    });
});
