<?php
class UsuarioController
{

    private $renderer;
    private $model;

    public function __construct($renderer, $model)
    {
        $this->renderer = $renderer;
        $this->model = $model;
    }

    public function reportarPregunta()
    {
        error_log("=== reportarPregunta INICIO ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        error_log("SESSION user_id: " . ($_SESSION['user_id'] ?? 'no definido'));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("No es POST, redirigiendo");
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $pregunta_id = $_POST['pregunta_id'] ?? null;
        $descripcion = $_POST['descripcion'] ?? '';
        $id_usuario = $_SESSION['user_id'] ?? null;

        error_log("pregunta_id extraído: " . var_export($pregunta_id, true));
        error_log("descripcion extraída: " . var_export($descripcion, true));

        if (empty($pregunta_id) || empty($descripcion)) {
            error_log("FALTA pregunta_id o descripcion - pregunta_id: '{$pregunta_id}', descripcion: '{$descripcion}'");
            error_log("Redirigiendo por datos vacíos");
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        // Prevención duplicados vía sesión también (fallback si tabla no tiene id_usuario)
        if (!isset($_SESSION['reportes_realizados']) || !is_array($_SESSION['reportes_realizados'])) {
            $_SESSION['reportes_realizados'] = [];
        }
        if (in_array($pregunta_id, $_SESSION['reportes_realizados'])) {
            error_log("Reporte duplicado detectado en sesión para pregunta_id={$pregunta_id}");
            return $this->responderReporteJSON(['status' => 'duplicate', 'message' => 'Ya reportaste esta pregunta.']);
        }

        error_log("Intentando insertar reporte...");
        $res = $this->model->insertarReporte($pregunta_id, $descripcion, $id_usuario);
        if ($res === 'duplicate') {
            error_log("PanelEditorController::reportarPregunta - modelo retornó duplicate pregunta_id={$pregunta_id}, usuario={$id_usuario}");
            $_SESSION['reportes_realizados'][] = $pregunta_id; // asegurar consistencia
            return $this->responderReporteJSON(['status' => 'duplicate', 'message' => 'Ya existe un reporte para esta pregunta.']);
        } elseif ($res === false) {
            error_log("PanelEditorController::reportarPregunta - insertarReporte returned false. pregunta_id={$pregunta_id}, usuario={$id_usuario}, descripcion=" . substr($descripcion, 0, 200));
            return $this->responderReporteJSON(['status' => 'error', 'message' => 'Error al guardar el reporte']);
        } else {
            error_log("PanelEditorController::reportarPregunta - reporte creado EXITOSAMENTE id={$res}, pregunta_id={$pregunta_id}");
            $_SESSION['reportes_realizados'][] = $pregunta_id;
            return $this->responderReporteJSON(['status' => 'ok', 'id' => $res, 'message' => 'Reporte enviado']);
        }
    }

    public function guardarSugerencia()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
                exit;
            }
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $descripcion = $_POST['descripcion'] ?? '';
        $id_categoria = $_POST['id_categoria'] ?? null;
        $respuesta_correcta = $_POST['respuesta_correcta'] ?? '';
        $respuesta_incorrecta1 = $_POST['respuesta_incorrecta1'] ?? '';
        $respuesta_incorrecta2 = $_POST['respuesta_incorrecta2'] ?? '';
        $respuesta_incorrecta3 = $_POST['respuesta_incorrecta3'] ?? '';

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));


        if (empty($descripcion) || empty($respuesta_correcta) /* ... resto de condiciones ... */) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Campos incompletos']);
                exit;
            }
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }

        $id = $this->model->insertarSugerencia($descripcion, $id_categoria, $respuesta_correcta, $respuesta_incorrecta1, $respuesta_incorrecta2, $respuesta_incorrecta3);

        if ($isAjax) {
            if (ob_get_length())
                ob_clean();

            header('Content-Type: application/json');

            if ($id) {
                echo json_encode(['status' => 'ok', 'id' => $id, 'message' => 'Sugerencia enviada']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar la sugerencia']);
            }
            exit;
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

    private function responderReporteJSON($payload)
    {
        error_log("=== reportarPregunta FIN (JSON) ===");
        // Detectar si es petición AJAX/fetch; si no, fallback a redirect
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($payload);
            exit;
        }
        // Fallback: redirigir
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }

}

    ?>
