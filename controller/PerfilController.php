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
        if (isset($_SESSION["nombreDeUsuario"])) {
            $datos["sesion"] = $this->model->getDatosUsuario($_SESSION["user_id"]);
        }
        $datos['usuario'] = isset($_GET['id']) ? $this->model->getDatosUsuario($_GET['id']) : $this->model->getDatosUsuario($_SESSION["user_id"]);
        $this->renderer->render("perfil", $datos);
    }


}