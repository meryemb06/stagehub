<?php

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/routes.php';

boot();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/') ?: '/';
}

try {
    foreach (route_list() as [$verb, $pattern, $handler]) {
        if ($verb === $method && preg_match($pattern, $path, $match)) {
            $handler($match);
            return;
        }
    }
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo 'Une erreur est survenue.';
    return;
}

http_response_code(404);
echo 'Page introuvable.';
