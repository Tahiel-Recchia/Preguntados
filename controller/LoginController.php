<?php
class LoginController
{
    private $renderer;
    private $model;

    public function __construct($renderer, $model)
    {
        $this->renderer = $renderer;
        $this->model = $model;
    }

    public function base()
    {
        if (isset($_POST["username"]) && isset($_POST["password"])) {
            $this->login();
        } else {
            $data = ['noNavbar' => true, 'noFooter' => true];
            if (isset($_SESSION['login_error'])) {
                $data['error'] = $_SESSION['login_error'];
                unset($_SESSION['login_error']);

            }
            $this->renderer->render('login', $data);
        }

    }
    public function obtenerUsuario()
    {
        $username = $_GET['username'] ?? null;
        header('Content-Type: application/json');
        if (!$username) {
            echo json_encode(['error' => 'Falta username']);
            exit;
        }

        $foto = $this->model->getFotoByUsername($username);
        if ($foto) {
            if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                $foto = '/' . ltrim($foto, '/');
            }
            echo json_encode(['fotoDePerfil' => $foto]);
        } else {
            echo json_encode(['fotoDePerfil' => '/public/placeholder.png']);
        }
        exit;
    }


    public function login()
    {
        $user = $_POST["username"];
        $password = $_POST["password"];

        $result = $this->model->login($user, $password);

        if (is_array($result)) {
            $_SESSION["user_id"] = $result['user_id'];
            $_SESSION["nombreDeUsuario"] = $result['nombreDeUsuario'];
            $_SESSION["rol"] = $result['rol'] ?? 'usuario';
            $_SESSION["ratio"] = $result['ratio'];

            // Guardar fotoDePerfil en sesión para que la navbar y vistas puedan mostrarla
            $foto = $this->model->getFotoByUsername($result['nombreDeUsuario']);
            if ($foto) {
                if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                    $foto = '/' . ltrim($foto, '/');
                }
                $_SESSION['fotoDePerfil'] = $foto;
            } else {
                $_SESSION['fotoDePerfil'] = '/public/placeholder.png';
            }
            if ($_SESSION["rol"] == 3) {
                header("Location: /panelAdmin");
            } else {
                header("Location: /menu");
            }
            exit();

        } else {
            $_SESSION['login_error'] = $result;

            session_write_close();

            header("Location: /login");
            exit();
        }
    }

    public function logout()
    {
        session_destroy();
        header("Location: /login");
        exit();
    }
}