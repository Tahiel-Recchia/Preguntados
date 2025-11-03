<?php
class PartidaModel {
    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function iniciarPartida(){
        $fecha_inicio_obj = new DateTime();
        $horaInicio = $fecha_inicio_obj->format('Y-m-d H:i:s');
        $estado = "En curso";
        $sql = "INSERT INTO Partida (horaInicio, estado) VALUES (?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ss", $horaInicio, $estado);
        $stmt->execute();
        $stmt->close();
        return $this->conexion->lastInsertId();
    }
    public function terminarPartida($idPartida, $puntaje){
        $fecha_fin_obj = new DateTime();
        $horaFin = $fecha_fin_obj->format('Y-m-d H:i:s');
        $estado = "Finalizada";
        $sql = "UPDATE Partida SET horaFin = ?, estado = ?, puntaje = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssii", $horaFin, $estado, $puntaje, $idPartida);
        $stmt->execute();
        $stmt->close();
    }
}