<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;

require dirname(__DIR__) . '/config/bootstrap.php';

$_env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'prod';
$_debug = filter_var($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL);

if ($_debug) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($_env, $_debug);
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
