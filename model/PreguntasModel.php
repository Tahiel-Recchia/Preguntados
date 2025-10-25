<?php

class PreguntasModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerPorCategoria($categoriaId)
    {
        $sql = "SELECT * FROM preguntas WHERE id = $categoriaId";
        $resultado = $this->conexion->query($sql);
        $stmt = $this->conexion->prepare($resultado);
        $stmt->bind_param("i", $categoriaId);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();
        return $fila;
    }
}