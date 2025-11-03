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

        // Normalizar nombres de columnas para la vista/JS: siempre devolver categoria_id y dificultad_id
        if ($pregunta) {
            // Las posibles columnas detectadas por detectPreguntaColumns pueden ser 'id_categoria' o 'categoria_id'
            $cols = $this->detectPreguntaColumns();
            $catCol = $cols['categoria'];
            $difCol = $cols['dificultad'];

            // Mapear al formato esperado por el frontend
            if (isset($pregunta[$catCol]) && !isset($pregunta['categoria_id'])) {
                $pregunta['categoria_id'] = $pregunta[$catCol];
            }
            if (isset($pregunta[$difCol]) && !isset($pregunta['dificultad_id'])) {
                $pregunta['dificultad_id'] = $pregunta[$difCol];
            }
        }

        // Obtener las respuestas
    $tablaResp = $this->detectRespuestasTable();
    $query = "SELECT * FROM " . $tablaResp . " WHERE id_pregunta = ? ORDER BY es_correcta DESC";
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
        $tablaResp = $this->detectRespuestasTable();
        $stmtResp = null;
        try {
            $stmtResp = $this->conexion->prepare("INSERT INTO " . $tablaResp . " (descripcion, es_correcta, id_pregunta) VALUES (?, ?, ?)");
        } catch (Exception $e) {
            error_log("Fallo prepare insertar respuestas con tabla $tablaResp: " . $e->getMessage());
            // intentar la otra variación de nombre de tabla
            $alt = ($tablaResp === 'respuesta') ? 'respuestas' : 'respuesta';
            try {
                $stmtResp = $this->conexion->prepare("INSERT INTO " . $alt . " (descripcion, es_correcta, id_pregunta) VALUES (?, ?, ?)");
                $tablaResp = $alt;
            } catch (Exception $e2) {
                error_log("Fallo prepare alternativo insertar respuestas con tabla $alt: " . $e2->getMessage());
                $stmtResp = null;
            }
        }

        if ($stmtResp) {
            // Respuesta correcta
            $es_correcta = 1;
            $stmtResp->bind_param("sii", $respuesta_correcta, $es_correcta, $id_pregunta);
            $stmtResp->execute();

            // Respuestas incorrectas
            $es_correcta = 0;

            $stmtResp->bind_param("sii", $respuesta_incorrecta1, $es_correcta, $id_pregunta);
            $stmtResp->execute();

            $stmtResp->bind_param("sii", $respuesta_incorrecta2, $es_correcta, $id_pregunta);
            $stmtResp->execute();

            $stmtResp->bind_param("sii", $respuesta_incorrecta3, $es_correcta, $id_pregunta);
            $stmtResp->execute();

            $stmtResp->close();
        } else {
            error_log('No se pudo preparar statement para insertar respuestas; omitiendo inserción de respuestas.');
        }
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

        $stmt = $this->conexion->prepare("
        UPDATE pregunta 
        SET descripcion = ?, " . $catCol . " = ?, " . $difCol . " = ?, aprobada = ?
        WHERE id = ?
    ");
            try {
                $stmt->bind_param("siiii", $descripcion, $id_categoria, $id_dificultad, $aprobada, $id);
                $ok = $stmt->execute();
                if ($stmt->errno) {
                    error_log("Error en UPDATE pregunta (id={$id}): " . $stmt->error);
                } else {
                    error_log("UPDATE pregunta ejecutado (id={$id}), filas afectadas: " . $stmt->affected_rows);
                }
            } catch (Exception $e) {
                error_log("Excepción al ejecutar UPDATE pregunta (id={$id}): " . $e->getMessage());
            }

        // reemplazar respuestas
    $tablaResp = $this->detectRespuestasTable();
        try {
            $res = $this->conexion->query("DELETE FROM " . $tablaResp . " WHERE id_pregunta = $id");
            if ($res === false) {
                error_log("Fallo DELETE en tabla $tablaResp (id={$id}): " . $this->conexion->error);
                throw new Exception($this->conexion->error);
            } else {
                error_log("DELETE ejecutado en $tablaResp para id_pregunta={$id}");
            }
        } catch (Exception $e) {
            error_log("Fallo DELETE en tabla $tablaResp: " . $e->getMessage());
            $alt = ($tablaResp === 'respuesta') ? 'respuestas' : 'respuesta';
            try {
                $res2 = $this->conexion->query("DELETE FROM " . $alt . " WHERE id_pregunta = $id");
                if ($res2 === false) {
                    error_log("Fallo DELETE alternativo en tabla $alt (id={$id}): " . $this->conexion->error);
                } else {
                    $tablaResp = $alt;
                    error_log("DELETE alternativo ejecutado en $alt para id_pregunta={$id}");
                }
            } catch (Exception $e2) {
                error_log("Fallo DELETE alternativo en tabla $alt: " . $e2->getMessage());
                // seguir, intentaremos insertar y fallará o se omitirá
            }

        }

        $respuestas = [
            ['texto' => $respCorrecta, 'es_correcta' => 1],
            ['texto' => $resp1, 'es_correcta' => 0],
            ['texto' => $resp2, 'es_correcta' => 0],
            ['texto' => $resp3, 'es_correcta' => 0]
        ];

        $tablaResp = $this->detectRespuestasTable();
        $stmtResp = null;
        try {
            $stmtResp = $this->conexion->prepare("INSERT INTO " . $tablaResp . " (id_pregunta, descripcion, es_correcta) VALUES (?, ?, ?)");
        } catch (Exception $e) {
            error_log("Fallo prepare insertar respuestas (update) con tabla $tablaResp: " . $e->getMessage());
            $alt = ($tablaResp === 'respuesta') ? 'respuestas' : 'respuesta';
            try {
                $stmtResp = $this->conexion->prepare("INSERT INTO " . $alt . " (id_pregunta, descripcion, es_correcta) VALUES (?, ?, ?)");
                $tablaResp = $alt;
            } catch (Exception $e2) {
                error_log("Fallo prepare alternativo insertar respuestas (update) con tabla $alt: " . $e2->getMessage());
                $stmtResp = null;
            }
        }

        if ($stmtResp) {
                foreach ($respuestas as $r) {
                    try {
                        $stmtResp->bind_param("isi", $id, $r['texto'], $r['es_correcta']);
                        $stmtResp->execute();
                        if ($stmtResp->errno) {
                            error_log("Error INSERT respuesta (pregunta_id={$id}): " . $stmtResp->error);
                        }
                    } catch (Exception $e) {
                        error_log("Excepción INSERT respuesta (pregunta_id={$id}): " . $e->getMessage());
                    }
                }
                $stmtResp->close();
                error_log("Inserción de respuestas completa para pregunta_id={$id} en tabla {$tablaResp}");
            } else {
                error_log('No se pudo preparar statement para insertar respuestas (update); omitiendo inserción.');
        }
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

    /**
     * Detecta el nombre de la tabla de respuestas: 'respuesta' o 'respuestas'
     * Devuelve el nombre encontrado o 'respuesta' por defecto.
     */
    private function detectRespuestasTable()
    {
        // Intentar 'respuesta' primero
        try {
            $res = $this->conexion->query("SHOW TABLES LIKE 'respuesta'");
        } catch (Exception $e) {
            $res = [];
        }
        if (!empty($res)) {
            return 'respuesta';
        }

        // Probar 'respuestas'
        try {
            $res2 = $this->conexion->query("SHOW TABLES LIKE 'respuestas'");
        } catch (Exception $e) {
            $res2 = [];
        }
        if (!empty($res2)) {
            return 'respuestas';
        }

        // fallback
        return 'respuesta';
    }

}
