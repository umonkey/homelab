<?php

ini_set("display_errors", 1);

umask(0007);

$_SERVER['HTTP_HOST'] = 'artwall.pro';

if (version_compare(PHP_VERSION, '5.5.0') < 0) {
    header("HTTP/1.0 503 Service Unavailable");
    header("Content-Type: text/plain; charset=utf-8");
    die("Для работы сайта нужен PHP версии 5.5 или более поздней.");
}

require __DIR__ . '/vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/src/dependencies.php';

// Register middleware
require __DIR__ . '/src/middleware.php';

// Register routes
require __DIR__ . '/src/routes.php';

$container = $app->getContainer();
$nf = $container->get('node');
$s3 = $container->get('S3');
$logger = $container->get('logger');

$db = $container->get('database');
$db->query('SET @@session.wait_timeout = 1200');

$nodes = $nf->where('deleted = 0 AND type = \'file\'');
foreach ($nodes as $node) {
    $logger->debug('cli: updating node {0}', [$node['id']]);
    $tn = $container->get('thumbnailer');
    $node = $tn->updateNode($node);
    $node = $s3->uploadNodeFiles($node);
    $node = $nf->save($node);
}

die("Done.\n");
