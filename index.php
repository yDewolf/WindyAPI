<?php

declare(strict_types=1);

require __DIR__ . "/src/Database.php";
require __DIR__ . "/src/ErrorHandler.php";
require __DIR__ . "/src/RequestHandler.php";

require __DIR__ . "/src/utils/RandomTokenGen.php";
require __DIR__ . "/src/utils/ControllerUtils.php";
require __DIR__ . "/src/routing/Router.php";

# Controllers
require __DIR__ . "/src/UserController/UserController.php";
require __DIR__ . "/src/CommunityController/CommunityController.php";
require __DIR__ . "/src/SocialsController/SocialsController.php";

// spl_autoload_register(function ($class) {
//     $paths = [
//         "/src/$class/$class.php",
//         "/src/$class.php",
//         "/src/routing/$class.php"
//     ];
//     for ($i = 0; $i < count($paths); $i++) {
//         if (file_exists(__DIR__ . $paths[$i])) {
//             require __DIR__ . $paths[$i];
//             break;
//         }
//     }
// });

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

header("Content-type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Origin: *");

$classes = [];
$router = new Router("routes.ini");
// $router->parseRouteIni("routes.ini");

$router->parseURI($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

