<?php

declare(strict_types=1);

/**
 * A minimal router.
 *
 * The router's whole job: given a request method (GET/POST) and a URL path
 * (/product/velocity-runner), find the matching handler and run it.
 *
 * Routes may contain {placeholders}, e.g. '/product/{slug}'. The matched
 * values are passed to the handler as arguments.
 */
class Router
{
    /** @var array<string, array<string, callable>> method => [pattern => handler] */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Find the route matching this request and run its handler.
     * Returns the handler's output (an HTML string) or a 404 page.
     */
    public function dispatch(string $method, string $uri): string
    {
        // The raw URI can include a query string, e.g. "/search?q=shoes".
        // We only route on the path part; query params stay in $_GET.
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Treat "/cart/" and "/cart" as the same page.
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $params = $this->match($pattern, $path);
            if ($params !== null) {
                return $handler(...$params);
            }
        }

        http_response_code(404);
        return render('errors/404', [], ['title' => 'Page not found']);
    }

    /**
     * Compare a route pattern against the actual path.
     * Returns the captured {placeholder} values, or null if no match.
     */
    private function match(string $pattern, string $path): ?array
    {
        // Convert '/product/{slug}' into the regex '#^/product/([^/]+)$#'.
        // [^/]+ means "one or more characters that are not a slash".
        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            array_shift($matches); // drop the full-string match, keep captures
            return array_map('urldecode', $matches);
        }

        return null;
    }
}
