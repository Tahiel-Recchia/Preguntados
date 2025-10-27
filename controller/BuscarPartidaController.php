<?php

class BuscarPartidaController
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

    public function jugarpartida(){
        if(isset($_SESSION['nombreDeUsuario'])){
            header('Location: /ruleta/base');
            exit;
        }else{
            header('Location: /perfil');
            exit;
        }
    }


}