<?php

class HistorialController
{


    private $renderer;

    private $historialmodel;

    public function __construct($renderer, $historialmodel)
    {

        $this->renderer = $renderer;
        $this->historialmodel = $historialmodel;
    }

    public function base(){
        $this->mostrarHistorial();
    }
    public function mostrarHistorial(){
        if(!isset($_SESSION["user_id"])){
            header("Location: /");
            exit();
        }
        $data["historial"] = $this->historialmodel->getHistorial($_SESSION["user_id"]);
        $this->renderer->render("historial", $data);
    }
}