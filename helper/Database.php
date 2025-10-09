<?php

class Database
{

    private $conexion;

    public function __construct($server, $user, $pass, $database)
    {
        $this->conexion = new mysqli($server, $user, $pass, $database);
        if ($this->conexion->error) {
            die("Error en la conexión: " . $this->conexion->error);
        }
    }

    public function query($sql)
    {
        $result = $this->conexion->query($sql);

        if ($result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }


    public function prepare($sql)
    {
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            die("Error en prepare: " . $this->conexion->error);
        }
        return $stmt;
    }
}