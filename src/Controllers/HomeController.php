<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('pages/welcome', ['loggedIn' => $this->app->auth->check()]);
    }

    public function health(): Response
    {
        $this->app->db->scalar('SELECT 1');
        return new Response('ok', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
