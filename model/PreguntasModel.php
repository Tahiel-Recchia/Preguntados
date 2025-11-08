<?php

class PreguntasModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPorCategoria($categoriaId, $idsExcluidos = [])
    {
        $sql_pregunta = "SELECT p.id, p.descripcion, c.descripcion AS categoria_nombre
                     FROM pregunta p
                     JOIN categoria c ON p.id_categoria = ?
                     WHERE p.aprobada = 1";

        if (!empty($idsExcluidos)) {
            // Creamos placeholders (?,?,?) para los IDs a excluir
            $placeholders = implode(',', array_fill(0, count($idsExcluidos), '?'));
            $sql_pregunta .= " AND p.id NOT IN ($placeholders)";
        }
        $sql_pregunta .= " ORDER BY RAND() LIMIT 1";

        $stmt_pregunta = $this->conexion->prepare($sql_pregunta);
        $tipos = "i";
        $params = [$categoriaId];

        if (!empty($idsExcluidos)) {
            foreach ($idsExcluidos as $id) {
                $tipos .= "i";
                $params[] = $id;
            }
        }
        $stmt_pregunta->bind_param($tipos, ...$params);
        $stmt_pregunta->execute();
        $resultado_pregunta = $stmt_pregunta->get_result();

        if ($resultado_pregunta->num_rows === 0) {
            return null;
        }

        $pregunta = $resultado_pregunta->fetch_assoc();
        $id_pregunta = $pregunta['id'];
        $stmt_pregunta->close();


    $tablaResp = $this->detectRespuestasTable();
    $sql_opciones = "SELECT descripcion, es_correcta FROM " . $tablaResp . " WHERE id_pregunta = ?";

        $stmt_opciones = $this->conexion->prepare($sql_opciones);
        $stmt_opciones->bind_param("i", $id_pregunta);
        $stmt_opciones->execute();
        $resultado_opciones = $stmt_opciones->get_result();
        $opciones = $resultado_opciones->fetch_all(MYSQLI_ASSOC);
        $stmt_opciones->close();

        shuffle($opciones);


        $datos_para_la_vista = [
            "pregunta"  => $pregunta["descripcion"],
            "categoria" => $pregunta["categoria_nombre"],
            "id_pregunta" => $pregunta["id"],
            "opciones"  => $opciones
        ];

        return $datos_para_la_vista;
    }

    public function getRespuestaCorrecta($idPregunta){
        $pregunta = $this->obtenerPorId($idPregunta);
        $opciones = $pregunta['opciones'];
        $respuestaCorrecta = "";
        foreach ($opciones as $opcion) {
            if ($opcion['es_correcta'] == 1) {
                $respuestaCorrecta = $opcion['descripcion'];
                break;
            }
        }
        return $respuestaCorrecta;
    }

    public function verificarRespuesta($idPregunta, $opcionDelUsuario){
        $respuestaCorrecta = $this->getRespuestaCorrecta($idPregunta);
        if($opcionDelUsuario === $respuestaCorrecta){
            return true;
        }
        return false;
    }

    public function obtenerPorId($idPreguntaBuscada) {
        $sql_pregunta = "SELECT id, descripcion
                    FROM pregunta
                     WHERE aprobada = 1 AND id = ?
                     ORDER BY RAND() 
                     LIMIT 1";

        $stmt_pregunta = $this->conexion->prepare($sql_pregunta);
        $stmt_pregunta->bind_param("i", $idPreguntaBuscada);
        $stmt_pregunta->execute();
        $resultado_pregunta = $stmt_pregunta->get_result();

        if ($resultado_pregunta->num_rows === 0) {
            return null;
        }

        $pregunta = $resultado_pregunta->fetch_assoc();
        $id_pregunta = $pregunta['id'];
        $stmt_pregunta->close();


    $tablaResp = $this->detectRespuestasTable();
    $sql_opciones = "SELECT descripcion, es_correcta FROM " . $tablaResp . " WHERE id_pregunta = ?";

        $stmt_opciones = $this->conexion->prepare($sql_opciones);
        $stmt_opciones->bind_param("i", $id_pregunta);
        $stmt_opciones->execute();
        $resultado_opciones = $stmt_opciones->get_result();
        $opciones = $resultado_opciones->fetch_all(MYSQLI_ASSOC);
        $stmt_opciones->close();

        shuffle($opciones);


        $datos_para_la_vista = [
            "pregunta"  => $pregunta["descripcion"],
            "id_pregunta" => $pregunta["id"],
            "opciones"  => $opciones
        ];

        return $datos_para_la_vista;
    }

    /**
     * Detecta el nombre de la tabla de respuestas: 'respuesta' o 'respuestas'
     */
    private function detectRespuestasTable()
    {
        try {
            $res = $this->conexion->query("SHOW TABLES LIKE 'respuesta'");
        } catch (Exception $e) {
            $res = [];
        }
        if (!empty($res)) {
            return 'respuesta';
        }
        try {
            $res2 = $this->conexion->query("SHOW TABLES LIKE 'respuestas'");
        } catch (Exception $e) {
            $res2 = [];
        }
        if (!empty($res2)) {
            return 'respuestas';
        }
        return 'respuesta';
    }

    public function getHoraEnvio(){
        return new DateTime();

    }
}