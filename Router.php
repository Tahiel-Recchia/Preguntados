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
        // Obtener instancia del controlador solicitada (si viene nulo, usar el controlador por defecto)
        $controller = $this->getController($controllerName);
        if ($controller === null) {
            // Obtener la instancia del controlador por defecto desde la factory
            // (usamos create() directamente porque $this->defaultController ya contiene la clave completa)
            $controller = $this->factory->create($this->defaultController);
            // Si aún así no existe, no hay nada que ejecutar
            if ($controller === null) {
                return;
            }
            $this->executeMethod($controller, $this->defaultMethod);
            exit;
        }

        $this->executeMethod($controller, $methodName);
    }

    public function getController($controllerName)
    {
        $controllerString = isset($controllerName) ? $controllerName . 'Controller' : $this->defaultController;
        return $this->factory->create($controllerString);
    }


    public function executeMethod($controller, $method)
    {
         if (!is_string($method) || !method_exists($controller, $method)) {

            call_user_func([$controller, $this->defaultMethod]);
            return;
        }
        call_user_func([$controller, $method]);
    }

}
