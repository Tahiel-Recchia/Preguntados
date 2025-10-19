<?php
class LoginModel {
    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function login($user, $password_plano){
        $sql = "SELECT id, password_hash, verified FROM users WHERE username = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if (!$fila) {
            return "Usuario o clave incorrecta";
        }




        if (!password_verify($password_plano, $fila["password_hash"])) {
            return "Usuario o clave incorrecta";
        }

        if ($fila["verified"] != 1) {
            return "Usuario no verificado";
        }

        return [
            'user_id' => $fila["id"],
            'username' => $user
        ];
    }



}