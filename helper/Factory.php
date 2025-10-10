<?php
include_once("controller/LoginController.php");
include_once("helper/Renderer.php");
include_once("helper/Database.php");
include_once("controller/MenuController.php");
include_once("Router.php");
    class Factory {
        private $config;
        private $objetos;

        public function __construct(){
             $this->config = parse_ini_file("config/config.ini");
             $this->objetos["database"] = new Database(
                 $this->config["server"],
                 $this->config["user"],
                 $this->config["pass"],
                 $this->config["database"]
             );
             $this->objetos["renderer"] = new Renderer();
             $this->objetos["router"] = new Router($this, 'menuController', 'base');
             $this->objetos["logincontroller"] = new LoginController($this->objetos["database"], $this->objetos["renderer"]);
             $this->objetos["menucontroller"] = new MenuController($this->objetos["database"], $this->objetos["renderer"]);
      }


        public function create($string) {
            $string = strtolower($string);
            if (isset($this->objetos[$string])) {
                return $this->objetos[$string];
            }
            return null;
        }

}
