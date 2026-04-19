<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;

require dirname(__DIR__) . '/config/bootstrap.php';

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
