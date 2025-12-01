<?php
class UsuarioModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

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
?>