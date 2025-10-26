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
        $sql = "SELECT * FROM pregunta WHERE categoria_id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $categoriaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fila = $result->fetch_assoc();
        $stmt->close();

        return [
            "pregunta" => $fila["descripcion"],
            "categoria" => $fila["categoria_id"],
            "opcion_correcta" => $fila["opcion_correcta"],
            "opcion_uno" => $fila["opcion1"],
            "opcion_dos" => $fila["opcion2"],
            "opcion_tres" => $fila["opcion3"],
            "opcion_cuatro" => $fila["opcion4"],
        ];
    }
}