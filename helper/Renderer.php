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
        $contentAsString = file_get_contents( $this->partialsPath .'/header.mustache');
        $contentAsString .= file_get_contents( $contentFile );
        $contentAsString .= file_get_contents($this->partialsPath . '/footer.mustache');

        return $this->mustache->render($contentAsString, $data);
    }
}