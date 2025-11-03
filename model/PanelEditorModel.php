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
        // Detectar nombres de columna en la tabla 'pregunta' (puede ser id_categoria o categoria_id según migraciones)
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];
        $difCol = $cols['dificultad'];

        $query = "SELECT p.id, p.descripcion, p.aprobada, p." . $catCol . " AS id_categoria, p." . $difCol . " AS id_dificultad,
                     c.descripcion AS categoria, 
                     d.descripcion AS dificultad
              FROM pregunta p
              LEFT JOIN categoria c ON p." . $catCol . " = c.id
              LEFT JOIN dificultad d ON p." . $difCol . " = d.id";

        return $this->conexion->query($query);
    }

    public function obtenerPreguntaConRespuestas($id)
    {
        // Obtener la pregunta
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];
        $difCol = $cols['dificultad'];

        $query = "SELECT p.*, c.descripcion AS categoria_nombre, d.descripcion AS dificultad_nombre
         FROM pregunta p
         LEFT JOIN categoria c ON p." . $catCol . " = c.id
         LEFT JOIN dificultad d ON p." . $difCol . " = d.id
         WHERE p.id = ?";

        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $pregunta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Obtener las respuestas
        $query = "SELECT * FROM respuesta WHERE id_pregunta = ? ORDER BY es_correcta DESC";
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
        $id_categoria,
        $id_dificultad,
        $respuesta_correcta,
        $respuesta_incorrecta1,
        $respuesta_incorrecta2,
        $respuesta_incorrecta3
    ) {
        // === INSERTAR PREGUNTA ===
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];
        $difCol = $cols['dificultad'];

        $stmt = $this->conexion->prepare(
            "INSERT INTO pregunta (descripcion, aprobada, " . $catCol . ", " . $difCol . ") 
        VALUES (?, 1, ?, ?)"
        );
        $stmt->bind_param("sii", $descripcion, $id_categoria, $id_dificultad);
        $stmt->execute();

        // Obtener el ID de la última pregunta insertada
        $id_pregunta = $stmt->insert_id;
        $stmt->close();

        // === INSERTAR RESPUESTAS ===
        $stmtResp = $this->conexion->prepare(
            "INSERT INTO respuesta (descripcion, es_correcta, id_pregunta) VALUES (?, ?, ?)"
        );

        // Respuesta correcta
        $es_correcta = 1;
        $stmtResp->bind_param("sii", $respuesta_correcta, $es_correcta, $id_pregunta);
        $stmtResp->execute();

        // Respuestas incorrectas
        $es_orrecta = 0;

        $stmtResp->bind_param("sii", $respuesta_incorrecta1, $es_correcta, $id_pregunta);
        $stmtResp->execute();

        $stmtResp->bind_param("sii", $respuesta_incorrecta2, $es_correcta, $id_pregunta);
        $stmtResp->execute();

        $stmtResp->bind_param("sii", $respuesta_incorrecta3, $es_correcta, $id_pregunta);
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

    public function updatePreguntaConRespuestas($id, $descripcion, $id_categoria, $id_dificultad, $aprobada, $respCorrecta, $resp1, $resp2, $resp3)
    {
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];
        $difCol = $cols['dificultad'];

        $stmt = $this->conexion->prepare("\
        UPDATE pregunta 
        SET descripcion = ?, " . $catCol . " = ?, " . $difCol . " = ?, aprobada = ?
        WHERE id = ?
    ");
        $stmt->bind_param("siiii", $descripcion, $id_categoria, $id_dificultad, $aprobada, $id);
        $stmt->execute();

        // reemplazar respuestas
        $this->conexion->query("DELETE FROM respuesta WHERE id_pregunta = $id");

        $respuestas = [
            ['texto' => $respCorrecta, 'es_correcta' => 1],
            ['texto' => $resp1, 'es_correcta' => 0],
            ['texto' => $resp2, 'es_correcta' => 0],
            ['texto' => $resp3, 'es_correcta' => 0]
        ];

        $stmtResp = $this->conexion->prepare("
        INSERT INTO respuesta (id_pregunta, descripcion, es_correcta)
        VALUES (?, ?, ?)
    ");

        foreach ($respuestas as $r) {
            $stmtResp->bind_param("isi", $id, $r['texto'], $r['es_correcta']);
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

    /**
     * Detecta los nombres reales de las columnas de categoría/dificultad en la tabla 'pregunta'
     * Devuelve un array con keys 'categoria' y 'dificultad' que contienen el nombre de la columna.
     */
    private function detectPreguntaColumns()
    {
        $default = ['categoria' => 'id_categoria', 'dificultad' => 'id_dificultad'];
        try {
            $cols = $this->conexion->query("SHOW COLUMNS FROM pregunta");
        } catch (Exception $e) {
            // Si falla, devolvemos valores por defecto
            return $default;
        }

        if (empty($cols) || !is_array($cols)) {
            return $default;
        }

        $fields = array_column($cols, 'Field');

        $categoria = in_array('id_categoria', $fields) ? 'id_categoria' : (in_array('categoria_id', $fields) ? 'categoria_id' : 'id_categoria');
        $dificultad = in_array('id_dificultad', $fields) ? 'id_dificultad' : (in_array('dificultad_id', $fields) ? 'dificultad_id' : 'id_dificultad');

        return ['categoria' => $categoria, 'dificultad' => $dificultad];
    }

}
