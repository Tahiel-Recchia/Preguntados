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
        echo "ID recibido: " . $preguntaId;
        $pregunta = $this->model->obtenerPreguntaPorId($preguntaId);
        $respuestas = $this->model->obtenerRespuestasPorPregunta($preguntaId);
        
        echo '$respuestas';
        
        $this->renderer->render('editorPregunta', [
            'pregunta' => $pregunta,
            'rtaCorrecta' => $respuestas['correcta'],
            'rtaIncorrectas' => $respuestas['incorrectas']
        ]);
    }


    
}
