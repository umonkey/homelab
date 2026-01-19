jQuery(function ($) {
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
