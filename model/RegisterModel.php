<?php
class RegisterModel
{
    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function registerUser($userData){
        $internalErrors = [];
        if ($this->verifyUsername($userData["username"])) {
            $internalErrors[] = "El nombre de usuario ya está registrado.";
        }
        if ($this->verifyEmail($userData["email"])) {
            $internalErrors[] = "El email ya está en uso.";
        }
        if (!empty($internalErrors)) {
            $errorMessage = implode(" | ", $internalErrors);
            throw new \Exception($errorMessage);
        }

        $passwordHash = $this->generatePasswordHash($userData["password"]);
        $tokenData = $this->generateToken();
        // Asegurar rol por defecto (1 = jugador)
        $userData['role'] = isset($userData['role']) ? (int)$userData['role'] : 1;
        $this->insertUserIntoDatabase($userData, $tokenData, $passwordHash);
    }


    private function generateToken()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        $tokenHash = hash('sha256', $token);
        $tokenData = [ "token" => $tokenHash];
        return $tokenData;
    }

    private function generatePasswordHash($password){
        return password_hash($password, PASSWORD_DEFAULT);

    }

    private function insertUserIntoDatabase($userData, $tokenData, $passwordHash)
    {
        // Insertar con rol_id (por defecto 1 = jugador)
        $sql = "INSERT INTO usuario 
        (nombreCompleto, nombreDeUsuario, mail, contrasenia, fechaNac, sexo, direccion, token, rol_id, fotoDePerfil)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            throw new \Exception("Error al preparar la consulta: " . $this->conexion->error);
        }

        $rol = (int)$userData['role'];
        $foto = isset($userData['profilePic']) ? $userData['profilePic'] : '/public/placeholder.png';

        $stmt->bind_param(
            "ssssssssss",
            $userData["name"],
            $userData["username"],
            $userData["email"],
            $passwordHash,
            $userData["birthdate"],
            $userData["gender"],
            $userData["address"],
            $tokenData["token"],
            $rol,
            $foto
        );

        if (!$stmt->execute()) {
            throw new \Exception("Error al insertar usuario: " . $stmt->error);
        }

        $stmt->close();
    }

    private function verifyUsername($username){
        $sql = "SELECT 1 FROM usuario WHERE nombreDeUsuario = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $userExists = ($stmt->num_rows > 0);
        $stmt->close();
        return $userExists;
    }

    private function verifyEmail($email){
        $sql = "SELECT 1 FROM usuario WHERE mail = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $emailExists = ($stmt->num_rows > 0);
        $stmt->close();
        return $emailExists;
    }
}
?>