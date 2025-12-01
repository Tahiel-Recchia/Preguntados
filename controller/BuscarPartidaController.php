<?php

class BuscarPartidaController
{

    private $renderer;
    private $model;

    public function __construct($renderer, $model)
    {
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