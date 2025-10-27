<?php

class PanelEditorModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPreguntas()
    {
        $query = "SELECT p.id, p.descripcion, p.aprobada, 
                     c.descripcion AS categoria, 
                     d.descripcion AS dificultad
              FROM pregunta p
              LEFT JOIN categoria c ON p.categoria_id = c.id
              LEFT JOIN dificultad d ON p.dificultad_id = d.id";

        $resultado = $this->conexion->query($query); // devuelve array ya

        return $resultado; // ya es un array, no necesitas fetch_assoc()
    }

    public function insertarPregunta(
        $descripcion,
        $categoria_id,
        $dificultad_id,
        $respuesta_correcta,
        $respuesta_incorrecta1,
        $respuesta_incorrecta2,
        $respuesta_incorrecta3
    ) {
        // === INSERTAR PREGUNTA ===
        $stmt = $this->conexion->prepare(
            "INSERT INTO pregunta (descripcion, aprobada, categoria_id, dificultad_id) 
        VALUES (?, 1, ?, ?)"
        );
        $stmt->bind_param("sii", $descripcion, $categoria_id, $dificultad_id);
        $stmt->execute();

        // Obtener el ID de la última pregunta insertada
        $pregunta_id = $stmt->insert_id;
        $stmt->close();

        // === INSERTAR RESPUESTAS ===
        $stmtResp = $this->conexion->prepare(
            "INSERT INTO respuesta (descripcion, esCorrecta, pregunta_id) VALUES (?, ?, ?)"
        );

        // Respuesta correcta
        $esCorrecta = 1;
        $stmtResp->bind_param("sii", $respuesta_correcta, $esCorrecta, $pregunta_id);
        $stmtResp->execute();

        // Respuestas incorrectas
        $esCorrecta = 0;

        $stmtResp->bind_param("sii", $respuesta_incorrecta1, $esCorrecta, $pregunta_id);
        $stmtResp->execute();

        $stmtResp->bind_param("sii", $respuesta_incorrecta2, $esCorrecta, $pregunta_id);
        $stmtResp->execute();

        $stmtResp->bind_param("sii", $respuesta_incorrecta3, $esCorrecta, $pregunta_id);
        $stmtResp->execute();

        $stmtResp->close();
    }


    public function getPreguntaById($id)
    {
        $query = "SELECT * FROM pregunta WHERE id = $id";
        $resultado = $this->conexion->query($query);
        return $resultado->fetch_assoc();
    }

    public function updatePregunta($id, $descripcion, $categoria_id, $dificultad_id, $aprobada)
    {
        $descripcion = $this->conexion->real_escape_string($descripcion);
        $query = "UPDATE pregunta
              SET descripcion = '$descripcion',
                  categoria_id = $categoria_id,
                  dificultad_id = $dificultad_id,
                  aprobada = $aprobada
              WHERE id = $id";
        $this->conexion->query($query);
    }

    public function deletePregunta($id)
    {
        $query = "DELETE FROM pregunta WHERE id = $id";
        $this->conexion->query($query);
    }

}
