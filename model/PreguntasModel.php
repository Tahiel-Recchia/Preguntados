<?php

class PreguntasModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPorCategoria($categoriaId, $idsExcluidos = [], $nivelUsuario)
    {
        $dificultadDeseada = $this->calcularDificultad($nivelUsuario);
        $pregunta = $this->buscarPreguntaEnBD($categoriaId, $idsExcluidos, $dificultadDeseada);
        if (!$pregunta) {
            $pregunta = $this->buscarPreguntaEnBD($categoriaId, $idsExcluidos, null);
        }


        if (!$pregunta) {
            return null;
        }

        $opciones = $this->obtenerOpciones($pregunta['id']);

        return [
            "pregunta"  => $pregunta["descripcion"],
            "categoria" => $pregunta["categoria_nombre"],
            "id_pregunta" => $pregunta["id"],
            "opciones"  => $opciones
        ];
    }



    private function calcularDificultad($nivel)
    {
        if ($nivel <= 0.33) return 1;
        if ($nivel <= 0.66) return 2;
        return 3;
    }

    private function buscarPreguntaEnBD($categoriaId, $idsExcluidos, $dificultad = null)
    {
        $sql = "SELECT p.id, p.descripcion, c.descripcion AS categoria_nombre
            FROM pregunta p
            JOIN categoria c ON p.id_categoria = c.id
            WHERE p.aprobada = 1 AND p.id_categoria = ?";

        $params = [$categoriaId];
        $types = "i";

        if ($dificultad !== null) {
            $sql .= " AND p.id_dificultad = ?";
            $params[] = $dificultad;
            $types .= "i";
        }

        if (!empty($idsExcluidos)) {

            $placeholders = implode(',', array_fill(0, count($idsExcluidos), '?'));
            $sql .= " AND p.id NOT IN ($placeholders)";
            $params = array_merge($params, $idsExcluidos);
            $types .= str_repeat('i', count($idsExcluidos));
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    private function obtenerOpciones($idPregunta)
    {
        $tablaResp = $this->detectRespuestasTable();
        $sql = "SELECT descripcion, es_correcta FROM " . $tablaResp . " WHERE id_pregunta = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $res = $stmt->get_result();
        $opciones = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        shuffle($opciones);
        return $opciones;
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

    private function contarPreguntasPorCategoria($categoriaId)
    {
        $sql = "SELECT COUNT(*) as total FROM pregunta WHERE id_categoria = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $categoriaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function actualizarDificultadPregunta($idPregunta, $fueCorrecta){
        $sql = "SELECT respuestas_correctas, respuestas_totales FROM pregunta WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$result) return;
        $respuestas_correctas = $result['respuestas_correctas'] + ($fueCorrecta ? 1 : 0);
        $respuestas_totales = $result['respuestas_totales'] + 1;
        $promedioDeRespuestas = ($respuestas_totales > 0) ? ($respuestas_correctas / $respuestas_totales) : 0;
        $dificultad = 2;
        if($respuestas_totales >= 10){
            if ($promedioDeRespuestas >= 0.7) $dificultad = 1;
            if ($promedioDeRespuestas <= 0.3) $dificultad = 3;
        }

        $sql = "UPDATE pregunta SET respuestas_correctas = ?, respuestas_totales = ?, id_dificultad = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("iidi",$respuestas_correctas,$respuestas_totales,$dificultad,$idPregunta);
        $stmt->execute();
    }
}