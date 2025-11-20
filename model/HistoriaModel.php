<?php

class HistoriaModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getHistorial($idUsuario){
        $sql = "SELECT * FROM partida WHERE id_usuario = ? ORDER BY id DESC LIMIT 15";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $historial = [];
        while($row = $result->fetch_assoc()){
            $historial[] = $row;
        }
        return $historial;
    }
}