<?php

class CategoriaModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getCategorias(){
        $sql = $this->conexion->query("SELECT * FROM categoria");
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();
        return $fila;
    }
}