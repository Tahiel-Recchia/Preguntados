<?php

class Router
{

    private $factory;
    private $renderer;
    private $defaultController;
    private $defaultMethod;

    public function __construct($factory, $defaultController, $defaultMethod)
    {
        $this->factory = $factory;
        $this->renderer = $factory->create('renderer');
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
    }


    public function executeController($controllerName, $methodName)
    {
        $controller = $this->getController($controllerName);
        if ($controller === null) {
            $this->executeMethod($this->defaultController, $this->defaultMethod);
            exit;
        }
        $this->executeMethod($controller, $methodName);
    }

    public function getController($controllerName)
    {
        $controllerString = isset($controllerName) ? $controllerName . 'Controller' : $this->defaultController;
        return $this->factory->create($controllerString);
    }

    // public function executeMethod($controller, $method){

    //     $validMethod = method_exists($controller, $method) ? $method : "";
    //     if($validMethod === ""){
    //         call_user_func([$controller, $this->defaultMethod]);
    //         exit;
    //     }
    //     call_user_func([$controller, $method]);
    // }

    public function executeMethod($controller, $method)
    {
        // Manejar sesion ACA

        // Si $method no está definido o no es string, usar el método por defecto
        if (!is_string($method) || !method_exists($controller, $method)) {
            // Llama al método por defecto, por ejemplo "base" o "index"
            call_user_func([$controller, $this->defaultMethod]);
            return;
        }

        // Si el método existe, lo ejecuta
        call_user_func([$controller, $method]);
    }

}
