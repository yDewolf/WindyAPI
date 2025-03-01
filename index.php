<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $paths = [
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


// $parts = explode("/", $_SERVER["REQUEST_URI"]);
$database = new Database("localhost", "windy_db", "root", "");

$gateway = new UsersGateway($database);
$controller = new UserController($gateway);

$getUserRoute = new Route("getUser", ["id" => true], $controller);

$router = new Router([$getUserRoute]);
$router->parseURI($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

// $id = $parts[2] ?? null;

// # Use a config file later

// $controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
