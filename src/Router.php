<?php

declare(strict_types=1);

namespace WV;

final class Router
{
    /** @var list<array{method:string,regex:string,handler:array,middleware:list<string>}> */
    private array $routes = [];

    /** @param list<string> $middleware */
    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[A-Za-z0-9_.-]+)', $pattern);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @return array{handler:array,params:array<string,string>,middleware:list<string>}
     * @throws HttpException
     */
    public function match(string $method, string $path): array
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return ['handler' => $route['handler'], 'params' => $params, 'middleware' => $route['middleware']];
        }
        throw new HttpException($pathMatched ? 405 : 404);
    }
}
