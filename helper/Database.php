<?php

class Database
{
    private $conexion;
    private $server;
    private $user;
    private $pass;
    private $database;

    public function __construct($server, $user, $pass, $database)
    {
        $this->server = $server;
        $this->user = $user;
        $this->pass = $pass;
        $this->database = $database;

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
        // Intentar preparar; si la conexión se cayó, reintentar una vez tras reconectar
        try {
            $stmt = $this->conexion->prepare($sql);
        } catch (mysqli_sql_exception $e) {
            error_log("prepare() mysqli_sql_exception: " . $e->getMessage() . " -- intentando reconectar...");
            // intentar reconectar
            $this->reconnect();
            try {
                $stmt = $this->conexion->prepare($sql);
            } catch (mysqli_sql_exception $e2) {
                error_log("prepare() fallo tras reconectar: " . $e2->getMessage() . " -- SQL: " . $sql);
                throw new Exception("Error en prepare (mysqli_sql_exception): " . $e2->getMessage() . " -- SQL: " . $sql);
            }
        }

        if (!$stmt) {
            // Lanzar excepción para que el código llamador pueda manejar el error en lugar de terminar el proceso
            $err = $this->conexion->error ?? 'Unknown error';
            error_log("Error en prepare: " . $err . " -- SQL: " . $sql);
            throw new Exception("Error en prepare: " . $err . " -- SQL: " . $sql);
        }

        return $stmt;
    }

    public function lastInsertId(){
        return $this->conexion->insert_id;
    }

    private function reconnect()
    {
        try {
            if ($this->conexion) {
                @$this->conexion->close();
            }
        } catch (Exception $e) {
            // ignore
        }

        $this->conexion = @new mysqli($this->server, $this->user, $this->pass, $this->database);
        if ($this->conexion->connect_error) {
            error_log('Reconectar falló: ' . $this->conexion->connect_error);
        } else {
            error_log('Reconectado a la base de datos con éxito');
        }
    }

}