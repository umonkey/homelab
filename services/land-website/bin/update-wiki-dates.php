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

$nodes = $nodeRepo->where('type = ?', ['wiki']);
foreach ($nodes as $node) {
    if (empty($node['source'])) {
        continue;
    }

    $page = $wiki->render($node['source']);
    $section = $page['section'] ?? null;

    $source = $node['source'];
    $source = str_replace("rss: off\n", "", $source);

    if ($section != 'blog') {
        $source = "rss: off\n" . $source;
    }

    if (!empty($page['date'])) {
        $node['created'] = $page['date'];
    }

    $node['source'] = $source;
    $nodeRepo->save($node);
}

$db->commit();
