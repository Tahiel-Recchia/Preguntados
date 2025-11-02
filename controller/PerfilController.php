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
        $datos = [];
        if (isset($_SESSION["nombreDeUsuario"])) {
            $datos["sesion"] = $this->model->getDatosUsuario($_SESSION["user_id"]);
        }

        $usuarioId = isset($_GET['id']) ? $_GET['id'] : ($_SESSION["user_id"] ?? null);
        $datos['usuario'] = $usuarioId ? $this->model->getDatosUsuario($usuarioId) : null;

        // Marcar si el usuario es editor (rol_id == 2)
        $datos['isEditor'] = false;
        if (!empty($datos['usuario']) && isset($datos['usuario']['rol_id'])) {
            $datos['isEditor'] = ($datos['usuario']['rol_id'] == 2);
        }

        $this->renderer->render("perfil", $datos);
    }


}