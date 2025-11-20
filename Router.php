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
        $controllerCheck = strtolower($controllerName);

        // Lista negra: Palabras que NO son controladores reales, sino archivos
        // Agregamos 'imagenes' y 'favicon.ico' que vimos en tu log
        $ignorados = ['imagenes', 'public', 'js', 'css', 'favicon.ico', 'vendor'];

        // La condición ahora dice:
        // 1. Si NO es preguntas
        // 2. Y NO es ruleta
        // 3. Y NO está en la lista de ignorados (imagenes, favicon, etc.)
        // 4. Y hay sesión... ENTONCES borramos.

        if($controllerCheck !== "preguntas"
            && $controllerCheck !== "ruleta"
            && !in_array($controllerCheck, $ignorados)
            && isset($_SESSION['idPartida'])) {

            $preguntasController = $this->factory->create('preguntasController');
            call_user_func([$preguntasController, "terminarPartida"]);
        }
    }

}
