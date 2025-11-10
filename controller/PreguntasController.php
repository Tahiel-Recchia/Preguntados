<?php

class PreguntasController
{
    private $conexion;
    private $renderer;
    private $model;
    private $partida;
    private $perfil;


    public function __construct($conexion, $renderer, $model, $partida, $perfil)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->partida = $partida;
        $this->perfil = $perfil;
    }

    public function base()
    {
        if (!isset($_SESSION['idPartida'])) {
            $_SESSION['idPartida'] = $this->partida->iniciarPartida();
        }

        if(!isset($_SESSION["preguntas_totales"])){
            $_SESSION["preguntas_totales"] = 0;
        }
        if(!isset($_SESSION["preguntas_correctas"])){
            $_SESSION["preguntas_correctas"] = 0;
        }

        $data['sesion']['nombreDeUsuario'] = $_SESSION["nombreDeUsuario"];
        $this->jugarPartida();
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


    public function cargarRespuesta()
    {
        $respuestaUsuario = $_POST['respuesta_usuario'];
        $respuestaCorrecta = $_SESSION['respuesta_correcta_actual'] ?? '';
        $idPreguntaAnterior = $_SESSION['id_pregunta_actual'] ?? 0;
        $idUsuario = $_SESSION['user_id'];
        $data = $this->model->obtenerPorId($idPreguntaAnterior);
        $data = $this->procesarOpciones($data, $respuestaCorrecta, $respuestaUsuario);
        $esValida = $this->model->verificarRespuesta($idPreguntaAnterior, $respuestaUsuario);
        $_SESSION['horaRespuesta'] = $this->model->getHoraEnvio();
        if(!$this->partida->verificarTiempo($_SESSION['horaEnvio'], $_SESSION['horaRespuesta'])){
            $this->tiempoAgotado();
            return;
        }
        unset($_SESSION['horaEnvio']);
        unset($_SESSION['horaRespuesta']);
        if ($esValida) {
            $data['mensaje_resultado'] = "¡Correcto!";
            $data['es_correcto'] = true;
            $this->renderer->render("preguntas", $data);
        } else {
            $this->terminarPartida();
            $this->renderer->render("preguntaErronea", $data);
        }
        if($esValida){
            $_SESSION['preguntas_correctas']++;
        }
        $_SESSION['preguntas_totales']++;

        if($_SESSION['preguntas_totales'] > 0){
            $_SESSION['ratio'] = $_SESSION['preguntas_correctas'] / $_SESSION['preguntas_totales']; ;
        }
        $this->perfil->actualizarRatio($idUsuario, $_SESSION['ratio']);
    }

    public function cargarPregunta()
    {
        $data = $this->obtenerPregunta();
        if ($data == null) {
            //Si no hay más preguntas manda al menú, habría que hacer algo más lindo que solo mandar al menú
            unset($_SESSION['preguntasVistas']);
            unset($_SESSION['respuesta_correcta_actual']);
            unset( $_SESSION['id_pregunta_actual']);
            header('Location: /menu');
            exit;
        }


        $respuestaCorrecta = $this->model->getRespuestaCorrecta($data['id_pregunta']);
        $horaEnvio = $this->model->getHoraEnvio();
        $_SESSION['horaEnvio'] = $horaEnvio;
        $_SESSION['respuesta_correcta_actual'] = $respuestaCorrecta;
        $_SESSION['id_pregunta_actual'] = $data['id_pregunta'];
        $this->renderer->render("preguntas", $data);
    }

    public function terminarPartida(){
        $this->partida->terminarPartida($_SESSION['idPartida'], 1000);
        unset($_SESSION['idPartida']);
        unset($_SESSION['preguntasVistas']);
        unset($_SESSION['respuesta_correcta_actual']);
        unset( $_SESSION['id_pregunta_actual']);
    }

    public function tiempoAgotado(){
        $this->terminarPartida();
        $data['tiempoAgotado'] = "¡Te quedaste sin tiempo!";
        $this->renderer->render("preguntaErronea", $data);
    }

    public function procesarOpciones($data, $respuestaCorrecta, $respuestaUsuario) {

        $opciones = $data['opciones'];
        $opcionesProcesadas = [];

        foreach ($opciones as $opcion) {
            $esLaCorrecta = ($opcion['descripcion'] == $respuestaCorrecta);
            $esLaSeleccionada = ($opcion['descripcion'] == $respuestaUsuario);

            $opcion['es_la_correcta'] = $esLaCorrecta;
            $opcion['es_la_seleccionada_incorrecta'] = ($esLaSeleccionada && !$esLaCorrecta);
            $opcion['es_otra_incorrecta'] = (!$esLaCorrecta && !$esLaSeleccionada);

            $opcionesProcesadas[] = $opcion;
        }
        $data['opciones'] = $opcionesProcesadas;
        $data['modo_resultado'] = true;

        return $data;
    }

}