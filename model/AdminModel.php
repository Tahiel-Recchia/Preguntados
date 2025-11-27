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

    public function obtenerJugadoresPorCiudad()
    {
        $query = "
        SELECT 
            TRIM(SUBSTRING_INDEX(direccion, ',', -1)) AS ciudad,
            COUNT(*) AS cantidad
        FROM usuario
        WHERE direccion IS NOT NULL AND direccion <> ''
        GROUP BY ciudad
        ORDER BY cantidad DESC
    ";

        $resultado = $this->conexion->query($query);

        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {

            if (empty($fila["ciudad"])) {
                $fila["ciudad"] = "Desconocido";
            }

            $datos[] = $fila;
        }

        return $datos;
    }

    public function obtenerJugadoresPorEdad()
    {
        $query = "
        SELECT 
            (YEAR(CURDATE()) - fechaNac) AS edad,
            COUNT(*) AS cantidad
        FROM usuario
        WHERE fechaNac IS NOT NULL
        GROUP BY edad
        ORDER BY edad ASC
    ";

        $resultado = $this->conexion->query($query);

        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        return $datos;
    }

    // Fechas

    public function obtenerPartidasPorDia()
    {
        $query = "
        SELECT DATE(horaInicio) AS fecha, COUNT(*) AS cantidad
        FROM partida
        GROUP BY DATE(horaInicio)
        ORDER BY fecha DESC
        LIMIT 30
    ";
        $resultado = $this->conexion->query($query);
        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        return $datos;
    }

    public function obtenerPartidasPorSemana()
    {
        $query = "
        SELECT YEAR(horaInicio) AS anio, WEEK(horaInicio, 1) AS semana, COUNT(*) AS cantidad
        FROM partida
        GROUP BY anio, semana
        ORDER BY anio DESC, semana DESC
        LIMIT 12
    ";
        $resultado = $this->conexion->query($query);
        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        return $datos;
    }

    public function obtenerPartidasPorMes()
    {
        $query = "
        SELECT YEAR(horaInicio) AS anio, MONTH(horaInicio) AS mes, COUNT(*) AS cantidad
        FROM partida
        GROUP BY anio, mes
        ORDER BY anio DESC, mes DESC
        LIMIT 12
    ";
        $resultado = $this->conexion->query($query);
        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        return $datos;
    }

    public function obtenerPartidasPorAno()
    {
        $query = "
        SELECT YEAR(horaInicio) AS anio, COUNT(*) AS cantidad
        FROM partida
        GROUP BY anio
        ORDER BY anio DESC
    ";
        $resultado = $this->conexion->query($query);
        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        return $datos;
    }

    // Metodos para cambiar roles


    public function hacerEditor($userId)
    {
        $sql = "UPDATE usuario SET rol_id = 2 WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $userId);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    public function eliminarEditor($userId)
    {
        $sql = "UPDATE usuario SET rol_id = 1 WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $userId);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

}

