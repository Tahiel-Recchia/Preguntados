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
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], [2, 3])) {
            header('HTTP/1.1 403 Forbidden');
            // redirigir a menú usando URL explícita para entornos sin .htaccess
            header('Location: /index.php?controller=menu');
            exit;
        }
    }
    public function base()
    {
        $this->requireEditor();
        $data = [];
        // Proveer datos de sesión a la vista para que el navbar tenga la misma estructura que en otras pantallas
        if (isset($_SESSION['user_id'])) {
            $data['sesion'] = [
                'id' => $_SESSION['user_id'],
                'nombreDeUsuario' => $_SESSION['nombreDeUsuario'] ?? null,
                'fotoDePerfil' => $_SESSION['fotoDePerfil'] ?? '/public/placeholder.png',
                'rol' => $_SESSION['rol'] ?? null
            ];
        }
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        // Podés traer info adicional del modelo
        $data["preguntas"] = $this->model->obtenerPreguntas();
        $this->renderer->render('panelEditor', $data); // crear vista
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
                $respuesta_incorrecta3
            );

            header("Location: /index.php?controller=paneleditor");
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
        header("Location: /index.php?controller=paneleditor");
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

        header("Location: /index.php?controller=paneleditor");
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
}
