<?php

class PerfilModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getDatosUsuario($idUsuario){
    $sql = "SELECT id, nombreDeUsuario, fechaNac, fotoDePerfil, sexo, direccion, mejorPuntaje, rol_id, ratio FROM usuario WHERE id = ?";
    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

        return $fila;
    }

    public function actualizarRatio($correctas, $totales, $ratio, $idUsuario){
        $sqlUpdate = "UPDATE usuario SET preguntas_correctas = ?, preguntas_totales = ?, ratio = ? WHERE id = ?";
        $stmtUpdate = $this->conexion->prepare($sqlUpdate);
        $stmtUpdate->bind_param("iidi", $correctas, $totales, $ratio, $idUsuario);
        $stmtUpdate->execute();
    }
    public function actualizarDireccion($userId, $direccion)
    {
        $sql = "UPDATE usuario SET direccion = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("si", $direccion, $userId);
        $stmt->execute();
        $stmt->close();
    }

}