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

// ===== Métodos para reportes =====
public function obtenerReportesPendientes()
{
    // Devolvemos un array asociativo con info básica del reporte y la pregunta
    $query = "SELECT r.id AS id_reporte, p.descripcion AS pregunta
              FROM reporte r
              LEFT JOIN pregunta p ON r.pregunta_id = p.id";
    $res = $this->conexion->query($query);
    if ($res === false || !is_object($res)) {
        $errorMsg = isset($this->conexion->error) ? $this->conexion->error : 'Error desconocido';
        error_log("Error obtenerReportesPendientes: " . $errorMsg);
        return [];
    }
    return $res->fetch_all(MYSQLI_ASSOC);
}

public function obtenerReportePorId($id)
{
    $stmt = $this->conexion->prepare(
        "SELECT r.*, p.descripcion AS pregunta_descripcion
         FROM reporte r
         LEFT JOIN pregunta p ON r.pregunta_id = p.id
         WHERE r.id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) return null;

    // Obtener respuestas de la pregunta para mostrar
    $tablaResp = $this->detectRespuestasTable();
    $stmt2 = $this->conexion->prepare("SELECT * FROM " . $tablaResp . " WHERE id_pregunta = ? ORDER BY es_correcta DESC");
    $stmt2->bind_param("i", $data['pregunta_id']);
    $stmt2->execute();
    $respuestas = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();

    $data['respuestas'] = $respuestas;
    return $data;
}

public function marcarReporte($id, $estado)
{
    $allowed = ['aceptado', 'rechazado'];
    if (!in_array($estado, $allowed)) return false;

    $stmt = $this->conexion->prepare("UPDATE reporte SET estado = ?, actualizado_en = NOW() WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);
    $ok = $stmt->execute();
    if ($stmt->errno) {
        error_log("Error marcarReporte (id={$id}): " . $stmt->error);
    }
    $stmt->close();
    return $ok;
}

public function obtenerPreguntasSugeridas()
{
    $cols = $this->detectPreguntaColumns();
    $catCol = $cols['categoria'];
    $difCol = $cols['dificultad'];
    $query = "SELECT p.id, p.descripcion, p.aprobada, p." . $catCol . " AS id_categoria, p." . $difCol . " AS id_dificultad,
                 c.descripcion AS categoria, d.descripcion AS dificultad
          FROM pregunta p
          LEFT JOIN categoria c ON p." . $catCol . " = c.id
          LEFT JOIN dificultad d ON p." . $difCol . " = d.id
          WHERE p.aprobada = 2";
    $result = $this->conexion->query($query);
    if ($result === false || !is_object($result)) {
        error_log("Error en obtenerPreguntasSugeridas: " . $this->conexion->error);
        return [];
    }
    $sugerencias = $result->fetch_all(MYSQLI_ASSOC);
    error_log("Sugerencias obtenidas: " . print_r($sugerencias, true));
    return $sugerencias;
}

    public function aceptarSugerencia($id)
    {
        $stmt = $this->conexion->prepare("UPDATE pregunta SET aprobada = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function rechazarSugerencia($id)
    {
        $stmt = $this->conexion->prepare("UPDATE pregunta SET aprobada = 3 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Inserta una sugerencia de pregunta (aprobada = 2) y sus respuestas asociadas
     */
    public function insertarSugerencia($descripcion, $id_categoria, $id_dificultad, $respuesta_correcta, $respuesta_incorrecta1, $respuesta_incorrecta2, $respuesta_incorrecta3)
    {
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];
        $difCol = $cols['dificultad'];

        try {
            $stmt = $this->conexion->prepare(
                "INSERT INTO pregunta (descripcion, aprobada, " . $catCol . ", " . $difCol . ") VALUES (?, 2, ?, ?)"
            );
            $stmt->bind_param("sii", $descripcion, $id_categoria, $id_dificultad);
            $stmt->execute();
            $id_pregunta = $stmt->insert_id;
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error insertarSugerencia INSERT pregunta: " . $e->getMessage());
            return false;
        }

        // Insertar respuestas
        $tablaResp = $this->detectRespuestasTable();
        $stmtResp = null;
        try {
            $stmtResp = $this->conexion->prepare("INSERT INTO " . $tablaResp . " (descripcion, es_correcta, id_pregunta) VALUES (?, ?, ?)");
        } catch (Exception $e) {
            error_log("Fallo prepare insertar respuestas sugerencia con tabla $tablaResp: " . $e->getMessage());
            $alt = ($tablaResp === 'respuesta') ? 'respuestas' : 'respuesta';
            try {
                $stmtResp = $this->conexion->prepare("INSERT INTO " . $alt . " (descripcion, es_correcta, id_pregunta) VALUES (?, ?, ?)");
                $tablaResp = $alt;
            } catch (Exception $e2) {
                error_log("Fallo prepare alternativo insertar respuestas sugerencia con tabla $alt: " . $e2->getMessage());
                $stmtResp = null;
            }
        }

        if ($stmtResp) {
            $es_correcta = 1;
            $stmtResp->bind_param("sii", $respuesta_correcta, $es_correcta, $id_pregunta);
            $stmtResp->execute();

            $es_correcta = 0;
            $stmtResp->bind_param("sii", $respuesta_incorrecta1, $es_correcta, $id_pregunta);
            $stmtResp->execute();
            $stmtResp->bind_param("sii", $respuesta_incorrecta2, $es_correcta, $id_pregunta);
            $stmtResp->execute();
            $stmtResp->bind_param("sii", $respuesta_incorrecta3, $es_correcta, $id_pregunta);
            $stmtResp->execute();
            $stmtResp->close();
        } else {
            error_log('No se pudo preparar statement para insertar respuestas de sugerencia; omitiendo inserción.');
        }

        return $id_pregunta;
    }

    /**
     * Inserta un reporte asociado a una pregunta.
     * Intentará usar columna id_usuario si se proporciona, y fallará de forma segura si la tabla tiene diferente esquema.
     */
    public function insertarReporte($pregunta_id, $descripcion, $id_usuario = null)
    {
        // Intentar inserción con campos comunes
        try {
            if ($id_usuario !== null) {
                $stmt = $this->conexion->prepare("INSERT INTO reporte (pregunta_id, descripcion, id_usuario, estado, creado_en) VALUES (?, ?, ?, 'pendiente', NOW())");
                $stmt->bind_param("isi", $pregunta_id, $descripcion, $id_usuario);
            } else {
                $stmt = $this->conexion->prepare("INSERT INTO reporte (pregunta_id, descripcion, estado, creado_en) VALUES (?, ?, 'pendiente', NOW())");
                $stmt->bind_param("is", $pregunta_id, $descripcion);
            }
            $ok = $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            return $ok ? $id : false;
        } catch (Exception $e) {
            // fallback a un INSERT minimal por si la tabla no tiene las columnas esperadas
            error_log('insertarReporte fallo inserción completa: ' . $e->getMessage());
            try {
                $stmt2 = $this->conexion->prepare("INSERT INTO reporte (pregunta_id, descripcion) VALUES (?, ?)");
                $stmt2->bind_param("is", $pregunta_id, $descripcion);
                $ok2 = $stmt2->execute();
                $id2 = $stmt2->insert_id;
                $stmt2->close();
                return $ok2 ? $id2 : false;
            } catch (Exception $e2) {
                error_log('insertarReporte fallback falló: ' . $e2->getMessage());
                return false;
            }
        }
    }


}
