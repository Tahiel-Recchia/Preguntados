<?php

class PreguntasController
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

        //El usuario ya respondió y se carga la respuesta
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respuesta_usuario'])) {


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
            }


            $opcionesProcesadas = [];
            foreach ($data['opciones'] as $opcion) {

                // 1. Definimos los estados base (simples booleanos)
                $esLaRespuestaCorrecta = ($opcion['descripcion'] == $respuestaCorrecta);
                $esLaRespuestaDelUsuario = ($opcion['descripcion'] == $respuestaUsuario);
                $opcion['es_la_correcta'] = $esLaRespuestaCorrecta;
                $opcion['es_la_seleccionada_incorrecta'] = ($esLaRespuestaDelUsuario && !$esLaRespuestaCorrecta);
                $opcionesProcesadas[] = $opcion;
            }
            $data['opciones'] = $opcionesProcesadas;
            $data['modo_resultado'] = true;

            $this->renderer->render("preguntas", $data);

           //El usuario píde una pregunta nueva, TODAVÍA NO RESPONDIÓ
        } else {

            // 1. Obtenemos una pregunta nueva
            $data = $this->obtenerPregunta();
            if ($data == null) {
               //Si no hay más preguntas manda al menú, habría que hacer algo más lindo que solo mandar al menú
                unset($_SESSION['preguntasVistas']);
                header('Location: /menu');
                exit;
            }


            $respuestaCorrecta = "";
            foreach ($data['opciones'] as $opcion) {
                if ($opcion['es_correcta'] == 1) {
                    $respuestaCorrecta = $opcion['descripcion'];
                    break;
                }
            }

            $_SESSION['respuesta_correcta_actual'] = $respuestaCorrecta;
            $_SESSION['id_pregunta_actual'] = $data['id_pregunta'];

            $this->renderer->render("preguntas", $data);
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
}





