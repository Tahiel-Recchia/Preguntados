<!-- 
class EditorModel {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // Crear pregunta (retorna id insertado)
    public function createQuestion($categoriaId, $pregunta, $opciones, $correcta, $creatorId) {
        $sql = "INSERT INTO preguntas (categoria_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, correcta, creador_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssssis", $categoriaId, $pregunta, $opciones['A'], $opciones['B'], $opciones['C'], $opciones['D'], $correcta, $creatorId);
        if (!$stmt->execute()) {
            throw new Exception("Error al crear pregunta: " . $stmt->error);
        }
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    // Actualizar pregunta
    public function updateQuestion($questionId, $categoriaId, $pregunta, $opciones, $correcta) {
        $sql = "UPDATE preguntas SET categoria_id = ?, pregunta = ?, opcion_a = ?, opcion_b = ?, opcion_c = ?, opcion_d = ?, correcta = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssssii", $categoriaId, $pregunta, $opciones['A'], $opciones['B'], $opciones['C'], $opciones['D'], $correcta, $questionId);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar pregunta: " . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    // "Eliminar" pregunta (marcar como eliminada) o borrado real: aquí hacemos soft delete (flag)
    public function softDeleteQuestion($questionId, $reviewerId) {
        $sql = "UPDATE preguntas SET deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $reviewerId, $questionId);
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar pregunta: " . $stmt->error);
        }
        $stmt->close();
        return true;
    }

    // Restaurar pregunta (cuando se rechaza el reporte)
    public function restoreQuestion($questionId) {
        $sql = "UPDATE preguntas SET deleted = 0, deleted_by = NULL, deleted_at = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $questionId);
        if (!$stmt->execute()) {
            throw new Exception("Error al restaurar pregunta: " . $stmt->error);
        }
        $stmt->close();
        return true;
    }

    // Obtener reportes pendientes
    public function getPendingReports() {
        $sql = "SELECT qr.*, p.pregunta, p.id as question_id, u.nombreDeUsuario as reporter 
                FROM question_reports qr
                LEFT JOIN preguntas p ON qr.question_id = p.id
                LEFT JOIN usuarios u ON qr.reporter_id = u.id
                WHERE qr.status = 'pending'
                ORDER BY qr.created_at DESC";
        $res = $this->db->query($sql);
        return $res;
    }

    // Marcar reporte aceptado/rechazado
    public function reviewReport($reportId, $action, $reviewerId) {
        // action = 'accepted' or 'rejected'
        // traemos el reporte para saber question_id
        $sqlGet = "SELECT question_id FROM question_reports WHERE id = ?";
        $stmt = $this->db->prepare($sqlGet);
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$result) throw new Exception("Reporte no encontrado.");

        $questionId = $result['question_id'];

        $sqlUpd = "UPDATE question_reports SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt2 = $this->db->prepare($sqlUpd);
        $stmt2->bind_param("sii", $action, $reviewerId, $reportId);
        if (!$stmt2->execute()) {
            throw new Exception("Error al actualizar reporte: " . $stmt2->error);
        }
        $stmt2->close();

        if ($action === 'accepted') {
            // eliminar (soft delete) la pregunta
            $this->softDeleteQuestion($questionId, $reviewerId);
        } else {
            // rejected -> restaurar (por si estaba marcada)
            $this->restoreQuestion($questionId);
        }
        return true;
    }

    // Sugerencias pendientes
    public function getPendingSuggestions() {
        $sql = "SELECT qs.*, u.nombreDeUsuario as submitter 
                FROM question_suggestions qs
                LEFT JOIN usuarios u ON qs.submitter_id = u.id
                WHERE qs.status = 'pending'
                ORDER BY qs.created_at DESC";
        return $this->db->query($sql);
    }

    // Aceptar sugerencia: inserta en preguntas y marca suggestion como accepted
    public function acceptSuggestion($suggestionId, $reviewerId) {
        // obtener la sugerencia
        $sqlGet = "SELECT * FROM question_suggestions WHERE id = ?";
        $stmt = $this->db->prepare($sqlGet);
        $stmt->bind_param("i", $suggestionId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$res) throw new Exception("Sugerencia no encontrada.");

        // insertar en preguntas (ajusta columnas si tu tabla difiere)
        $sqlIns = "INSERT INTO preguntas (categoria_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, correcta, creador_id, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt2 = $this->db->prepare($sqlIns);
        $stmt2->bind_param("isssssis", $res['categoria_id'], $res['pregunta'], $res['opcion_a'], $res['opcion_b'], $res['opcion_c'], $res['opcion_d'], $res['correcta'], $reviewerId);
        if (!$stmt2->execute()) {
            throw new Exception("Error al aceptar sugerencia: " . $stmt2->error);
        }
        $newQuestionId = $stmt2->insert_id;
        $stmt2->close();

        // actualizar suggestion status
        $sqlUpd = "UPDATE question_suggestions SET status = 'accepted', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt3 = $this->db->prepare($sqlUpd);
        $stmt3->bind_param("ii", $reviewerId, $suggestionId);
        if (!$stmt3->execute()) {
            throw new Exception("Error al marcar sugerencia: " . $stmt3->error);
        }
        $stmt3->close();

        return $newQuestionId;
    }

    // Rechazar sugerencia
    public function rejectSuggestion($suggestionId, $reviewerId) {
        $sql = "UPDATE question_suggestions SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $reviewerId, $suggestionId);
        if (!$stmt->execute()) {
            throw new Exception("Error al rechazar sugerencia: " . $stmt->error);
        }
        $stmt->close();
        return true;
    }
} -->
