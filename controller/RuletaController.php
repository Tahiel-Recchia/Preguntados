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

    public function base(){
        $categoriasArray = $this->model->getCategorias();
        $data["categorias_json"] =  json_encode($categoriasArray);
        // Normalizar datos de sesión para que el navbar use la misma estructura en todas las vistas

        $this->renderer->render("ruleta", $data);
    }
}