<?php

require 'vendor/autoload.php';

$configuration = [
    'site4Monitoring' => 'http://brovary-rada.gov.ua/documents/',
];

$params = [
    'host' => 'localhost',
    'username' => 'test',
    'password' => 'test',
    'dbname' => 'test',
    'charset' => 'utf8',
    '_debug' => true,
    '_prefix' => 'p_',
];

$db = go\DB\DB::create($params, 'mysql');


$client = new \GuzzleHttp\Client(
    [
        'base_uri' => $configuration['site4Monitoring'],
        'headers' => [
            'Content-Type' => 'application/json;charset=UTF-8',
        ],
        'timeout' => 30,
        'verify' => true,
    ]
);



$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/cron/monitoring', 'cron/monitoring');
    $r->addRoute('GET', '/api/site/list', 'handler');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
        exit();
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        header("HTTP/1.0 405 Method Not Allowed");
        echo "405 Method Not Allowed";
        exit();
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        $class = new \uawc\SiteMonitoring\API($db);

        call_user_func_array([$class, $handler], $vars);
        break;
}