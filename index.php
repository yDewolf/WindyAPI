<?php

declare(strict_types=1);

require __DIR__ . "/src/utils/RandomTokenGen.php";
require __DIR__ . "/src/utils/ControllerUtils.php";

spl_autoload_register(function ($class) {
    $paths = [
        "/src/$class/$class.php",
        "/src/$class.php",
        "/src/routing/$class.php"
    ];
    for ($i = 0; $i < count($paths); $i++) {
        if (file_exists(__DIR__ . $paths[$i])) {
            require __DIR__ . $paths[$i];
            break;
        }
    }
});

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

header("Content-type: application/json; charset=UTF-8");

$classes = [];
$router = new Router([]);
$router->parseRouteIni("routes.ini");

$router->parseURI($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

