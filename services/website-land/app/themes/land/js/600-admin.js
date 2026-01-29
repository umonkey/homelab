/* global ufw_filepicker, sfmt, editor_insert */

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
