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
        try {
            $result = $this->conexion->query($sql);
        } catch (mysqli_sql_exception $e) {
            error_log("Database query error: " . $e->getMessage() . " -- SQL: " . $sql);
            throw new Exception("Database query error: " . $e->getMessage() . " -- SQL: " . $sql);
        }

        if ($result === false) {
            $err = $this->conexion->error;
            error_log("Database query error: " . $err . " -- SQL: " . $sql);
            throw new Exception("Database query error: " . $err . " -- SQL: " . $sql);
        }

        if ($result instanceof mysqli_result) {
            if ($result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            return [];
        }
        return $result;
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