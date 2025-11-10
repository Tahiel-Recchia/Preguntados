<?php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
class PerfilController
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
        $datos = [];

        // --- Sesión del usuario logueado ---
        if (isset($_SESSION["nombreDeUsuario"])) {
            $datos["sesion"] = $this->model->getDatosUsuario($_SESSION["user_id"]);

            // Normalizar ruta de foto de perfil de sesión
            if (!empty($datos["sesion"]["fotoDePerfil"])) {
                $foto = $datos["sesion"]["fotoDePerfil"];
                if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                    $foto = '/' . ltrim($foto, '/');
                }
                $datos["sesion"]["fotoDePerfil"] = $foto;
            } else {
                $datos["sesion"]["fotoDePerfil"] = '/public/placeholder.png';
            }
        }

        // --- Usuario mostrado (propio o por id GET) ---
        $usuarioId = isset($_GET['id']) ? $_GET['id'] : ($_SESSION["user_id"] ?? null);
        $datos['usuario'] = $usuarioId ? $this->model->getDatosUsuario($usuarioId) : null;

        // Normalizar foto del usuario mostrado
        if (!empty($datos['usuario']['fotoDePerfil'])) {
            $foto = $datos['usuario']['fotoDePerfil'];
            if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                $foto = '/' . ltrim($foto, '/');
            }
            $datos['usuario']['fotoDePerfil'] = $foto;
        } else {
            $datos['usuario']['fotoDePerfil'] = '/public/placeholder.png';
        }

        // --- Marcar si el usuario es editor ---
        $datos['isEditor'] = false;
        if (!empty($datos['usuario']) && isset($datos['usuario']['rol_id'])) {
            $datos['isEditor'] = ($datos['usuario']['rol_id'] == 2);
        }

        // --- Generar QR del perfil ---
        if (!empty($datos['usuario'])) {
            // Detecta el dominio actual (funciona en localhost también)
            $dominio = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
            $perfilUrl = $dominio . "/perfil?id=" . $usuarioId;

            // Crear QR
            $qr = QrCode::create($perfilUrl);
            $writer = new PngWriter();
            $qrResult = $writer->write($qr);

            // Pasar imagen base64 a la vista
            $datos['qrPerfil'] = 'data:image/png;base64,' . base64_encode($qrResult->getString());
        }

        // --- Renderizar vista ---
        $this->renderer->render("perfil", $datos);
    }


    public function actualizarUbicacion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /perfil");
            exit;
        }

        session_start();
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
}