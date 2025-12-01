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
$controladoresDeJuego = ['preguntas', 'ruleta', 'usuario'];
$metodosDeJuego = ['reportarPregunta'];

$controller = isset($_GET['controller']) ? strtolower($_GET['controller']) : null;
$method     = isset($_GET['method']) ? $_GET['method'] : null;

if(isset($_SESSION['idPartida'])
    && !in_array($controller, $controladoresDeJuego)
    && !in_array($method, $metodosDeJuego)) {
    call_user_func([$pregunta, "terminarPartida"]);
}
if(isset($_SESSION['user_id'])){
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

    if (($rolActual == $ROL_EDITOR || $rolActual == $ROL_USUARIO) && $controller == "paneladmin") {
        header("Location: /");
        exit();
    }
} else{
    $controladoresSinLogin = ['login', 'register'];
    if(!in_array($controller, $controladoresSinLogin)){
        header("Location: /login");
        exit();
    }
}
$router->executeController($controller, $method);