<?php

require __DIR__ . '/../src/bootstrap.php';

$c = $app->getContainer();
$db = $c->get('database');
$nf = $c->get('node');
$wiki = $c->get('wiki');

$db->beginTransaction();

$db->query("DELETE FROM nodes WHERE type = 'wiki'");

if ($user = $nf->where("type = 'user' AND published = 1 ORDER BY id LIMIT 1")) {
    $user = $user[0];
}

$windex = [];

$nodes = $nf->where("type = 'blog' OR type = 'article'");
foreach ($nodes as $node) {
    $source = "date: {$node['created']}\n";
    $source .= "section: {$node['type']}\n";

    if (preg_match('@^url: /(.+)$@m', $node['source'], $m)) {
        $url = $m[1];
        if (substr($url, -1) == '/') {
            $url .= 'index.html';
        }

        $source .= "disqus_id: {$url}\n";
    }

    if (false === strpos($node['source'], '---')) {
        $source .= "---\n";
    }

    $source .= $node['source'];

    try {
        $w = $wiki->updatePage($node['name'], $source, $user);
        $w['created'] = $node['created'];
        $nf->save($w);

    } catch (Throwable $e) {
        printf("Error converting node %u: %s\n", $node['id'], $e->getMessage());
    }

    if ($node['type'] == 'blog') {
        $date = strftime('%d.%m.%y', strtotime($node['created']));
        $windex[$node['created']] = "- {$date} [[{$node['name']}]]";
    }
}

krsort($windex);
$contents = implode("\n", $windex);

$wiki->updatePage('Блог', $contents, $user);

$db->commit();
