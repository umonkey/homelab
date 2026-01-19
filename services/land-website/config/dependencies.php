<?php

/**
 * Dependency container setup.
 **/

declare(strict_types=1);

use App\Util;
use Psr\Container\ContainerInterface;

$container = $app->getContainer();

$container['kdpv'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['kdpv'];
    return new App\Services\NodePictureService($settings);
};
