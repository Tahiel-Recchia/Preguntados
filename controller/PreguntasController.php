<?php

class PreguntasController
{
    private $conexion;
    private $renderer;
    private $model;
    private $partida;
    private $perfil;
    private $puntaje;
    private $categoria;


    public function __construct($conexion, $renderer, $model, $partida, $perfil, $puntaje, $categoria)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->partida = $partida;
        $this->perfil = $perfil;
        $this->puntaje = $puntaje;
        $this->categoria = $categoria;
    }

    public function base()
    {
        if(!isset($_SESSION["idPartida"])) {
            $this->inicializarPartida();
        }
        $this->jugarPartida();
    }

    public function inicializarPartida(){
            $_SESSION["puntajeActual"] = 0;
            $_SESSION['idPartida'] = $this->partida->iniciarPartida();
    }
    public function jugarPartida()
    {
        //El usuario ya respondió y se carga la respuesta
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respuesta_usuario'])) {
            $this->procesarRespuesta();
            //El usuario píde una pregunta nueva, TODAVÍA NO RESPONDIÓ
        } else {
            // 1. Obtenemos una pregunta nueva
            $this->mostrarPregunta();
        }
    }

    public function mostrarPregunta()
    {
        if(!isset($_SESSION['id_pregunta_actual'])){
            $partida  = $this->obtenerPregunta();

            if (!$partida) {
                $this->finalizarPorFaltaDePreguntas();
                return;
            }

            $_SESSION['id_pregunta_actual'] = $partida['id_pregunta'];
            $_SESSION['respuesta_correcta_actual'] = $this->model->getRespuestaCorrecta($partida['id_pregunta']);
            $_SESSION['horaEnvio'] = $this->model->getHoraEnvio();
            $partida['puntos'] = $_SESSION['puntajeActual'];

        } else {
            $partida = $this->model->obtenerPorId($_SESSION['id_pregunta_actual']);
            $partida['categoria'] = $this->categoria->getCategoriaById($_GET['categoria']);
            $partida['puntos'] = $_SESSION['puntajeActual'];
        }
        $partida['ocultarNavbar'] = true;
        $this->renderer->render("preguntas", $partida);
    }
    public function obtenerPregunta()
    {
        $categoriaId = $_POST['categoria'] ?? $_GET['categoria'] ?? ($_SESSION['categoria_actual'] ?? null);
        if ($categoriaId == null) {
            return null;
        }
        $idUsuario = $_SESSION['user_id'];

        if(!$this->verificarCategoriaValida($categoriaId, $idUsuario)){
            header('Location: /ruleta/base');
            exit();
        }

        $nivelUsuario = $_SESSION['ratio'] ?? 0.0;
        $pregunta = $this->model->obtenerPorCategoria($idUsuario, $categoriaId, $nivelUsuario);

        $pregunta['categoria'] = $this->categoria->getCategoriaById($categoriaId);
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
        $data['categorias'] = $this->categoria->getCategorias();
        $data['puntos'] = $_SESSION['puntajeActual'];
        if ($esCorrecta) {
            $this->sumarPuntos();
            $data['mensaje_resultado'] = "¡Correcto!";
            $data['es_correcto'] = true;
            $this->actualizarEstadisticas(1, $idUsuario);
            $this->renderer->render("preguntas", $data);
        } else {
            $this->terminarPartida();
            $this->actualizarEstadisticas(0, $idUsuario);
            $this->renderer->render("preguntaErronea", $data);
        }
        $this->model->actualizarDificultadPregunta($idPregunta, $esCorrecta);
        $this->model->guardarPreguntaVista($idUsuario, $idPregunta);
        unset($_SESSION['respuesta_correcta_actual'], $_SESSION['id_pregunta_actual']);
    }

    public function sumarPuntos(){
        $_SESSION['puntajeActual'] += 1;
    }

    public function actualizarEstadisticas($esCorrecta, $idUsuario){
        $this->perfil->actualizarRatio($esCorrecta,  $idUsuario);
    }

    public function finalizarPorFaltaDePreguntas(){
        $this->limpiarSesionPreguntas();
        $this->actualizarEstadisticas($_SESSION['user_id']);
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
        $idUsuario = $_SESSION['user_id'];
        $idPregunta = $_SESSION['id_pregunta_actual'];
        $this->actualizarEstadisticas(0, $idUsuario);
        $this->model->guardarPreguntaVista($idUsuario, $idPregunta);
        $idPregunta = $_SESSION['id_pregunta_actual'] ?? null;
        $data = [];
        if ($idPregunta) {
            $data = $this->model->obtenerPorId($idPregunta) ?? [];
        }

        $data['tiempoAgotado'] = "¡Te quedaste sin tiempo!";
        $data['puntos'] = $_SESSION['puntajeActual'];
        $this->terminarPartida();
        $this->renderer->render("preguntaErronea", $data);
    }

    public function procesarOpciones($data, $respuestaCorrecta, $respuestaUsuario) {
        if (!isset($data['opciones'])) return $data;
        foreach ($data['opciones'] as &$opcion) {
            $esLaCorrecta = ($opcion['descripcion'] == $respuestaCorrecta);
            $esLaSeleccionada = ($opcion['descripcion'] == $respuestaUsuario);
            $opcion['es_la_correcta'] = $esLaCorrecta;
            $opcion['es_la_seleccionada_incorrecta'] = ($esLaSeleccionada && !$esLaCorrecta);
            $opcion['es_otra_incorrecta'] = (!$esLaCorrecta && !$esLaSeleccionada);
        }
        unset($opcion);

        $data['modo_resultado'] = true;

        return $data;
    }

    public function limpiarSesionPreguntas(){
        unset($_SESSION['preguntasVistas']);
        unset($_SESSION['id_pregunta_actual']);
        unset($_SESSION['horaEnvio'], $_SESSION['horaRespuesta']);
        unset($_SESSION['respuesta_correcta_actual']);
    }

    public function salir(){
        $this->terminarPartida();
        header('Location: /');
        exit();
    }

    public function obtenerHoraDeInicio()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['horaEnvio'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No hay partida activa']);
            return;
        }
        $horaEnvio = $_SESSION['horaEnvio'];
        $timestamp = $horaEnvio->getTimestamp();

        echo json_encode([
            'status' => 'ok',
            'timestamp_inicio' => $timestamp,
            'hora_servidor_actual' => time()
        ]);
    }

    public function verificarCategoriaValida($categoriaId, $idUsuario){
        $categoriasConEstado = $this->categoria->getCategoriasConEstado($idUsuario);

        $esCategoriaValida = false;

        foreach ($categoriasConEstado as $cat) {
            if ($cat['id'] == $categoriaId) {
                if ($cat['disponible'] === true) {
                    $esCategoriaValida = true;
                }
                break;
            }
        }

        return $esCategoriaValida;
    }
}