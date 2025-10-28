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
            $categoria_id = $_POST["categoria_id"];
            $dificultad_id = $_POST["dificultad_id"];
            $respuesta_correcta = $_POST["respuesta_correcta"];
            $respuesta_incorrecta1 = $_POST["respuesta_incorrecta1"];
            $respuesta_incorrecta2 = $_POST["respuesta_incorrecta2"];
            $respuesta_incorrecta3 = $_POST["respuesta_incorrecta3"];

            $this->model->insertarPregunta(
                $descripcion,
                $categoria_id,
                $dificultad_id,
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
        $categoria_id = $_POST["categoria_id"];
        $dificultad_id = $_POST["dificultad_id"];
        $aprobada = isset($_POST["aprobada"]) ? 1 : 0;
        $respuesta_correcta = $_POST["respuesta_correcta"];
        $respuesta_incorrecta1 = $_POST["respuesta_incorrecta1"];
        $respuesta_incorrecta2 = $_POST["respuesta_incorrecta2"];
        $respuesta_incorrecta3 = $_POST["respuesta_incorrecta3"];

        $this->model->updatePreguntaConRespuestas(
            $id,
            $descripcion,
            $categoria_id,
            $dificultad_id,
            $aprobada,
            $respuesta_correcta,
            $respuesta_incorrecta1,
            $respuesta_incorrecta2,
            $respuesta_incorrecta3
        );

        header("Location: /paneleditor");
        exit;
    }
}
