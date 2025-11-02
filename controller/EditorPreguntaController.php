<?php

class EditorPreguntaController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($conexion, $renderer, $model)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
    }

    public function base()
    {
        $preguntaId = $_GET['id'] ?? null;
        $pregunta = $this->model->obtenerPreguntaPorId($preguntaId);
        $respuestas = $this->model->obtenerRespuestasPorPregunta($preguntaId);
        
        $this->renderer->render('editorPregunta', [
            'pregunta' => $pregunta,
            'rtaCorrecta' => $respuestas['correcta'],
            'rtaIncorrectas' => $respuestas['incorrectas']
        ]);
    }


    
}
