#!/usr/bin/env php
<?php

/**
 * Update node indexes.
 *
 * 1) Reads and saves all nodes, to update node indexes.
 * 2) Updates search index for all nodes.
 **/

$app = require __DIR__ . '/../config/bootstrap.php';

$container = $app->getContainer();
$db = $container->db;
$logger = $container->logger;
$nodeRepo = $container->node;
$fts = $container->fts;
$wiki = $container->wiki;

$db->transact(function ($db) use ($nodeRepo, $fts, $logger) {
    $logger->info('starting node reindex...');

    $db->query('DELETE FROM search');

    // Maintain previous update time.
    $update = $db->prepare('UPDATE nodes SET updated = ? WHERE id = ?');

    // TODO: use a cursor to minimize memory usage.

    $nodes = $nodeRepo->where('deleted = 0 ORDER BY id');
    foreach ($nodes as $node) {
        $timestamp = $node['updated'];
        $nodeRepo->save($node);
        $update->execute([$timestamp, $node['id']]);

        if ($meta = getNodeSearchData($node)) {
            $key = 'node:' . $node['id'];
            $meta['updated'] = $node['updated'];

            $text = $meta['text'];
            unset($meta['text']);

            $title = $meta['title'];

            $fts->reindexDocument($key, $title, $text, $meta);
        }
    }

    $logger->info('reindexed {count} node fields.', [
        'count' => count($nodes),
    ]);
});


function getNodeSearchData(array $node): ?array
{
    global $wiki;

    if ((int)$node['deleted'] === 1) {
        return null;
    }

    if ((int)$node['published'] === 0) {
        return null;
    }

    switch ($node['type']) {
    case 'file':
    case 'user':
        return null;
    case 'wiki':
        return $wiki->getSearchMeta($node);
    default:
        $meta = [
            'title' => $node['title'] ?? $node['name'] ?? null,
            'text' => $node['description'] ?? $node['text'] ?? null,
            'link' => "/node/{$node['id']}",
            'snippet' => $node['snippet'] ?? $node['description'] ?? null,
            'image' => null,
        ];
        return empty($meta['text']) ? null : $meta;
    }
}
