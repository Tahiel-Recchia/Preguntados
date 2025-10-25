<?php

class RuletaController
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

    public function ruleta(){
        $categorias = $this->model->obtenerCategorias();
        $this->renderer->render("ruleta", ['categorias' => $categorias]);
    }
}