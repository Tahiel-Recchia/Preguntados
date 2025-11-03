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

    /**
     * Endpoint para obtener fotoDePerfil por nombre de usuario (devuelve JSON)
     */
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
            // Normalizar ruta: prefijar '/' si hace falta
            if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                $foto = '/' . ltrim($foto, '/');
            }
            echo json_encode(['fotoDePerfil' => $foto]);
        } else {
            echo json_encode(['fotoDePerfil' => '/public/placeholder.png']);
        }
        exit;
    }


    public function login(){
        $user = $_POST["username"];
        $password = $_POST["password"];

        $result = $this->model->login($user, $password);

        if (is_array($result)) {
            $_SESSION["user_id"] = $result['user_id'];
            $_SESSION["nombreDeUsuario"] = $result['nombreDeUsuario'];
            $_SESSION["rol"] = $result['rol'] ?? 'usuario';

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
        header("Location: /login");
        exit();
    }
}