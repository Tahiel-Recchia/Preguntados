<?php
class LoginController {
    private $conexion;
    private $renderer;

    public function __construct($conexion, $renderer) {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
    }


    public function login() {
        if (isset($_POST["usuario"]) && isset($_POST["password"])) {
            $usuario = $_POST["usuario"];
            $password = $_POST["password"];
            $sql = "SELECT * FROM usuarios WHERE usuario='$usuario' AND password='$password'";
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


    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php");
        exit();
    }
}