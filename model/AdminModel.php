<?php

class AdminModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

     public function obtenerJugadoresPorPais()
    {
        $query = "
            SELECT 
                TRIM(SUBSTRING_INDEX(direccion, ',', 1)) AS pais,
                COUNT(*) AS cantidad
            FROM usuario
            WHERE direccion IS NOT NULL AND direccion <> ''
            GROUP BY pais
            ORDER BY cantidad DESC
        ";

        $resultado = $this->conexion->query($query);

        // Asegurarse de que pais no venga vacío
        foreach ($resultado as &$fila) {
            if (empty($fila["pais"])) {
                $fila["pais"] = "Desconocido";
            }
        }

        return $resultado;
    }

}

