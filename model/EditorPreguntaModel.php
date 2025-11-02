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
        $stmt = $this->conexion->prepare("SELECT * FROM respuesta WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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