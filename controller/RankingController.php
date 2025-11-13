<?php

class RankingController
{

    private $conexion;
    private $renderer;
    private $model;
    private $perfil;

    public function __construct($conexion, $renderer, $model, $perfil)
    {

        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->perfil = $perfil;
    }

    public function base(){
        $this->mostrarRanking();
    }

    public function mostrarRanking(){
        $data = [];
        if (isset($_SESSION["nombreDeUsuario"])) {
            $data["sesion"] = $this->perfil->getDatosUsuario($_SESSION["user_id"]);
        }else{
            header("location: login");
        }
        $data["ranking"] = $this->model->getRankingLimitado(10);
        $this->renderer->render("ranking", $data);
    }
}