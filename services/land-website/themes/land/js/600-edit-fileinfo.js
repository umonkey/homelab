/**
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
