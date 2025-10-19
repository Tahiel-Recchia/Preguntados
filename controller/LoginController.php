<?php


class LoginController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($conexion, $renderer, $model)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
    }

    public function base(){
        if (isset($_POST["username"]) && isset($_POST["password"])){
            $this->login();
        } else{
            $this->renderer->render('login');
        }

    }


    public function login(){
            $user = $_POST["username"];
            $password = $_POST["password"];

            $result = $this->model->login($user, $password);

            if (is_array($result)) {

                $_SESSION["user_id"] = $result['user_id'];
                $_SESSION["username"] = $result['username'];
                header("Location: /Preguntados/menu");
                exit();
            } else{
                $data = ['error' => $result];
                $this->renderer->render('login', $data);
            }

        }




    public function logout()
    {
        session_start();
        session_destroy();
        header("Location: index.php");
        exit();
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