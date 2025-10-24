<?php

class MenuController{

    private $conexion;
    private $renderer;

    public function __construct($conexion, $renderer)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
    }


    public function base()
    {
        $data = [];

        if (isset($_SESSION["nombreDeUsuario"])) {
            $data["nombreDeUsuario"] = $_SESSION["nombreDeUsuario"];
        }

        $this->renderer->render("menu", $data);
    }
}