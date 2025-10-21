<?php
class RegisterController
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
    public function base()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderer->render("register", ['noNavbar' => true, 'noFooter' => true]);
            return;
        }
        if (isset($_POST["username"]) && isset($_POST["password"])) {

            $userData = [
                "username" => $_POST["username"],
                "password" => $_POST["password"],
                "passwordRepeated" => $_POST["passwordRepeated"],
                "name" => $_POST["name"],
                "birthdate" => $_POST["birthdate"],
                "gender" => $_POST["gender"],
                "address" => $_POST["address"],
                "email" => $_POST["email"]
            ];

            //Verificacion de contraseña
            $passwordErrors = $this->verifyPassword($userData["password"], $userData["passwordRepeated"]);
            if (!empty($passwordErrors)) {
                $this->renderer->render("register", ["error" => $passwordErrors, 'noNavbar' => true, 'noFooter' => true]);
                return;
            }
            $this->model->registerUser($userData);
            $this->renderer->render("login", ['noNavbar' => true, 'noFooter' => true]);
        }
    }
    private function verifyPassword($password, $passwordRepeated){
        $errors = [];
        if($password !== $passwordRepeated) {
            $errors = "Las contraseñas no coinciden";
        }
        if(strlen($password) < 8){
            $errors = "La contraseña debe tener al menos 8 caracteres.";
        }
        return $errors;
    }
}