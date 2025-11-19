<?php
session_start();
require 'vendor/autoload.php';
include_once("helper/Factory.php");
$factory = new Factory();
$router = $factory->create("router");
$pregunta = $factory->create("preguntascontroller");

$controller = isset($_GET['controller']) ? $_GET['controller'] : null;
$method     = isset($_GET['method']) ? $_GET['method'] : null;
if(isset($_SESSION['idPartida']) && $controller !== "preguntas" && $controller !== "ruleta"){
    $pregunta->terminarPartida();
}
$router->executeController($controller, $method);