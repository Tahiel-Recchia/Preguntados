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

        // Esto devuelve un mysqli_result
        $resultado = $this->conexion->query($query);

        // Convertimos a array asociativo seguro
        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {

            // Si país llega vacío, lo reemplazo
            if (empty($fila["pais"])) {
                $fila["pais"] = "Desconocido";
            }

            $datos[] = $fila;
        }

        return $datos; // ← ahora sí es un array válido
    }

    public function obtenerPorcentajesPorDificultad()
    {
        $sql = "SELECT 
                d.descripcion AS dificultad,
                COUNT(p.id) AS total,
                ROUND((COUNT(p.id) / (SELECT COUNT(*) FROM pregunta) * 100), 2) AS porcentaje
            FROM pregunta p
            JOIN dificultad d ON p.id_dificultad = d.id
            GROUP BY p.id_dificultad";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

}

