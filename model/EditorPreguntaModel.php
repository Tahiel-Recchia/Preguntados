<?php

class EditorPreguntaModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPreguntaPorId($id)
    {
        $stmt = $this->conexion->prepare("SELECT * FROM pregunta WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }
    public function obtenerRespuestasPorPregunta($id)
    {
        // Detectar nombre de tabla: 'respuesta' o 'respuestas'
        $tabla = 'respuesta';
        try {
            $check = $this->conexion->query("SHOW TABLES LIKE 'respuesta'");
        } catch (Exception $e) {
            $check = [];
        }
        if (empty($check)) {
            try {
                $check2 = $this->conexion->query("SHOW TABLES LIKE 'respuestas'");
            } catch (Exception $e) {
                $check2 = [];
            }
            if (!empty($check2)) {
                $tabla = 'respuestas';
            }
        }

        $sql = "SELECT * FROM " . $tabla . " WHERE id_pregunta = ?";
        $resultado = [];
        try {
            // Intentar prepared statement primero
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            // Si prepare falla (por ejemplo tabla inexistente o permisos), intentar un query directo seguro
            error_log("Fallo prepare en EditorPreguntaModel: " . $e->getMessage());
            try {
                $idInt = intval($id);
                $resultado = $this->conexion->query("SELECT * FROM " . $tabla . " WHERE id_pregunta = " . $idInt);
                // Database::query devuelve un array asociativo o lanza excepción
                if (!is_array($resultado)) {
                    $resultado = [];
                }
            } catch (Exception $e2) {
                error_log("Fallo fallback query en EditorPreguntaModel: " . $e2->getMessage());
                $resultado = [];
            }
        }

        $respuestas = [
            'correcta' => null,
            'incorrectas' => []
        ];

        foreach ($resultado as $r) {
            if ($r['es_correcta'] == 1) {
                $respuestas['correcta'] = $r;
            } else {
                $respuestas['incorrectas'][] = $r;
            }
        }

        return $respuestas;
    }


    

}