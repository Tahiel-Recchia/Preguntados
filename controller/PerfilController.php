<?php

class PerfilController
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
        $idUsuario = $_SESSION["user_id"];
        $datos = $this->model->getDatosUsuario($idUsuario);
        $this->renderer->render("perfil", $datos);
    }


}