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
                $_SESSION["user"] = $user;
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
            $this->renderer->render("register");
            return;
        }
        if (isset($_POST["user"]) && isset($_POST["password"])) {
            $user = $_POST["user"];
            $password = $_POST["password"];
            $passwordRepeated = $_POST["passwordRepeated"];

            //Verificacion de contraseña
            $pwError = $this->verifyPassword($password, $passwordRepeated);
            if ($pwError !== null) {
                $this->renderer->render("register", ["error" => $pwError]);
                return;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $name = $_POST["name"];
            $birth = $_POST["birth"];
            $gender = $_POST["gender"];
            $address = $_POST["address"];
            $email = $_POST["email"];
            $token = $token = bin2hex(openssl_random_pseudo_bytes(16));
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');

            // Validación de imagen de perfil
            $fotoPath = null;

            if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
            $foto = $_FILES['profilePic'];

            // Tamaño máximo permitido: 2MB
            $maxSize = 2 * 1024 * 1024;
            if ($foto['size'] > $maxSize) {
            $this->renderer->render("register", ["error" => "La imagen no puede superar los 2MB."]);
            return;
            }

            // Tipos permitidos
            $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($foto['type'], $permitidos)) {
                $this->renderer->render("register", ["error" => "Formato no permitido. Solo JPG, PNG o GIF."]);
                return;
            }

            // Verificar que sea imagen real
            $check = getimagesize($foto['tmp_name']);
            if ($check === false) {
                $this->renderer->render("register", ["error" => "El archivo no es una imagen válida."]);
                return;
            }

            // Crear nombre único
            $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $nuevoNombre = uniqid('pf_', true) . '.' . strtolower($extension);

            // Ruta destino
            $destino = __DIR__ . "/../imagenes/perfiles/" . $nuevoNombre;

            // Mover archivo
            if (!move_uploaded_file($foto['tmp_name'], $destino)) {
                $this->renderer->render("register", ["error" => "Error al subir la imagen."]);
                return;
            }

            // Guardamos solo el nombre en la BD
            $fotoPath = "imagenes/perfiles/" . $nuevoNombre;
            } else {
            // Si no sube nada, usar imagen por defecto
            $fotoPath = "imagenes/placeholder.png";
            }
            
        // Insertar usuario en la base de datos
        $sql = "INSERT INTO users 
                (user, email, password_hash, verified, verification_token_hash, token_expires_at, name, foto)
                VALUES (?, ?, ?, 0, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssssss", $user, $email, $passwordHash, $tokenHash, $expiresAt, $name, $fotoPath);

        if (!$stmt->execute()) {
            die("Error al insertar usuario: " . $stmt->error);
        }
        $stmt->close();

        // Enviar correo de verificación
        $this->sendVerificationEmail($email, $user, $token);

        // Mostrar mensaje de éxito o redirigir
        $this->renderer->render("register", ["success" => "Registro exitoso. Revisa tu correo para verificar la cuenta."]);
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
            $this->renderer->render("register", ["error" => $error]);
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