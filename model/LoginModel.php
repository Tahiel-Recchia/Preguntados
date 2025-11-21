<?php
class LoginModel {
    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function login($user, $password_plano){
        // 🔹 Incluimos el rol en la consulta con JOIN
        $sql = "
            SELECT u.*, r.descripcion AS rol_nombre 
            FROM usuario u 
            LEFT JOIN rol r ON u.rol_id = r.id
            WHERE u.nombreDeUsuario = ?
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if (!$fila) {
            return "Usuario o clave incorrecta";
        }

        if (!password_verify($password_plano, $fila["contrasenia"])) {
            return "Usuario o clave incorrecta";
        }

        if ($fila["validado"] != 1) {
            return "Usuario no verificado";
        }

        // ✅ Ahora devolvemos también el rol
        return [
            'user_id' => $fila["id"],
            'nombreDeUsuario' => $user,
            'rol' => $fila["rol_id"],
            'ratio' => $fila["ratio"]
        ];
    }

    /**
     * Obtener foto de perfil por nombre de usuario (para preview en login)
     */
    public function getFotoByUsername($user){
        $sql = "SELECT fotoDePerfil FROM usuario WHERE nombreDeUsuario = ? LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if ($fila && !empty($fila['fotoDePerfil'])) {
            return $fila['fotoDePerfil'];
        }
        return null;
    }
}
