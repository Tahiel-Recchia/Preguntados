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
    private function requireEditor()
    {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], [2])) {
            header('Location: /');
            exit;
        }
    }
    public function base()
    {
        $this->requireEditor();
        $data = [];
        if (isset($_SESSION['user_id'])) {
            $data['sesion'] = [
                'id' => $_SESSION['user_id'],
                'nombreDeUsuario' => $_SESSION['nombreDeUsuario'] ?? null,
                'fotoDePerfil' => $_SESSION['fotoDePerfil'] ?? '/public/placeholder.png',
                'rol' => $_SESSION['rol'] ?? null
            ];
        }
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        $data["preguntas"] = $this->model->obtenerPreguntas($_SESSION['user_id'] ?? null);
        $sugs = $this->model->obtenerPreguntasSugeridas();
        $data['sugerencias'] = is_array($sugs) ? array_values($sugs) : [];
        $reportes = $this->model->obtenerReportesPendientes();
        $data['reportes'] = is_array($reportes) ? array_values($reportes) : [];
        $this->renderer->render('panelEditor', $data);
    }
    

    // === Crear nueva pregunta ===
    public function guardar()
    {
        $this->requireEditor();
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
                $respuesta_incorrecta3,
                $_SESSION['user_id'] ?? null
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
        $this->requireEditor();
        $id = $_POST["id"] ?? null;
        if ($id) {
            $this->model->deletePregunta($id);
        }
        header("Location: /paneleditor");
        exit;
    }

    public function actualizar()
    {
        $this->requireEditor();
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
                    $r['es_correcta'] = (int) $r['es_correcta'];
                } elseif (isset($r['es_correcta'])) {
                    $r['es_correcta'] = (int) $r['es_correcta'];
                }
            }
            unset($r);
        }

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function verReportes()
    {
        $this->requireEditor();
        $data = [];
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        $data['reportes'] = is_array($this->model->obtenerReportesPendientes()) ? array_values($this->model->obtenerReportesPendientes()) : [];
        $data['preguntas'] = is_array($this->model->obtenerPreguntas($_SESSION['user_id'] ?? null)) ? array_values($this->model->obtenerPreguntas($_SESSION['user_id'] ?? null)) : [];
        $sugs = $this->model->obtenerPreguntasSugeridas();
        $data['sugerencias'] = is_array($sugs) ? array_values($sugs) : [];
        $this->renderer->render('panelEditor', $data);
    }

public function aceptarReporte()
{
    $this->requireEditor();
    $id = $_POST['id_reporte'];

    $stmt = $this->conexion->prepare("UPDATE reporte SET estado = 'aceptado' WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header("Location: /paneleditor/verReportes");
    exit;
}

public function rechazarReporte()
{
    $this->requireEditor();
    $id = $_POST['id_reporte'];

    $stmt = $this->conexion->prepare("UPDATE reporte SET estado = 'rechazado' WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header("Location: /paneleditor/verReportes");
    exit;
}

    public function verSugerencias()
    {
        $this->requireEditor();
        $data = [];
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        $sugs = $this->model->obtenerPreguntasSugeridas();
        $data['sugerencias'] = is_array($sugs) ? array_values($sugs) : [];
        $data['preguntas'] = is_array($this->model->obtenerPreguntas($_SESSION['user_id'] ?? null)) ? array_values($this->model->obtenerPreguntas($_SESSION['user_id'] ?? null)) : [];
        $data['reportes'] = is_array($this->model->obtenerReportesPendientes()) ? array_values($this->model->obtenerReportesPendientes()) : [];
        $this->renderer->render('panelEditor', $data);
    }

public function aceptarSugerencia()
{
    $this->requireEditor();
    $id = $_POST['id_sugerencia'];
    $this->model->aceptarSugerencia($id, $_SESSION['user_id'] ?? null);
    header("Location: /paneleditor/verSugerencias");
    exit;
}

public function rechazarSugerencia()
{
    $this->requireEditor();
    $id = $_POST['id_sugerencia'];
    $this->model->rechazarSugerencia($id);
    header("Location: /paneleditor/verSugerencias");
    exit;
}


    public function guardarSugerencia()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $descripcion = $_POST['descripcion'] ?? '';
        $id_categoria = $_POST['id_categoria'] ?? null;
        $id_dificultad = $_POST['id_dificultad'] ?? null;
        $respuesta_correcta = $_POST['respuesta_correcta'] ?? '';
        $respuesta_incorrecta1 = $_POST['respuesta_incorrecta1'] ?? '';
        $respuesta_incorrecta2 = $_POST['respuesta_incorrecta2'] ?? '';
        $respuesta_incorrecta3 = $_POST['respuesta_incorrecta3'] ?? '';

        // Validaciones mínimas
        if (empty($descripcion) || empty($respuesta_correcta) || empty($respuesta_incorrecta1) || empty($respuesta_incorrecta2) || empty($respuesta_incorrecta3)) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $id = $this->model->insertarSugerencia($descripcion, $id_categoria, $id_dificultad, $respuesta_correcta, $respuesta_incorrecta1, $respuesta_incorrecta2, $respuesta_incorrecta3);

        // redirigir de vuelta a la página anterior (juego)
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    // Endpoint para reportar una pregunta (desde la UI de juego)
    public function reportarPregunta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $pregunta_id = $_POST['pregunta_id'] ?? null;
        $descripcion = $_POST['descripcion'] ?? '';
        $id_usuario = $_SESSION['user_id'] ?? null;

        if (empty($pregunta_id) || empty($descripcion)) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $this->model->insertarReporte($pregunta_id, $descripcion, $id_usuario);

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }
}
