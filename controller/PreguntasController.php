<?php

class PreguntasController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($conexion, $renderer, $model)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
    }

    public function base(){
        $data = [];
        if (isset($_SESSION['nombreDeUsuario'])) {
            $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'];
        }
        $this->renderer->render("preguntas", $data);
    }
}