#!/usr/bin/env php
<?php

/**
 * Прекомпиляция всех шаблонов.
 *
 * Рендерит все найденные шаблоны, для получения готовых файлов перед выносом.
 * Попутно валидирует все шаблоны, всплывают ошибки с неверными инклудами и прочим.
 **/

$app = require __DIR__ . '/../config/bootstrap.php';

$container = $app->getContainer();
$paths = $container->get('settings')['templates']['template_path'] ?? [];
$template = $container->get('template');

$files = [];

foreach ($paths as $root) {
    $root = realpath($root);

    $di = new RecursiveDirectoryIterator($root);
    $i = new RecursiveIteratorIterator($di);
    $i = new RegexIterator($i, '@\.twig$@');

    foreach ($i as $k => $v) {
        if ($v->isFile()) {
            $name = substr($k, strlen($root) + 1);
            $files[$name] = true;
        }
    }
}

ksort($files);

$fail = false;
foreach (array_keys($files) as $name) {
    try {
        $template->render($name, []);
    } catch (\Throwable $e) {
        printf("%s: %s\n", $name, $e->getMessage());
        $fail = true;
    }
}

exit($fail ? 1 : 0);
