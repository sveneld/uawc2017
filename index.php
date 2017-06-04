<?php

namespace uawc;

use Composer\Autoload\ClassLoader;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use go\DB\DB;
use SebastianBergmann\Diff\Differ;
use uawc\Cache\DummyCache;
use function FastRoute\simpleDispatcher;

require 'vendor/autoload.php';

$classLoader = new ClassLoader();
$classLoader->setPsr4("uawc\\", [__DIR__ . '/src']);
$classLoader->register(true);

$db = DB::create(
    [
        'filename' => 'DB.sql',
        'mysql_quot' => true,
    ],
    'sqlite'
);

$cache = new DummyCache();

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/', 'help');
    $r->addGroup('/api/site/', function (RouteCollector $r) {
        $r->addRoute('GET', 'list', 'siteList');
        $r->addRoute('GET', '{id}/link/list', 'linkList');
        $r->addRoute('GET', '{id}/deleted/list', 'deletedLinkList');
        $r->addRoute('GET', '{id}/modified/list', 'modifiedLinkList');
    });
    $r->addGroup('/api/link/', function (RouteCollector $r) {
        $r->addRoute('GET', '{id}/version/list', 'linkVersionList');
        $r->addRoute('GET', '{id}[/{version:[\d]}]', 'link');
        $r->addRoute('GET', '{id}/diff/{versionFrom:[\d]}/{versionTo:[\d]}', 'linkDiff');
    });
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
        exit();
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        header("HTTP/1.0 405 Method Not Allowed");
        echo "405 Method Not Allowed";
        exit();
        break;
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $cacheKey = md5($handler.$vars);

        $response = $cache->get($cacheKey);

        if (is_null($response)) {
            $class = new API($db, new Differ('', false));
            $response = call_user_func_array([$class, $handler], $vars);
            $cache->save($cacheKey, $response);
        }

        echo $response;
        break;
}