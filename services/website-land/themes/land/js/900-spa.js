window.spa_link_filter = function (link) {
    if (link.closest('header .bars').length) {
        return false;
    }

    return true;
};
