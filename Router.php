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
        $this->verificarPartidaEnCurso($controllerName);
        $controller = $this->getController($controllerName);
        if ($controller === null) {
            $controller = $this->factory->create($this->defaultController);
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

    public function verificarPartidaEnCurso($controllerName){
        $controllerCheck = strtolower($controllerName ?? '');
        if(isset($_SESSION['idPartida'])
            && $controllerCheck !== "preguntas"
            && $controllerCheck !== "ruleta") {
            $preguntasController = $this->factory->create('preguntasController');
            call_user_func([$preguntasController, "terminarPartida"]);
        }
    }

}
