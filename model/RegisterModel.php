<?php
class RegisterModel
{
    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function registerUser($userData){
        echo "hola";
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
        $this->insertUserIntoDatabase($userData, $tokenData, $passwordHash);
    }


    private function generateToken()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');
        $tokenData = [ "token" => $tokenHash, "expires_at" => $expiresAt ];
    return $tokenData;
    }

    private function generatePasswordHash($password){
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $passwordHash;
    }

    private function insertUserIntoDatabase($userData, $tokenData, $passwordHash)
    {

        $sql = "INSERT INTO users (username, email, password_hash, verified, verification_token_hash, token_expires_at, name)
        VALUES (?,?,?,0,?,?,?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param(
            "ssssss",
            $userData["username"],
            $userData["email"],
            $passwordHash,
            $tokenData["token"],
            $tokenData["expires_at"],
            $userData["name"]
        );

        if (!$stmt->execute()) {
// Manejo de errores de BD, no morir con die()
            throw new \Exception("Error al insertar usuario: " . $stmt->error);
        }
        $stmt->close();
    }

    private function verifyUsername($username){
        $sql = "SELECT 1 FROM users WHERE username = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->store_result();
        $userExists = ($stmt->num_rows > 0);
        $stmt->close();
        return $userExists;
    }

    private function verifyEmail($email){
        $sql = "SELECT 1 FROM users WHERE email = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->store_result();
        $emailExists = ($stmt->num_rows > 0);
        $stmt->close();
        return $emailExists;
    }
    public function saludar(){
        echo "hola";
    }
}


?>
