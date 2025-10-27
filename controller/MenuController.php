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
        if (isset($_SESSION["nombreDeUsuario"])) {
            $data["sesion"] = $this->perfil->getDatosUsuario($_SESSION["user_id"]);
        }
        $data["ranking"] = $this->model->getRankingLimitado(5);
        $this->renderer->render("menu", $data);
    }
}