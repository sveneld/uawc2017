<?php

namespace uawc;

use Composer\Autoload\ClassLoader;
use go\DB\DB;
use GuzzleHttp\Client;
use Psr\Log\NullLogger;
use SebastianBergmann\Diff\Differ;

require 'vendor/autoload.php';

$classLoader = new ClassLoader();
$classLoader->setPsr4("uawc\\", [__DIR__.'/src']);
$classLoader->register(true);
$params = [
    'filename' => 'DB.sql',
    'mysql_quot' => true,
];
$db = DB::create($params, 'sqlite');

$cron = new Cron(
    new \simple_html_dom(),
    new \HTMLPurifier,
    new Client(),
    $db,
    new Differ('', false),
    new NullLogger()
);

$cron->collectLinks();