<?php

class Renderer{
    private $mustache;
    private $viewsPath;
    private $partialsPath;

    public function __construct($viewsPath, $partialsPath){
        $this->mustache = new Mustache_Engine(
            array(
                'partials_loader' => new Mustache_Loader_FilesystemLoader( $partialsPath )
            ));
        $this->viewsPath = $viewsPath;
        $this->partialsPath = $partialsPath;
    }

    public function render($contentFile , $data = array() ){
        if(isset($_SESSION['nombreDeUsuario'])) {
            $data['sesion']['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'];
            $data['sesion']['fotoDePerfil'] = $_SESSION['fotoDePerfil'];
            $data['sesion']['id'] = $_SESSION['user_id'];
            $data['sesion']['rol'] = $_SESSION['rol'];
        }
        $contentFilePath = $this->viewsPath . '/' . $contentFile . "Vista.mustache";
        echo $this->generateHtml($contentFilePath, $data);
    }

    public function generateHtml($contentFile, $data = array()) {
        $layoutTemplate = file_get_contents($this->partialsPath . '/layoutVista.mustache');
        $contentTemplate = file_get_contents($contentFile);
        $renderedContent = $this->mustache->render($contentTemplate, $data);
        $data['content'] = $renderedContent;
        if (isset($data['sesion']) && is_array($data['sesion'])) {
            foreach ($data['sesion'] as $k => $v) {
                if (!array_key_exists($k, $data)) {
                    $data[$k] = $v;
                }
            }
        }


        return $this->mustache->render($layoutTemplate, $data);
    }
}