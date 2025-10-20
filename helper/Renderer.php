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
        $contentFilePath = $this->viewsPath . '/' . $contentFile . "Vista.mustache";
        echo $this->generateHtml($contentFilePath, $data);
    }

    public function generateHtml($contentFile, $data = array()) {
        $contentAsString = '';
        $contentAsString = file_get_contents($this->partialsPath . '/headerVista.mustache');
        if (!isset($data['noNavbar']) || $data['noNavbar'] == false) {
            $contentAsString .= file_get_contents($this->partialsPath . '/navbarVista.mustache');
        }
        $contentAsString .= file_get_contents($contentFile);
        $contentAsString .= file_get_contents($this->partialsPath . '/footerVista.mustache');

        return $this->mustache->render($contentAsString, $data);
    }
}