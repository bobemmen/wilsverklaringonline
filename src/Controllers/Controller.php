<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\App;
use WV\Response;

abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    protected function view(string $template, array $vars = [], int $status = 200): Response
    {
        return Response::html($this->app->view->render($template, $vars), $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }

    protected function input(string $key): string
    {
        return $this->app->request->input($key);
    }

    /** De ingelogde gebruiker (de 'auth'-middleware garandeert dat die bestaat). */
    protected function user(): array
    {
        return $this->app->auth->user() ?? throw new \WV\HttpException(403);
    }

    protected function declaration(): array
    {
        return $this->app->store->declarationForUser($this->user()['id']);
    }

    protected function flash(string $message): void
    {
        $this->app->session->flash('status', $message);
    }

    /** Terug naar een formulier met foutmeldingen en eerder ingevulde (niet-geheime) waarden. */
    protected function fail(string $to, array $errors, array $old = []): Response
    {
        $this->app->session->flash('errors', $errors);
        $this->app->session->flash('old', $old);
        return Response::redirect($to);
    }

    /** Waarheen na inloggen: de oorspronkelijk gevraagde pagina (alleen interne paden). */
    protected function intended(string $default = '/dashboard'): string
    {
        $path = $this->app->session->pull('intended');
        return (is_string($path) && preg_match('#^/(?!/)[A-Za-z0-9/_\-.]*$#', $path)) ? $path : $default;
    }
}
