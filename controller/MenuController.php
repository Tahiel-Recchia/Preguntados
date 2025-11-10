<?php

class MenuController{

    private $conexion;
    private $renderer;
    private $model;

    private $perfil;

    public function __construct($conexion, $renderer, $ranking, $perfil)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $ranking;
        $this->perfil = $perfil;
    }


    public function base()
    {
        $data = [];
        if(!isset($_SESSION["nombreDeUsuario"])){
            header("Location: /login");
        }
        if (isset($_SESSION["nombreDeUsuario"])) {
            $data["sesion"] = $this->perfil->getDatosUsuario($_SESSION["user_id"]);
            // Indicar a la vista si el usuario es editor (rol_id == 2)
            // Mostrar opciones de editor también para administradores (rol_id 3)
            $data["isEditor"] = isset($data["sesion"]) && isset($data["sesion"]["rol_id"]) && in_array(intval($data["sesion"]["rol_id"]), [2, 3], true);
        }
        $data["ranking"] = $this->model->getRankingLimitado(5);
        // Renderizamos la vista con el nombre base 'menu' (Renderer agrega 'Vista' automáticamente)
        $this->renderer->render("menu", $data);
    }
}