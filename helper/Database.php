<?php

class Database
{

    private $conexion;

    public function __construct($server, $user, $pass, $database)
    {
        $this->conexion = @new mysqli($server, $user, $pass, $database);
        if ($this->conexion->connect_error) {
            die("Error en la conexión: " . $this->conexion->connect_error);
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
            return $result;
        }
        return $result;
    }


    public function prepare($sql)
    {
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            // Lanzar excepción para que el código llamador pueda manejar el error en lugar de terminar el proceso
            throw new Exception("Error en prepare: " . $this->conexion->error . " -- SQL: " . $sql);
        }
        return $stmt;
    }

    public function lastInsertId(){
        return $this->conexion->insert_id;
    }

}