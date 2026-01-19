#!/usr/bin/env php
<?php

require __DIR__ . '/../src/bootstrap.php';

$c = $app->getContainer();
$nodes = $c->get('node')->where('deleted = 0');
foreach ($nodes as $node) {
    $body = json_encode($node, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents("tmp/node-{$node['id']}.json", $body);
}
