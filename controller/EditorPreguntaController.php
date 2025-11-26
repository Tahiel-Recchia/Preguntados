<?php

class EditorPreguntaController
{
    private $conexion;
    private $renderer;
    private $model;
private $categorias;
    public function __construct($conexion, $renderer, $model, $categorias)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->categorias = $categorias;
    }

    public function base()
    {

        $preguntaId = $_GET['id'] ?? null;
        $pregunta = $this->model->obtenerPreguntaPorId($preguntaId);
        $respuestas = $this->model->obtenerRespuestasPorPregunta($preguntaId);
        $categorias = $this->categorias->getCategorias();
        foreach ($categorias as &$categoria) {
            if($categoria['id'] == $pregunta["id_categoria"]){
                $categoria["selected"] = true;
            } else {
                $categoria["selected"] = false;
            }
        }
        unset($categoria);
        $data = [
            'pregunta' => $pregunta,
            'rtaCorrecta' => $respuestas['correcta'],
            'rtaIncorrectas' => $respuestas['incorrectas'],
            'categorias' => $categorias
        ];


        $this->renderer->render('editorPregunta', $data);
    }


    
}
