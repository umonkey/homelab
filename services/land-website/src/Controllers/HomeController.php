<?php

/**
 * Главная страница.
 *
 * Выводит готовый шаблон, никаких данных.
 **/

declare(strict_types=1);

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Ufw1\Controller;

class HomeController extends Controller
{
    public function index(Request $request, Response $response, array $args): Response
    {
        $user = $this->auth->getUser($request);

        return $this->render($request, 'pages/home.twig', [
            'user' => $user,
        ]);
    }
}
