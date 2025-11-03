<?php

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

    public function base(){
        $datos = [];
        if (isset($_SESSION["nombreDeUsuario"])) {
            $datos["sesion"] = $this->model->getDatosUsuario($_SESSION["user_id"]);
            // Normalizar ruta de foto de perfil en sesión
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

        $usuarioId = isset($_GET['id']) ? $_GET['id'] : ($_SESSION["user_id"] ?? null);
        $datos['usuario'] = $usuarioId ? $this->model->getDatosUsuario($usuarioId) : null;

        // Normalizar ruta de foto de perfil del usuario mostrado
        if (!empty($datos['usuario']['fotoDePerfil'])) {
            $foto = $datos['usuario']['fotoDePerfil'];
            if (strpos($foto, '/') !== 0 && stripos($foto, 'http') !== 0) {
                $foto = '/' . ltrim($foto, '/');
            }
            $datos['usuario']['fotoDePerfil'] = $foto;
        } else {
            $datos['usuario']['fotoDePerfil'] = '/public/placeholder.png';
        }

        // Marcar si el usuario es editor (rol_id == 2)
        $datos['isEditor'] = false;
        if (!empty($datos['usuario']) && isset($datos['usuario']['rol_id'])) {
            $datos['isEditor'] = ($datos['usuario']['rol_id'] == 2);
        }

        $this->renderer->render("perfil", $datos);
    }


}