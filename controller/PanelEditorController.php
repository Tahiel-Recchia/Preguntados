<?php

class PanelEditorController
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
        // Si hay sesión, cargamos datos del usuario
        $data = [];
        if (isset($_SESSION["nombreDeUsuario"])) {
            $data["nombreDeUsuario"] = $_SESSION["nombreDeUsuario"];
            // Cargar datos de sesión completos (foto, puntaje, etc.) para que la navbar muestre avatar
            if (isset($_SESSION["user_id"])) {
                // Usar PerfilModel temporalmente para obtener los datos
                if (!class_exists('PerfilModel')) {
                    include_once(__DIR__ . '/../model/PerfilModel.php');
                }
                $perfilModel = new PerfilModel($this->conexion);
                $data['sesion'] = $perfilModel->getDatosUsuario($_SESSION['user_id']);
                // Normalizar fotoDePerfil
                if (!empty($data['sesion']['fotoDePerfil'])) {
                    $foto = $data['sesion']['fotoDePerfil'];
                    if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                        $foto = '/' . ltrim($foto, '/');
                    }
                    $data['sesion']['fotoDePerfil'] = $foto;
                } else {
                    $data['sesion']['fotoDePerfil'] = '/public/placeholder.png';
                }
            }
        }

        // Podés traer info adicional del modelo
        $data["preguntas"] = $this->model->obtenerPreguntas();

        $this->renderer->render("panelEditor", $data);
    }

    // === Crear nueva pregunta ===
    public function guardar()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $descripcion = $_POST["descripcion"];
            $id_categoria = $_POST["id_categoria"];
            $id_dificultad = $_POST["id_dificultad"];
            $respuesta_correcta = $_POST["respuesta_correcta"];
            $respuesta_incorrecta1 = $_POST["respuesta_incorrecta1"];
            $respuesta_incorrecta2 = $_POST["respuesta_incorrecta2"];
            $respuesta_incorrecta3 = $_POST["respuesta_incorrecta3"];

            $this->model->insertarPregunta(
                $descripcion,
                $id_categoria,
                $id_dificultad,
                $respuesta_correcta,
                $respuesta_incorrecta1,
                $respuesta_incorrecta2,
                $respuesta_incorrecta3
            );

            header("Location: /paneleditor");
            exit;
        }

        // si no es POST, muestra el formulario
        $this->renderer->render("panelEditor");
    }

    // === Eliminar pregunta ===
    public function eliminar()
    {
        $id = $_POST["id"] ?? null;
        if ($id) {
            $this->model->deletePregunta($id);
        }
        header("Location: /paneleditor");
        exit;
    }

    public function actualizar()
    {
        $id = $_POST["id"];
        $descripcion = $_POST["descripcion"];
        $id_categoria = $_POST["id_categoria"];
        $id_dificultad = $_POST["id_dificultad"];
        $aprobada = isset($_POST["aprobada"]) ? 1 : 0;
        $respuesta_correcta = $_POST["respuesta_correcta"];
        $respuesta_incorrecta1 = $_POST["respuesta_incorrecta1"];
        $respuesta_incorrecta2 = $_POST["respuesta_incorrecta2"];
        $respuesta_incorrecta3 = $_POST["respuesta_incorrecta3"];

        $this->model->updatePreguntaConRespuestas(
            $id,
            $descripcion,
            $id_categoria,
            $id_dificultad,
            $aprobada,
            $respuesta_correcta,
            $respuesta_incorrecta1,
            $respuesta_incorrecta2,
            $respuesta_incorrecta3
        );

        header("Location: /paneleditor");
        exit;
    }

    // Devuelve una pregunta con sus respuestas en JSON (para el modal de edición)
    public function obtenerPregunta()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Falta id']);
            exit;
        }

        $data = $this->model->obtenerPreguntaConRespuestas($id);

        // Normalizar nombre de campo de la respuesta para JS (esCorrecta)
        if (!empty($data['respuestas']) && is_array($data['respuestas'])) {
            foreach ($data['respuestas'] as &$r) {
                if (isset($r['es_correcta'])) {
                    $r['esCorrecta'] = (int)$r['es_correcta'];
                } elseif (isset($r['esCorrecta'])) {
                    $r['esCorrecta'] = (int)$r['esCorrecta'];
                }
            }
            unset($r);
        }

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
