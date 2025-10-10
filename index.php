<?php
session_start();

include_once("helper/Factory.php");
$factory = new Factory();
$database = $factory->create("database");
$renderer = $factory->create("renderer");
$router = $factory->create("router");

$controller = isset($_GET['controller']) ? $_GET['controller'] : null;
$method     = isset($_GET['method']) ? $_GET['method'] : null;

$router->executeController($controller, $method);

