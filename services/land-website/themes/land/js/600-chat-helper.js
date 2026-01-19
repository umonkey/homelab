/* global VK */

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
