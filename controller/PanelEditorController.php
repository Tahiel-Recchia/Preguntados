<?php

class PanelEditorController
{
    private $renderer;
    private $model;
    private $categoria;


    public function __construct($renderer, $model, $categoria)
    {
        $this->renderer = $renderer;
        $this->model = $model;
        $this->categoria = $categoria;
    }
    public function base()
    {
        $data = [];

        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        $data["preguntas"] = $this->model->obtenerPreguntas($_SESSION['user_id'] ?? null);
        $sugs = $this->model->obtenerPreguntasSugeridas();
        $data['sugerencias'] = is_array($sugs) ? array_values($sugs) : [];
        $reportes = $this->model->obtenerReportesPendientes();
        $data['reportes'] = is_array($reportes) ? array_values($reportes) : [];
        $data['categorias'] = $this->categoria->getCategorias();
        $this->renderer->render('panelEditor', $data);
    }


    // === Crear nueva pregunta ===
    public function guardar()
    {

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $descripcion = $_POST["descripcion"];
            $id_categoria = $_POST["id_categoria"];
            $respuesta_correcta = $_POST["respuesta_correcta"];
            $respuesta_incorrecta1 = $_POST["respuesta_incorrecta1"];
            $respuesta_incorrecta2 = $_POST["respuesta_incorrecta2"];
            $respuesta_incorrecta3 = $_POST["respuesta_incorrecta3"];

            $this->model->insertarPregunta(
                $descripcion,
                $id_categoria,
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
        $aprobada = isset($_POST["aprobada"]) ? 1 : 0;
        $respuesta_correcta = $_POST["respuesta_correcta"];
        $respuesta_incorrecta1 = $_POST["respuesta_incorrecta1"];
        $respuesta_incorrecta2 = $_POST["respuesta_incorrecta2"];
        $respuesta_incorrecta3 = $_POST["respuesta_incorrecta3"];

        $this->model->updatePreguntaConRespuestas(
            $id,
            $descripcion,
            $id_categoria,
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

    /**
     * Devuelve un reporte con la información de la pregunta y sus respuestas en JSON
     */
    public function obtenerReporte()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Falta id']);
            exit;
        }

        $report = $this->model->obtenerReportePorId($id);
        error_log('PanelEditorController::obtenerReporte - reporte: ' . print_r($report, true));

        if ($report && is_array($report)) {
            if (empty($report['descripcion'])) {
                $report['descripcion'] = $report['motivo'] ?? $report['mensaje'] ?? $report['razon'] ?? $report['report_text'] ?? '';
            }
        }
        if (!$report) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Reporte no encontrado']);
            exit;
        }

        $preguntaId = $report['pregunta_id'] ?? null;
        $pregData = null;
        if ($preguntaId) {
            $pregData = $this->model->obtenerPreguntaConRespuestas($preguntaId);
        }

        $out = [
            'reporte' => $report,
            'pregunta' => $pregData['pregunta'] ?? null,
            'respuestas' => $pregData['respuestas'] ?? []
        ];

        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }

    public function verReportes()
    {
        $data = [];
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        $data['reportes'] = is_array($this->model->obtenerReportesPendientes()) ? array_values($this->model->obtenerReportesPendientes()) : [];
        $data['preguntas'] = is_array($this->model->obtenerPreguntas($_SESSION['user_id'] ?? null)) ? array_values($this->model->obtenerPreguntas($_SESSION['user_id'] ?? null)) : [];
        $sugs = $this->model->obtenerPreguntasSugeridas();
        $data['sugerencias'] = is_array($sugs) ? array_values($sugs) : [];
        $data['categorias'] = $this->categoria->getCategorias();
        $this->renderer->render('panelEditor', $data);
    }




    public function verSugerencias()
    {
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
        $id = $_POST['id_sugerencia'];
        $this->model->aceptarSugerencia($id, $_SESSION['user_id'] ?? null);
        header("Location: /paneleditor/verSugerencias");
        exit;
    }

    public function rechazarSugerencia()
    {
        $id = $_POST['id_sugerencia'];
        $this->model->rechazarSugerencia($id);
        header("Location: /paneleditor/verSugerencias");
        exit;
    }

    public function actualizarDesdeReporte()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paneleditor/verReportes');
            exit;
        }
        $idPregunta = $_POST['id'] ?? null;
        $idReporte = $_POST['id_reporte'] ?? null;
        $descripcion = $_POST['descripcion'] ?? '';
        $id_categoria = $_POST['id_categoria'] ?? null;
        $respuesta_correcta = $_POST['respuesta_correcta'] ?? '';
        $respuesta_incorrecta1 = $_POST['respuesta_incorrecta1'] ?? '';
        $respuesta_incorrecta2 = $_POST['respuesta_incorrecta2'] ?? '';
        $respuesta_incorrecta3 = $_POST['respuesta_incorrecta3'] ?? '';
        $aprobada = 1; // restaurar como aprobada

        if ($idPregunta) {
            $this->model->updatePreguntaConRespuestas(
                $idPregunta,
                $descripcion,
                $id_categoria,
                $aprobada,
                $respuesta_correcta,
                $respuesta_incorrecta1,
                $respuesta_incorrecta2,
                $respuesta_incorrecta3
            );
            if ($idReporte) {
                $this->model->eliminarReporte($idReporte);
            }
        }
        header('Location: /paneleditor/verReportes');
        exit;
    }



    // === Categorías ===
    public function guardarCategoria()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $descripcion = $_POST['descripcion'] ?? '';
            $color = $_POST['color'] ?? '';

            $rutaImagen = null;

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {

                $tmp = $_FILES['imagen']['tmp_name'];

                $nombreOriginal = basename($_FILES['imagen']['name']);
                $nombreImagen = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);

                // ✔ Ruta física correcta
                $destino = __DIR__ . "/../public/categorias/" . $nombreImagen;

                // Si falla, lo mostramos
                if (!move_uploaded_file($tmp, $destino)) {
                    die("ERROR moviendo archivo a: " . $destino);
                }

                // ✔ Ruta que se guarda en la BD
                $rutaImagen = "/public/categorias/" . $nombreImagen;
            }

            $this->categoria->guardarCategoria($descripcion, $color, $rutaImagen);
        }

        header("Location: /paneleditor/categorias");
        exit;
    }




    public function eliminarCategoria()
    {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            header("Location: /paneleditor");
            exit;
        }

        // Borrar BD
        $this->model->deleteCategoria($id);

        header("Location: /paneleditor");
        exit;
    }

    public function editarCategoria()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $descripcion = $_POST['descripcion'] ?? '';
            $color = $_POST['color'] ?? '';

            // Traer categoría actual para imagen vieja
            $categoriaActual = $this->categoria->getCategoriaById($id);
            $imagenVieja = $categoriaActual['imagen'] ?? null;
            $nuevaImagen = $imagenVieja;

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['imagen']['tmp_name'];
                $nombreOriginal = basename($_FILES['imagen']['name']);
                $nuevaImagen = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
                $destino = __DIR__ . "/../public/categorias/" . $nuevaImagen;
                move_uploaded_file($tmp, $destino);

                // borrar imagen vieja si existe y no es null
                if ($imagenVieja) {
                    $rutaVieja = __DIR__ . "/../public/categorias/" . $imagenVieja;
                    if (file_exists($rutaVieja))
                        unlink($rutaVieja);
                }
            }

            $this->categoria->actualizarCategoria($id, $descripcion, $color, $nuevaImagen);
        }

        header("Location: /paneleditor");
        exit;
    }

    public function rechazarReporte()
    {
        $id = $_POST['id_reporte'];
        // La acción "Quitar Reporte" debe borrar el registro
        $this->model->eliminarReporte($id);

        header("Location: /paneleditor/verReportes");
        exit;
    }
}
