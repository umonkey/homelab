#!/usr/bin/env python
<?php

/**
 * Обновление старых ссылок внутри вики страниц.
 *
 * Проверяет все вики страницы на наличие внутренних ссылок, которые есть в таблице nodes_wiki_idx (поле url).
 * Если находит -- заменяет.
 **/

$app = require __DIR__ . '/../config/bootstrap.php';

$container = $app->getContainer();
$db = $container->db;
$nodeRepo = $container->node;
$wiki = $container->wiki;

$db->beginTransaction();

$nodes = $nodeRepo->where('type = ? AND deleted = 0', ['wiki']);
foreach ($nodes as $node) {
    if (empty($node['source'])) {
        debug($node);
        continue;
    }

    if (empty($node['title'])) {
        if (preg_match('@^# (.+)$@m', $node['source'], $m)) {
            $node['title'] = $m[1];
        }
    }

    if (!empty($page['date'])) {
        $node['created'] = $page['date'];
    }

    $nodeRepo->save($node);
}

$db->commit();
