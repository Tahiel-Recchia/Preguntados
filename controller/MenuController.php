<?php

class MenuController{

    private $conexion;
    private $renderer;
    private $model;

    private $perfil;
private $categoria;
    public function __construct($conexion, $renderer, $ranking, $perfil, $categoria)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $ranking;
        $this->perfil = $perfil;
        $this->categoria = $categoria;
    }


    public function base()
    {
        $data = [];
        if(!isset($_SESSION["nombreDeUsuario"])){
            header("Location: /login");
            exit();
        }
        if (isset($_SESSION["nombreDeUsuario"])) {
            $data["sesion"] = $this->perfil->getDatosUsuario($_SESSION["user_id"]);

            $data["isJugador"] = isset($data["sesion"]) && isset($data["sesion"]["rol_id"]) && in_array(intval($data["sesion"]["rol_id"]), [1], true);
            $data["isEditor"] = isset($data["sesion"]) && isset($data["sesion"]["rol_id"]) && in_array(intval($data["sesion"]["rol_id"]), [2], true);
            $data["isAdmin"] = isset($data["sesion"]) && isset($data["sesion"]["rol_id"]) && in_array(intval($data["sesion"]["rol_id"]), [3], true);
            $data['categorias'] = $this->categoria->getCategorias();
        }
        $data["ranking"] = $this->model->getRankingLimitado(5);
        $this->renderer->render("menu", $data);
    }
}