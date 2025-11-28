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
        $sql = "INSERT INTO partida (horaInicio, estado, id_usuario) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssi", $horaInicio, $estado, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        return $this->conexion->lastInsertId();
    }
    public function terminarPartida($idPartida, $puntaje){
        $fecha_fin_obj = new DateTime();
        $horaFin = $fecha_fin_obj->format('Y-m-d H:i:s');
        $estado = "Finalizada";
        $sql = "UPDATE partida SET horaFin = ?, estado = ?, puntaje = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssii", $horaFin, $estado, $puntaje, $idPartida);
        $stmt->execute();
        $stmt->close();
    }

    public function verificarTiempo($horaEnvio, $horaRespuesta){
        $segundosEnvio = $horaEnvio->getTimestamp();
        $segundosRespuesta   = $horaRespuesta->getTimestamp();
        $diferenciaSegundos = $segundosRespuesta - $segundosEnvio;
        if ($diferenciaSegundos > 10){
            return false;
        }
        return true;
    }

}