<?php
session_start();
require 'vendor/autoload.php';
include_once("helper/Factory.php");
$factory = new Factory();
$router = $factory->create("router");
$pregunta = $factory->create("preguntascontroller");
$ROL_ADMIN = 3;
$ROL_EDITOR = 2;
$ROL_USUARIO = 1;
$controladoresDeJuego = ['preguntas', 'ruleta'];

$controller = isset($_GET['controller']) ? strtolower($_GET['controller']) : null;
$method     = isset($_GET['method']) ? $_GET['method'] : null;

if(isset($_SESSION['idPartida'])
    && !in_array($controller, $controladoresDeJuego)) {
    call_user_func([$pregunta, "terminarPartida"]);
}
if(isset($_SESSION['idUsuario'])){
    $rolActual = $_SESSION['rol'];
    $rolesProhibidosEnJuego = [$ROL_ADMIN, $ROL_EDITOR];
    $controladoresJuegoInicio = ['preguntas', 'ruleta', 'buscarpartida'];
    if (in_array($rolActual, $rolesProhibidosEnJuego) && in_array($controller, $controladoresJuegoInicio)) {
        header("Location: /");
        exit();
    }

    $controladoresEditor = ['paneleditor', 'editorpregunta'];
    if ($rolActual == $ROL_USUARIO && in_array($controller, $controladoresEditor)) {
        header("Location: /");
        exit();
    }

    if ($rolActual == $ROL_EDITOR && $controller == "paneladmin") {
        header("Location: /");
        exit();
    }
}
$router->executeController($controller, $method);