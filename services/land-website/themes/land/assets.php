<?php

return [
    'blacklist' => [
        '/vendor/umonkey/ufw1/assets/photoalbum.css',
    ],

    'public/css/app.css' => [
        '/vendor/umonkey/ufw1/assets/*.css',
        'css/*.css',
    ],

    'public/js/app.js' => [
        '/vendor/umonkey/ufw1/assets/*.js',
        'js/*.js',
    ],

    'public/css/libs.css' => [
        'fonts/pt-sans.css',
        'fonts/fontawesome.css',
        'lib/fancybox/jquery.fancybox.min.css',
    ],

    'public/js/libs.js' => [
        'lib/jquery/jquery-3.3.1.min.js',
        'lib/fancybox/jquery.fancybox.min.js',
    ],

    'public/css/maps.css' => [
        'lib/leaflet/leaflet.css',
        'lib/leaflet/Control.FullScreen.css',
        'lib/leaflet/Control.Loading.css',
        'lib/leaflet/MarkerCluster.Default.css',
        'lib/leaflet/MarkerCluster.css',
    ],

    'public/js/maps.js' => [
        'lib/leaflet/leaflet.min.js',
        'lib/leaflet/leaflet.markercluster.min.js',
        'lib/leaflet/Control.FullScreen.js',
        'lib/leaflet/Control.Loading.js',
        'lib/leaflet/ufw_map.js',
    ],
];
