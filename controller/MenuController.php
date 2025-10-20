<?php

class MenuController{

    private $conexion;
    private $renderer;

    public function __construct($conexion, $renderer)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
    }


public function base(){
    $data = [];
    if (isset($_SESSION['username'])) {
        $data['username'] = $_SESSION['username'];
    }
    $this->renderer->render("menu", $data);
    }
}