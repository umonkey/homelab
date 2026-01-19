<?php

/**
 * Install routes.
 **/

declare(strict_types=1);

$app->installAccount($app);
$app->installAdmin($app);
$app->installFiles($app);
$app->installNode($app);
$app->installRewrite($app);
$app->installSearch($app);
$app->installSitemap($app);
$app->installTaskQ($app);
$app->installWiki($app);

$app->get('/', 'App\Controllers\HomeController:index');
$app->post('/admin/upload', 'Ufw1\Handlers\Wiki:onUpload');
$app->get('/node/{id:[0-9]+}/kdpv', 'App\Controllers\NodePictureController:index');
$app->get('/node/{id:[0-9]+}/data.json', 'App\Controllers\NodeDataController:index');
