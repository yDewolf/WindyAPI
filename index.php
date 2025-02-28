<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    require __DIR__ . "/src/$class.php";
});

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");


header("Content-type: application/json; charset=UTF-8");

$parts = explode("/", $_SERVER["REQUEST_URI"]);

$id = $parts[2] ?? null;

# Use a config file later
$database = new Database("localhost", "windy_db", "root", "");

$gateway = new UsersGateway($database);
$controller = new UserController($gateway);

$controller->processRequest($_SERVER["REQUEST_METHOD"], $id);
