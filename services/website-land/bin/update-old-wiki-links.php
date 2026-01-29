<?php

/**
 * Обновление старых ссылок внутри вики страниц.
 *
 * Проверяет все вики страницы на наличие внутренних ссылок, которые есть в таблице nodes_wiki_idx (поле url).
 * Если находит -- заменяет.
 **/

$app = require __DIR__ . '/../config/bootstrap.php';

$container = $app->getContainer();
$db = $container->get('db');
$nodeRepo = $container->get('node');

$db->beginTransaction();

$nodes = $nodeRepo->where('type = ?', ['wiki']);
foreach ($nodes as $node) {
    if (empty($node['source'])) {
        printf("wiki page \"%s\" is empty.\n", $node['name']);
        continue;
    }

    if ($source = update_source($node['source'], $nodeRepo)) {
        $node['source'] = $source;
        $node = $nodeRepo->save($node);
        printf("wiki page \"%s\" updated\n", $node['name']);
    }
}

$db->commit();

function update_source(string $source, $nodeRepo): ?string
{
    $match = false;

    // Замена обычных ссылок: [title](target).
    $source = preg_replace_callback('@\[([^]]+)\]\(([^)]+)\)@', function ($m) use ($nodeRepo, &$match) {
        $target = $nodeRepo->where('type = ? AND id IN (SELECT id FROM nodes_wiki_idx WHERE url = ?)', ['wiki', $m[2]]);
        if (!empty($target)) {
            $match = true;
            $target = $target[0]['name'];
            return "[[{$target}|{$m[1]}]]";
        } else {
            return $m[0];
        }
    }, $source);

    // Замена сносок: [1]: /target/
    $source = preg_replace_callback('@^\[(.+?)\]: (\S+)@m', function ($m) use ($source, $nodeRepo, &$match) {
        $node = $nodeRepo->where('type = ? AND id IN (SELECT id FROM nodes_wiki_idx WHERE url = ?)', ['wiki', $m[2]]);

        if (!empty($node)) {
            $node = $node[0];
            $match = true;
            $target = '/wiki?name=' . urlencode($node['name']);
            return "[{$m[1]}]: {$target} \"{$node['name']}\"";
        } else {
            return $m[0];
        }
    }, $source);

    return $match ? $source : null;
}
