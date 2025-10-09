<?php


class LoginController
{
    private $conexion;
    private $renderer;

    public function __construct($conexion, $renderer)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
    }


    public function login()
    {
        if (isset($_POST["user"]) && isset($_POST["password"])) {
            $user = $_POST["user"];
            $password = $_POST["password"];
            $sql = "SELECT * FROM users WHERE user='$user' AND password_hash='$password'";
            $resultado = $this->conexion->query($sql);
            if (sizeof($resultado) > 0) {
                $_SESSION["usuario"] = $usuario;
                header("Location: /");
                exit;
            } else {
                $error = "Usuario o clave incorrecta";
            }
        }
        $this->renderer->render("register");
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderer->render("registro");
            return;
        }
        if (isset($_POST["user"]) && isset($_POST["password"])) {
            $user = $_POST["user"];
            $password = $_POST["password"];
            $passwordRepeated = $_POST["passwordRepeated"];

            //Verificacion de contraseña
            $pwError = $this->verifyPassword($password, $passwordRepeated);
            if ($pwError !== null) {
                $this->renderer->render("registro", ["error" => $pwError]);
                return;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $name = $_POST["name"];
            $birth = $_POST["birth"];
            $gender = $_POST["gender"];
            $address = $_POST["address"];
            $email = $_POST["email"];
            $token = bin2hex(random_bytes(16));
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');


            $sql = "INSERT INTO users (user, email, password_hash, verified, verification_token_hash, token_expires_at, name)
        VALUES (?,?,?,0,?,?,?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("ssssss", $user, $email, $passwordHash, $tokenHash, $expiresAt, $name);
            if (!$stmt->execute()) {
                die("Error al insertar usuario: " . $stmt->error);
            }
            $stmt->close();
            $this->sendVerificationEmail($email, $user, $token);
        }
    }


    public function logout()
    {
        session_start();
        session_destroy();
        header("Location: index.php");
        exit();
    }


    private function verifyPassword($password, $passwordRepeated)
    {
        if ($password === '' || $passwordRepeated === '') {
            $error = "Completá ambos campos de contraseña.";
        } elseif ($password !== $passwordRepeated) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($password) < 1) {
            $error = "La contraseña debe tener al menos 1 caracter.";
        }

        if (!empty($error)) {
            $this->renderer->render("registro", ["error" => $error]);
        }
    }

    private function sendVerificationEmail($email, $username, $token)
    {

        $tokenUrl = "http://localhost/tu_proyecto/verify?token=" . $token;

        // Asunto y mensaje del correo
        $subject = "Verificación de tu cuenta";
        $message = "Hola $username,\n\n";
        $message .= "Para verificar tu cuenta, hacé click en este enlace:\n";
        $message .= "$tokenUrl\n\n";
        $message .= "Este enlace expira en 24 horas.\n";

        // Encabezados
        $headers = "From: no-reply@localhost\r\n";
        $headers .= "Reply-To: no-reply@localhost\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Enviar el correo
        if (mail($email, $subject, $message, $headers)) {
            // Opcional: loguear que se envió el correo
            error_log("Correo de verificación enviado a $email");
        } else {
            // Opcional: loguear error
            error_log("Error al enviar correo de verificación a $email");

        }
    }
}