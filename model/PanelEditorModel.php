<?php

class PanelEditorModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPreguntas($editorId = null)
    {
        // Detectar nombres de columna en la tabla 'pregunta' (puede ser id_categoria o categoria_id según migraciones)
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];
        $difCol = $cols['dificultad'];

        // Si existe una columna que indique el creador/editor, filtramos por ella
        $creatorCol = $this->detectCreatorColumn();

        if ($creatorCol && $editorId !== null) {
                 $query = "SELECT p.id, p.descripcion, p.aprobada, p." . $catCol . " AS id_categoria, p." . $difCol . " AS id_dificultad,
                        c.descripcion AS categoria, d.descripcion AS dificultad
                    FROM pregunta p
                    LEFT JOIN categoria c ON p." . $catCol . " = c.id
                    LEFT JOIN dificultad d ON p." . $difCol . " = d.id
                    WHERE p." . $creatorCol . " = ?
                    AND p.id NOT IN (SELECT pregunta_id FROM reporte)";

            $stmt = $this->conexion->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $editorId);
                $stmt->execute();
                $res = $stmt->get_result();
                $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();
                return $rows;
            } else {
                error_log('PanelEditorModel::obtenerPreguntas - fallo prepare, query: ' . $this->conexion->error);
                return [];
            }
        }

        // Fallback: si no hay columna creador o no nos pasaron editorId, devolvemos solo preguntas aprobadas
         $query = "SELECT p.id, p.descripcion, p.aprobada, p." . $catCol . " AS id_categoria, p." . $difCol . " AS id_dificultad,
                c.descripcion AS categoria, d.descripcion AS dificultad
            FROM pregunta p
            LEFT JOIN categoria c ON p." . $catCol . " = c.id
            LEFT JOIN dificultad d ON p." . $difCol . " = d.id
            WHERE p.aprobada = 1
            AND p.id NOT IN (SELECT pregunta_id FROM reporte)";

        $res = $this->conexion->query($query);
        if ($res === false || !is_object($res)) {
            error_log('PanelEditorModel::obtenerPreguntas fallback error: ' . $this->conexion->error);
            return [];
        }
        return $res->fetch_all(MYSQLI_ASSOC);
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
        $respuesta_correcta,
        $respuesta_incorrecta1,
        $respuesta_incorrecta2,
        $respuesta_incorrecta3,
        $creadorId = null
    ) {
        // === INSERTAR PREGUNTA ===
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];

        // Si existe columna creador y se proporcionó, incluirla en el INSERT
        $creatorCol = $this->detectCreatorColumn();
        if ($creatorCol && $creadorId !== null) {
            $sql = "INSERT INTO pregunta (descripcion, aprobada, " . $catCol  . ", id_dificultad, " . $creatorCol . ") VALUES (?, 1, ?, 2, ?)";
            $stmt = $this->conexion->prepare($sql);
            if ($stmt) $stmt->bind_param("sii", $descripcion, $id_categoria, $creadorId);
        } else {
            $stmt = $this->conexion->prepare(
                "INSERT INTO pregunta (descripcion, aprobada, " . $catCol . ", id_dificultad)
            VALUES (?, 1, ?, 2)"
            );
            if ($stmt) $stmt->bind_param("si", $descripcion, $id_categoria);
        }
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

    public function updatePreguntaConRespuestas($id, $descripcion, $id_categoria, $aprobada, $respCorrecta, $resp1, $resp2, $resp3)
    {
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];

        $stmt = $this->conexion->prepare("
        UPDATE pregunta 
        SET descripcion = ?, " . $catCol . " = ?, aprobada = ?
        WHERE id = ?
    ");
            try {
                $stmt->bind_param("siii", $descripcion, $id_categoria, $aprobada, $id);
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
        $stmt = $this->conexion->prepare("DELETE FROM reporte WHERE pregunta_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conexion->prepare("DELETE FROM usuario_pregunta WHERE pregunta_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conexion->prepare("DELETE FROM respuesta WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

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

    /**
     * Detecta posible columna que almacene el id del creador/editor en la tabla 'pregunta'.
     * Devuelve el nombre de la columna o null si no se encuentra.
     */
    private function detectCreatorColumn()
    {
        try {
            $cols = $this->conexion->query("SHOW COLUMNS FROM pregunta");
        } catch (Exception $e) {
            return null;
        }

        if (empty($cols) || !is_array($cols)) return null;

        $fields = array_column($cols, 'Field');

        $candidates = ['creador', 'creador_id', 'id_creador', 'id_usuario', 'usuario_id', 'autor_id', 'id_autor', 'editor_id', 'id_editor'];
        foreach ($candidates as $cand) {
            if (in_array($cand, $fields)) return $cand;
        }

        return null;
    }

// ===== Métodos para reportes =====
public function obtenerReportesPendientes()
{
    // Devolvemos un array asociativo con info básica del reporte y la pregunta
    $query = "SELECT r.id AS id_reporte, r.pregunta_id, r.descripcion AS descripcion, p.descripcion AS pregunta
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

    public function aceptarSugerencia($id, $editorId = null)
    {
        $creatorCol = $this->detectCreatorColumn();
        if ($creatorCol && $editorId !== null) {
            $sql = "UPDATE pregunta SET aprobada = 1, " . $creatorCol . " = ? WHERE id = ?";
            $stmt = $this->conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $editorId, $id);
                $stmt->execute();
                $stmt->close();
                return;
            }
        }

        // fallback: solo marcar aprobada
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
    public function insertarSugerencia($descripcion, $id_categoria, $respuesta_correcta, $respuesta_incorrecta1, $respuesta_incorrecta2, $respuesta_incorrecta3)
    {
        $cols = $this->detectPreguntaColumns();
        $catCol = $cols['categoria'];

        try {
            $stmt = $this->conexion->prepare(
                "INSERT INTO pregunta (descripcion, aprobada, " . $catCol . ", id_dificultad) VALUES (?, 2, ?, 2)"
            );
            $stmt->bind_param("si", $descripcion, $id_categoria);
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
        // Evitar duplicados: si la tabla tiene columna id_usuario, verificar por usuario y pregunta
        $tieneColUsuario = false;
        try {
            $cols = $this->conexion->query("SHOW COLUMNS FROM reporte");
            if ($cols && is_array($cols)) {
                $fields = array_column($cols, 'Field');
                $tieneColUsuario = in_array('id_usuario', $fields);
            }
        } catch (Exception $e) {
            // ignorar
        }
        if ($tieneColUsuario && $id_usuario !== null) {
            $stmtCheck = $this->conexion->prepare("SELECT 1 FROM reporte WHERE pregunta_id = ? AND id_usuario = ? LIMIT 1");
            if ($stmtCheck) {
                $stmtCheck->bind_param("ii", $pregunta_id, $id_usuario);
                $stmtCheck->execute();
                $rs = $stmtCheck->get_result();
                if ($rs && $rs->num_rows > 0) {
                    $stmtCheck->close();
                    return 'duplicate';
                }
                $stmtCheck->close();
            }
        } else {
            // Sin columna de usuario: prevenir al menos un duplicado total por pregunta
            $stmtCheck2 = $this->conexion->prepare("SELECT 1 FROM reporte WHERE pregunta_id = ? LIMIT 1");
            if ($stmtCheck2) {
                $stmtCheck2->bind_param("i", $pregunta_id);
                $stmtCheck2->execute();
                $rs2 = $stmtCheck2->get_result();
                if ($rs2 && $rs2->num_rows > 0) {
                    $stmtCheck2->close();
                    return 'duplicate';
                }
                $stmtCheck2->close();
            }
        }
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
            if (!$ok) {
                error_log('insertarReporte error execute: ' . $this->conexion->error);
            }
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
                if (!$ok2) {
                    error_log('insertarReporte fallback execute error: ' . $this->conexion->error);
                }
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
