<?php

class Router
{

   private $factory;
   private $renderer;
   private $defaultController;
   private $defaultMethod;

    public function __construct( $factory, $defaultController, $defaultMethod ){
        $this->factory = $factory;
        $this->renderer = $factory->create('renderer');
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
    }


    public function executeController($controllerName, $methodName){
        $controller = $this->getController($controllerName);
        if ($controller === null) {
            $defaultControllerInstance = $this->getController($this->defaultController);

            $this->executeMethod($defaultControllerInstance, $this->defaultMethod);
            exit;
        }
        $this->executeMethod($controller, $methodName);
    }

    public function getController($controllerName){
        $controller = $this->getControllerName($controllerName);
        return $controller;
    }

    public function executeMethod($controller, $method){
        $validMethod = method_exists($controller, $method) ? $method : "";
        if($validMethod === ""){
            call_user_func([$controller, $this->defaultMethod]);
            exit;
        }
        $controller->$validMethod();
    }

    private function getControllerName($controllerName){
        $controllerName = isset($controllerName) ? $controllerName . 'Controller' : $this->defaultController;
        return $this->factory->create($controllerName);
}
}