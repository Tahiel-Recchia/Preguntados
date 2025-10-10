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
    $this->renderer->render("menu");

    }
}