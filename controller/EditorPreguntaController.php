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
        // Permitir acceso solo a editores (no admins)
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 2) {
            header('Location: /index.php?controller=menu');
            exit;
        }
        $preguntaId = $_GET['id'] ?? null;
        $pregunta = $this->model->obtenerPreguntaPorId($preguntaId);
        $respuestas = $this->model->obtenerRespuestasPorPregunta($preguntaId);
        $data = [
            'pregunta' => $pregunta,
            'rtaCorrecta' => $respuestas['correcta'],
            'rtaIncorrectas' => $respuestas['incorrectas']
        ];

        // Proveer datos de sesión para que el navbar se renderice igual que en otras pantallas
        if (isset($_SESSION['user_id'])) {
            $data['sesion'] = [
                'id' => $_SESSION['user_id'],
                'nombreDeUsuario' => $_SESSION['nombreDeUsuario'] ?? null,
                'fotoDePerfil' => $_SESSION['fotoDePerfil'] ?? '/public/placeholder.png',
                'rol' => $_SESSION['rol'] ?? null
            ];
        }

        $this->renderer->render('editorPregunta', $data);
    }


    
}
