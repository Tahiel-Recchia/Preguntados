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
            $data = ['noNavbar' => true, 'noFooter' => true];
            if (isset($_SESSION['login_error'])) {
                $data['error'] = $_SESSION['login_error'];
                unset($_SESSION['login_error']);
            }
            $this->renderer->render('login', $data);
        }

    }


    public function login(){
        $user = $_POST["username"];
        $password = $_POST["password"];

        $result = $this->model->login($user, $password);

        if (is_array($result)) {
            $_SESSION["user_id"] = $result['user_id'];
            $_SESSION["nombreDeUsuario"] = $result['nombreDeUsuario'];

            session_write_close();
            header("Location: /menu");
            exit();

        } else{
            $_SESSION['login_error'] = $result;

            session_write_close();

            header("Location: /login");
            exit();
        }
    }



    public function logout()
    {
        session_destroy();
        header("Location: /");
        exit();
    }
}