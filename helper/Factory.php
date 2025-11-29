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
include_once("model/RankingModel.php");
include_once("vendor/autoload.php");
include_once("controller/PerfilController.php");
include_once("model/PerfilModel.php");
include_once("model/BuscarPartidaModel.php");
include_once("controller/BuscarPartidaController.php");
include_once("model/CategoriaModel.php");
include_once("controller/RuletaController.php");
include_once("model/PanelEditorModel.php");
include_once("controller/PanelEditorController.php");
include_once("controller/EditorPreguntaController.php");
include_once("model/EditorPreguntaModel.php");
include_once("model/RankingModel.php");
include_once("controller/RankingController.php");
include_once("model/PartidaModel.php");
include_once("model/PuntajeModel.php");
include_once("model/QrModel.php");
include_once("model/AdminModel.php");
include_once("controller/PanelAdminController.php");
include_once("model/HistoriaModel.php");
include_once("controller/HistorialController.php");
include_once ("controller/ApiController.php");

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
        $this->objetos["categoriamodel"] = new CategoriaModel($this->objetos["database"]);
        $this->objetos["renderer"] = new Renderer("vista", "vista/partial");
        $this->objetos["router"] = new Router($this, 'menucontroller', 'base');
        $this->objetos["loginmodel"] = new LoginModel($this->objetos["database"]);
        $this->objetos["logincontroller"] = new LoginController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["loginmodel"]);
        $this->objetos["rankingmodel"] = new RankingModel($this->objetos["database"]);
        // PerfilModel necesario para MenuController; crear antes de instanciar menucontroller
        $this->objetos["perfilmodel"] = new PerfilModel($this->objetos["database"]);
        // Instanciar MenuController (controlador por defecto)
        $this->objetos["menucontroller"] = new MenuController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["rankingmodel"], $this->objetos["perfilmodel"], $this->objetos['categoriamodel']);
        $this->objetos["registermodel"] = new RegisterModel($this->objetos["database"]);
        $this->objetos["registercontroller"] = new RegisterController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["registermodel"]);
        $this->objetos["qrmodel"] = new QrModel();
        $this->objetos["perfilcontroller"] = new PerfilController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["perfilmodel"], $this->objetos["qrmodel"]);
        // editor model y controller
        $this->objetos["puntajemodel"] = new PuntajeModel($this->objetos["database"]);

        $this->objetos["preguntasmodel"] = new PreguntasModel($this->objetos["database"]);
        $this->objetos["partidamodel"] = new PartidaModel($this->objetos["database"]);
        $this->objetos["preguntascontroller"] = new PreguntasController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["preguntasmodel"], $this->objetos["partidamodel"], $this->objetos["perfilmodel"], $this->objetos["puntajemodel"], $this->objetos["categoriamodel"]);
        $this->objetos["buscarpartidamodel"] = new BuscarPartidaModel($this->objetos["database"]);
        $this->objetos["buscarpartidacontroller"] = new BuscarPartidaController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["buscarpartidamodel"]);
        $this->objetos["rankingmodel"] = new RankingModel($this->objetos["database"]);
        $this->objetos["rankingcontroller"] = new RankingController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["rankingmodel"], $this->objetos["perfilmodel"]);
        $this->objetos["ruletacontroller"] = new RuletaController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["categoriamodel"]);
        $this->objetos["paneleditormodel"] = new PanelEditorModel($this->objetos["database"]);
        $this->objetos["paneleditorcontroller"] = new PanelEditorController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["paneleditormodel"], $this->objetos["categoriamodel"]);
        $this->objetos["editorpreguntamodel"] = new EditorPreguntaModel($this->objetos["database"]);
        $this->objetos["editorpreguntacontroller"] = new EditorPreguntaController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["editorpreguntamodel"], $this->objetos["categoriamodel"]);
        $this->objetos["adminmodel"] = new AdminModel($this->objetos["database"]);
        $this->objetos["paneladmincontroller"] = new PanelAdminController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["adminmodel"]);
        $this->objetos["historialmodel"] = new HistoriaModel($this->objetos["database"]);
        $this->objetos["historialcontroller"] = new HistorialController($this->objetos["database"], $this->objetos["renderer"], $this->objetos["historialmodel"]);
        $this->objetos["apicontroller"] = new ApiController();
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
