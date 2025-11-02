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
        $query = "SELECT p.id, p.descripcion, p.aprobada, p.categoria_id, p.dificultad_id,
                     c.descripcion AS categoria, 
                     d.descripcion AS dificultad
              FROM pregunta p
              LEFT JOIN categoria c ON p.categoria_id = c.id
              LEFT JOIN dificultad d ON p.dificultad_id = d.id";

        $resultado = $this->conexion->query($query);
        return $resultado;
    }

    public function obtenerPreguntaConRespuestas($id)
    {
        // Obtener la pregunta
        $query = "SELECT p.*, c.descripcion AS categoria_nombre, d.descripcion AS dificultad_nombre
                 FROM pregunta p
                 LEFT JOIN categoria c ON p.categoria_id = c.id
                 LEFT JOIN dificultad d ON p.dificultad_id = d.id
                 WHERE p.id = ?";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $pregunta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Obtener las respuestas
        $query = "SELECT * FROM respuesta WHERE pregunta_id = ? ORDER BY esCorrecta DESC";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'pregunta' => $pregunta,
            'respuestas' => $respuestas
        ];
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
        $stmt = $this->conexion->prepare("SELECT * FROM pregunta WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function updatePreguntaConRespuestas($id, $descripcion, $categoria_id, $dificultad_id, $aprobada, $respCorrecta, $resp1, $resp2, $resp3)
    {
        $stmt = $this->conexion->prepare("
        UPDATE pregunta 
        SET descripcion = ?, categoria_id = ?, dificultad_id = ?, aprobada = ?
        WHERE id = ?
    ");
        $stmt->bind_param("siiii", $descripcion, $categoria_id, $dificultad_id, $aprobada, $id);
        $stmt->execute();

        // reemplazar respuestas
        $this->conexion->query("DELETE FROM respuesta WHERE pregunta_id = $id");

        $respuestas = [
            ['texto' => $respCorrecta, 'esCorrecta' => 1],
            ['texto' => $resp1, 'esCorrecta' => 0],
            ['texto' => $resp2, 'esCorrecta' => 0],
            ['texto' => $resp3, 'esCorrecta' => 0]
        ];

        $stmtResp = $this->conexion->prepare("
        INSERT INTO respuesta (pregunta_id, descripcion, esCorrecta)
        VALUES (?, ?, ?)
    ");

        foreach ($respuestas as $r) {
            $stmtResp->bind_param("isi", $id, $r['texto'], $r['esCorrecta']);
            $stmtResp->execute();
        }
        $stmtResp->close();
    }

    public function deletePregunta($id)
    {
        $stmt = $this->conexion->prepare("DELETE FROM pregunta WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $filasAfectadas = $stmt->affected_rows;
        $stmt->close();
        return $filasAfectadas > 0;
    }

}
