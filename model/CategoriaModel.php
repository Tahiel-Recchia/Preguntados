<?php

class CategoriaModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getCategorias()
    {
        $sql = "SELECT * FROM categoria";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $filas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
        $stmt->close();
        return $filas;
    }

    public function guardarCategoria($descripcion, $color, $imagen)
    {
        $sql = "INSERT INTO categoria (descripcion, color, imagen) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sss", $descripcion, $color, $imagen);
        $stmt->execute();
        $stmt->close();
    }

     public function getCategoriaById($id)
    {
        $sql = "SELECT * FROM categoria WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }
    public function eliminarCategoria($id)
    {
        $sql = "DELETE FROM categoria WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    public function actualizarCategoria($id, $descripcion, $color, $imagen)
    {
        $sql = "UPDATE categoria SET descripcion = ?, color = ?, imagen = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssi", $descripcion, $color, $imagen, $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }


}