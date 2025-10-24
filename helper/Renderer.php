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
        $this->partialsPath = $partialsPath; // Guardamos esto
    }

    public function render($contentFile , $data = array() ){
        $contentFilePath = $this->viewsPath . '/' . $contentFile . "Vista.mustache";
        echo $this->generateHtml($contentFilePath, $data);
    }

    public function generateHtml($contentFile, $data = array()) {
        $layoutTemplate = file_get_contents($this->partialsPath . '/layoutVista.mustache');
        $contentTemplate = file_get_contents($contentFile);
        $renderedContent = $this->mustache->render($contentTemplate, $data);
        $data['content'] = $renderedContent;


        return $this->mustache->render($layoutTemplate, $data);
    }
}