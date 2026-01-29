<?php

/**
 * Генератор картинки для шаринга страницы.
 *
 * Использует шаблон из файла public/images/kdpv-template.png
 **/

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Ufw1\Controller;

class NodePictureController extends Controller
{
    public function index(Request $request, Response $response, array $args): Response
    {
        $nid = $args['id'];

        $node = $this->node->get($nid);
        if (empty($node)) {
            $this->notfound();
        }

        $text = $node['kdpv_text'] ?? $node['title'] ?? $node['name'] ?? $node['subtitle'];
        if (empty($text)) {
            $this->notfound();
        }

        try {
            $image = $this->kdpv->render($text);

            $response->getBody()->write($image);
            return $response->withHeader('Content-Type', 'image/png');
        } catch (\Throwable $e) {
            debug($e);
            $this->notfound();
        }
    }
}
