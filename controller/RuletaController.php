<?php

class RuletaController
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
        // Bloquear acceso a la ruleta/juego para editores y administradores
        if (isset($_SESSION['rol']) && in_array($_SESSION['rol'], [2, 3])) {
            header('Location: /');
            exit;
        }
        $categoriasArray = $this->model->getCategorias();
        $data["categorias_json"] =  json_encode($categoriasArray);
        // Normalizar datos de sesión para que el navbar use la misma estructura en todas las vistas
        if (isset($_SESSION['user_id'])) {
            $data['sesion'] = [
                'id' => $_SESSION['user_id'],
                'nombreDeUsuario' => $_SESSION['nombreDeUsuario'] ?? null,
                'fotoDePerfil' => isset($_SESSION['fotoDePerfil']) ? $_SESSION['fotoDePerfil'] : '/public/placeholder.png'
            ];
            // Asegurar ruta absoluta de la foto
            if ($data['sesion']['fotoDePerfil'] && strpos($data['sesion']['fotoDePerfil'], '/') !== 0 && stripos($data['sesion']['fotoDePerfil'], 'http') !== 0) {
                $data['sesion']['fotoDePerfil'] = '/' . ltrim($data['sesion']['fotoDePerfil'], '/');
            }
        }
        $this->renderer->render("ruleta", $data);
    }
}