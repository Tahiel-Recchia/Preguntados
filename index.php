<?php
session_start();
require 'vendor/autoload.php';
include_once("helper/Factory.php");
$factory = new Factory();
$router = $factory->create("router");

$controller = isset($_GET['controller']) ? $_GET['controller'] : null;
$method     = isset($_GET['method']) ? $_GET['method'] : null;

$router->executeController($controller, $method);