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

    public function mostrarPregunta(){
        $categoriaId = $_POST['categoria'] ?? $_GET['categoria'] ?? null;
        if($categoriaId == null){
            header('Location: /menu');
        }
        $pregunta = $this->model->obtenerPorCategoria($categoriaId);

        $this->renderer->render("preguntas", $pregunta);
    }
}