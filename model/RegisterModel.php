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
        $this->sendVerificationEmail($userData["email"], $userData["name"], $tokenData["token"]);
    }


    public function generateToken()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        $tokenHash = hash('sha256', $token);
        $tokenData = [ "token" => $tokenHash];
        return $tokenData;
    }

    public function generatePasswordHash($password){
        return password_hash($password, PASSWORD_DEFAULT);

    }

    public function insertUserIntoDatabase($userData, $tokenData, $passwordHash)
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

    public function verifyUsername($username){
        $sql = "SELECT 1 FROM usuario WHERE nombreDeUsuario = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $userExists = ($stmt->num_rows > 0);
        $stmt->close();
        return $userExists;
    }

    public function verifyEmail($email){
        $sql = "SELECT 1 FROM usuario WHERE mail = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $emailExists = ($stmt->num_rows > 0);
        $stmt->close();
        return $emailExists;
    }

    public function verifyToken($token){
        $sql = "SELECT id FROM usuario WHERE token = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 0){
            return false;
        }

        $user = $result->fetch_assoc();
        $idUsuario = $user["id"];

        $sql = "UPDATE usuario SET validado = 1, token = NULL WHERE id=?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        return true;
    }

    public function sendVerificationEmail($email, $name, $token)
    {
        require_once __DIR__ . "/../vendor/autoload.php";

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'tom.diuorno99@gmail.com';
            $mail->Password   = 'zguu gbtt gztc nwjj';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('no-reply@preguntados.com', 'Preguntados');

            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = "Verifica tu cuenta";

            $verificationLink = "http://localhost/register/verify?token=" . $token;

            $mail->Body = "
            <h2>Hola $name</h2>
            <p>Por favor hacé clic en el siguiente enlace para activar tu cuenta:</p>
            <a href='$verificationLink' style='padding:10px 20px;background:#4f46e5;color:white;text-decoration:none;border-radius:5px;'>
                Verificar cuenta
            </a>
            <br><br>
            <p>Si no creaste esta cuenta, ignorá este mensaje.</p>
        ";

            $mail->send();

        } catch (Exception $e) {
            throw new \Exception("No se pudo enviar el email de verificación: " . $mail->ErrorInfo);
        }
    }
}
?>