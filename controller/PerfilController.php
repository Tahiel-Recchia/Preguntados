<?php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
class PerfilController
{
    private $conexion;
    private $renderer;
    private $model;
    private $qr;

    public function __construct($conexion, $renderer, $model, $qr)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
        $this->qr = $qr;
    }



    public function base()
    {
        $usuarioId = $_GET['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$usuarioId) {
            header('Location: /login');
            exit();
        }
        $fila = $this->model->getDatosUsuario($usuarioId);
        $datos = [];
        if ($fila) {
            $fila['fotoDePerfil'] = $this->normalizarFoto($fila['fotoDePerfil']);
            $fila['isEditor'] = ($fila['rol_id'] == 2);
            $fila['esMiPerfil'] = ($usuarioId == ($_SESSION['user_id'] ?? null));
            $datos['usuario'] = $fila;
        }
        $this->renderer->render("perfil", $datos);
    }

    private function normalizarFoto($foto) {
        if (empty($foto)) {
            return '/public/placeholder.png';
        }
        if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
            return '/' . ltrim($foto, '/');
        }
        return $foto;
    }




    public function actualizarUbicacion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /perfil");
            exit;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header("Location: /login");
            exit;
        }

        $nuevaDireccion = trim($_POST['direccion']);

        if (empty($nuevaDireccion)) {
            $this->renderer->render("perfil", ["error" => "La dirección no puede estar vacía."]);
            return;
        }

        $this->model->actualizarDireccion($userId, $nuevaDireccion);

        header("Location: /perfil");
        exit;
    }

    public function getQr(){
        $datos['qrImageUrl'] = $this->qr->getQr($_GET['id']);
        $datosDeUsuario = $this->model->getDatosUsuario($_GET['id']);
        $datos['nombreDeUsuario'] = $datosDeUsuario['nombreDeUsuario'];
        $this->renderer->render("qr", $datos);
    }
}