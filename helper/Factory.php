<?php
include_once("controller/LoginController.php");
include_once("helper/Renderer.php");
include_once("helper/Database.php");
include_once("controller/MenuController.php");
include_once("controller/PreguntasController.php");
include_once("Router.php");
include_once("controller/RegisterController.php");
include_once("model/RegisterModel.php");
include_once("model/LoginModel.php");
include_once("model/PreguntasModel.php");
include_once("vendor/autoload.php");
include_once("controller/PerfilController.php");
include_once("model/PerfilModel.php");
class Factory
{
    private $config;
    private $objetos;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");
        $this->objetos["database"] = new Database(
            $this->config["server"],
            $this->config["user"],
            $this->config["pass"],
            $this->config["database"]
        );
        $this->objetos["renderer"] = new Renderer("vista", "vista/partial");
        $this->objetos["router"] = new Router($this, 'registercontroller', 'base');
        $this->objetos["loginmodel"] = new LoginModel($this->objetos["database"]);
        $this->objetos["logincontroller"] = new LoginController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["loginmodel"]);
        $this->objetos["menucontroller"] = new MenuController($this->objetos["database"], $this->objetos["renderer"]);
        $this->objetos["registermodel"] = new RegisterModel($this->objetos["database"]);
        $this->objetos["registercontroller"] = new RegisterController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["registermodel"]);
        $this->objetos["perfilmodel"] = new PerfilModel($this->objetos["database"]);
        $this->objetos["perfilcontroller"] = new PerfilController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["perfilmodel"]);
        $this->objetos["preguntasmodel"] = new PreguntasModel($this->objetos["database"]);
        $this->objetos["preguntascontroller"] = new PreguntasController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["preguntasmodel"]);
    }


    public function create($string)
    {
        $string = strtolower($string);
        if (isset($this->objetos[$string])) {
            return $this->objetos[$string];
        }
        return null;
    }

}
