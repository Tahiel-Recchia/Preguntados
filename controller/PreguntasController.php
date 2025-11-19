<?php

class PreguntasController
{
    private $conexion;
    private $renderer;
    private $model;
    private $partida;
    private $perfil;
    private $puntaje;


    public function __construct($conexion, $renderer, $model, $partida, $perfil, $puntaje)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->partida = $partida;
        $this->perfil = $perfil;
        $this->puntaje = $puntaje;
    }

    public function base()
    {
        if(!isset($_SESSION["preguntas_totales"])){
            $_SESSION["preguntas_totales"] = 0;
        }
        if(!isset($_SESSION["preguntas_correctas"])){
            $_SESSION["preguntas_correctas"] = 0;
        }
        $this->inicializarPartida();
        $this->jugarPartida();
    }

    public function inicializarPartida(){
        if(!isset($_SESSION['idPartida'])){
            $_SESSION['idPartida'] = $this->partida->iniciarPartida();
            $_SESSION["puntajeActual"] = 0;
        }
    }
    public function jugarPartida()
    {
        //El usuario ya respondió y se carga la respuesta
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respuesta_usuario'])) {
            $this->procesarRespuesta();
            //El usuario píde una pregunta nueva, TODAVÍA NO RESPONDIÓ
        } else {
            // 1. Obtenemos una pregunta nueva
            $this->mostrarNuevaPregunta();
        }
    }

    public function mostrarNuevaPregunta()
    {
        $pregunta = $this->obtenerPregunta();
        if (!$pregunta) {
            $this->finalizarPorFaltaDePreguntas();
            return;
        }
        $respuestaCorrecta = $this->model->getRespuestaCorrecta($pregunta['id_pregunta']);
        $horaEnvio = $this->model->getHoraEnvio();
        $_SESSION['horaEnvio'] = $horaEnvio;
        $_SESSION['respuesta_correcta_actual'] = $respuestaCorrecta;
        $_SESSION['id_pregunta_actual'] = $pregunta['id_pregunta'];
        $this->renderer->render("preguntas", $pregunta);
    }

    public function obtenerPregunta()
    {
        $categoriaId = $_POST['categoria'] ?? $_GET['categoria'] ?? ($_SESSION['categoria_actual'] ?? null);
        if ($categoriaId == null) {
            return null;
        }
        if (!isset($_SESSION['preguntasVistas']) || !is_array($_SESSION['preguntasVistas'])) {
            $_SESSION['preguntasVistas'] = [];
        }

        $idsExcluidos = $_SESSION['preguntasVistas'];
        $nivelUsuario = $_SESSION['ratio'];
        $pregunta = $this->model->obtenerPorCategoria($categoriaId, $idsExcluidos, $nivelUsuario);
        if ($pregunta) {
            $_SESSION['preguntasVistas'][] = $pregunta['id_pregunta'];
        }

        return $pregunta;
    }


    public function procesarRespuesta()
    {
        $idPregunta = $_SESSION['id_pregunta_actual'];
        $idUsuario = $_SESSION['user_id'];
        $respuestaUsuario = $_POST['respuesta_usuario'];

        $respuestaCorrecta = $_SESSION['respuesta_correcta_actual'];

        $horaRespuesta = $this->model->getHoraEnvio();
        $_SESSION['horaRespuesta'] = $horaRespuesta;

        if(!$this->partida->verificarTiempo($_SESSION['horaEnvio'], $horaRespuesta)){
            $this->tiempoAgotado();
            return;
        }

        unset($_SESSION['horaEnvio'], $_SESSION['horaRespuesta']);

        $esCorrecta = $this->model->verificarRespuesta($idPregunta, $respuestaUsuario);

        $data = $this->model->obtenerPorId($idPregunta);
        $data = $this->procesarOpciones($data, $respuestaCorrecta, $respuestaUsuario);

        if ($esCorrecta) {
            $this->sumarPuntos();
            $data['mensaje_resultado'] = "¡Correcto!";
            $data['es_correcto'] = true;
            $this->renderer->render("preguntas", $data);
        } else {
            $this->terminarPartida();
            $this->renderer->render("preguntaErronea", $data);
        }

        $this->actualizarEstadisticas($idUsuario);
    }

    public function sumarPuntos(){
        $_SESSION['puntajeActual'] +=30;
        $_SESSION['preguntas_correctas'] ++;
    }

    public function actualizarEstadisticas($idUsuario){
        $_SESSION['preguntas_totales'] ++;
        $ratio = $_SESSION['preguntas_correctas'] / $_SESSION['preguntas_totales'];
        $this->perfil->actualizarRatio($_SESSION['preguntas_correctas'], $_SESSION['preguntas_totales'], $ratio, $idUsuario);
    }

    public function finalizarPorFaltaDePreguntas(){
        $this->limpiarSesionPreguntas();
        $this->terminarPartida();
        header('Location: /');
    }

    public function terminarPartida(){
        $idUsuario = $_SESSION['user_id'];
        $puntajeFinal = $_SESSION['puntajeActual'];
        $this->partida->terminarPartida($_SESSION['idPartida'], $puntajeFinal);

        $this->puntaje->actualizarMejorPuntaje($idUsuario, $puntajeFinal);

        $this->limpiarSesionPreguntas();

        $_SESSION['puntajeActual'] = 0;

        unset($_SESSION['idPartida']);
    }

    public function tiempoAgotado(){
        $this->terminarPartida();
        $data['tiempoAgotado'] = "¡Te quedaste sin tiempo!";
        $this->renderer->render("preguntaErronea", $data);
    }

    public function procesarOpciones($data, $respuestaCorrecta, $respuestaUsuario) {

        $opciones = $data['opciones'];

        foreach ($opciones as $opcion) {
            $esLaCorrecta = ($opcion['descripcion'] == $respuestaCorrecta);
            $esLaSeleccionada = ($opcion['descripcion'] == $respuestaUsuario);

            $opcion['es_la_correcta'] = $esLaCorrecta;
            $opcion['es_la_seleccionada_incorrecta'] = ($esLaSeleccionada && !$esLaCorrecta);
            $opcion['es_otra_incorrecta'] = (!$esLaCorrecta && $esLaSeleccionada != $respuestaUsuario);
        }
        $data['modo_resultado'] = true;

        return $data;
    }

    public function limpiarSesionPreguntas(){
        unset($_SESSION['preguntasVistas']);
        unset($_SESSION['respuesta_correcta_actual']);
        unset($_SESSION['id_pregunta_actual']);
    }
}