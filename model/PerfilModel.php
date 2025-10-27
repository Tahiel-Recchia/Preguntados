<?php

class PerfilModel {
    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function getDatosUsuario($idUsuario){
    $sql = "SELECT nombreDeUsuario, fechaNac, fotoDePerfil, sexo, direccion, puntajeAcumulado FROM usuario WHERE id = ?";
    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

    return $fila;
    }
}