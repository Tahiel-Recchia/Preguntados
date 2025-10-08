<?php
session_start();

include_once("helper/Factory.php");
$factory = new Factory();
$database = $factory->create("database");
$renderer = $factory->create("renderer");
$loginController = $factory->create("loginController");

$controllerParam = isset($_GET["controller"]) ? $_GET["controller"] : "login";
$methodParam     = isset($_GET["method"]) ? $_GET["method"] : "login";
$controllers = [
    "login" => $loginController,
];

if (isset($controllers[$controllerParam])) {
    $controller = $controllers[$controllerParam];

    // si el metodo no existe, usar login() por defecto
    if (!method_exists($controller, $methodParam)) {
        $methodParam = 'login';
    }

    // Llamar al metodo
    $controller->$methodParam();
} else {
    // controlador no existe, fallback
    $loginController->login();
}