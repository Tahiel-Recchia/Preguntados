<?php

class PreguntasController
{
    private $conexion;
    private $renderer;
    private $model;
    private $partida;

    public function __construct($conexion, $renderer, $model, $partida)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->partida = $partida;
    }

    public function base()
    {
        if (isset($_SESSION['idPartida'])) {
            $this->jugarPartida();
        } else {
            $_SESSION['idPartida'] = $this->partida->iniciarPartida();
            $this->jugarPartida();
        }
    }

    public function jugarPartida()
    {
        //El usuario ya respondió y se carga la respuesta
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respuesta_usuario'])) {

            $this->cargarRespuesta();
            //El usuario píde una pregunta nueva, TODAVÍA NO RESPONDIÓ
        } else {
            // 1. Obtenemos una pregunta nueva
            $this->cargarPregunta();
        }
    }

    public function obtenerPregunta()
    {
        $categoriaId = $_POST['categoria'] ?? $_GET['categoria'] ?? null;

        if ($categoriaId == null) {
            return null;
        }
        if (!isset($_SESSION['preguntasVistas']) || !is_array($_SESSION['preguntasVistas'])) {
            $_SESSION['preguntasVistas'] = [];
        }
        $idsExcluidos = $_SESSION['preguntasVistas'];


        $pregunta = $this->model->obtenerPorCategoria($categoriaId, $idsExcluidos);

        if ($pregunta) {
            $_SESSION['preguntasVistas'][] = $pregunta['id_pregunta'];
        }

        return $pregunta;
    }


    public function cargarRespuesta()
    {
        $respuestaUsuario = $_POST['respuesta_usuario'];
        $respuestaCorrecta = $_SESSION['respuesta_correcta_actual'] ?? '';
        $idPreguntaAnterior = $_SESSION['id_pregunta_actual'] ?? 0;


        $data = $this->model->obtenerPorId($idPreguntaAnterior);

        // Verificar la respuesta
        $esValida = $this->model->verificarRespuesta($idPreguntaAnterior, $respuestaUsuario);
        if ($esValida) {
            $data['mensaje_resultado'] = "¡Correcto!";
            $data['es_correcto'] = true;
            //Acá hay que poner el código para sumar los puntos
        } else {
            $data['mensaje_resultado'] = "¡Incorrecto!";
            $data['es_incorrecto'] = true;
            $this->partida->terminarPartida($_SESSION['idPartida'], 1000);
            unset($_SESSION['idPartida']);

        }


        $opcionesProcesadas = [];
        foreach ($data['opciones'] as $opcion) {
            $esLaRespuestaCorrecta = ($opcion['descripcion'] == $respuestaCorrecta);
            $esLaRespuestaDelUsuario = ($opcion['descripcion'] == $respuestaUsuario);
            $opcion['es_la_correcta'] = $esLaRespuestaCorrecta;
            $opcion['es_la_seleccionada_incorrecta'] = ($esLaRespuestaDelUsuario && !$esLaRespuestaCorrecta);
            $opcionesProcesadas[] = $opcion;
        }
        $data['opciones'] = $opcionesProcesadas;
        $data['modo_resultado'] = true;
        $data['sesion']['nombreDeUsuario'] = $_SESSION["nombreDeUsuario"];

        $this->renderer->render("preguntas", $data);
    }

    public function cargarPregunta()
    {
        $data = $this->obtenerPregunta();
        if ($data == null) {
            //Si no hay más preguntas manda al menú, habría que hacer algo más lindo que solo mandar al menú
            unset($_SESSION['preguntasVistas']);
            header('Location: /menu');
            exit;
        }


        $respuestaCorrecta = $this->model->getRespuestaCorrecta($data['id_pregunta']);

        $_SESSION['respuesta_correcta_actual'] = $respuestaCorrecta;
        $_SESSION['id_pregunta_actual'] = $data['id_pregunta'];
        $data['sesion']['nombreDeUsuario'] = $_SESSION["nombreDeUsuario"];

        $this->renderer->render("preguntas", $data);
    }

}

