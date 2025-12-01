<?php

class RuletaController
{


    private $renderer;
    private $model;

    public function __construct($renderer, $model)
    {
        $this->renderer = $renderer;
        $this->model = $model;
    }

    public function base(){
        $idUsuario = $_SESSION['user_id'];
        $categoriasArray = $this->model->getCategoriasConEstado($idUsuario);
        $data["categorias_json"] =  json_encode($categoriasArray);

        $this->renderer->render("ruleta", $data);
    }
}