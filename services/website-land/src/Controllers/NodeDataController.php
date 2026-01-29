<?php

/**
 * Вывод информации о файле.
 **/

declare(strict_types=1);

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Ufw1\Controller;

class NodeDataController extends Controller
{
    public function index(Request $request, Response $response, array $args): Response
    {
        $nid = (int)$args['id'];

        $node = $this->node->get($nid);

        if (null === $node || $node['published'] == 0 || $node['deleted'] == 1) {
            return $response->withJSON([]);
        }

        if ($node['type'] != 'file') {
            return $response->withJSON([]);  // FIXME
        }

        return $response->withJSON([
            'file' => [
                'id' => (int)$node['id'],
                'name' => $node['name'],
                'type' => $node['mime_type'],
                'title' => $node['title'] ?? null,
                'caption' => $node['caption'] ?? null,
                'description' => $node['description'] ?? null,
                'preview' => $node['files']['medium']['url'] ?? "/node/{$nid}/download/medium",
            ],
        ]);
    }
}
