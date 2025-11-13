<?php

class PuntajeModel
{

    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function actualizarMejorPuntaje($idUsuario, $puntaje){
        $sql = "UPDATE Usuario SET puntaje = ? WHERE id = ? AND ? > mejorPuntaje";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("iii", $puntaje, $idUsuario);
        $stmt->execute();
        $stmt->close();
    }

    public function sumarPuntos($puntajeActual){
        return $puntajeActual += 30;
    }
}