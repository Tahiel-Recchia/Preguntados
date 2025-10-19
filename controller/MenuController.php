<?php

class MenuController{

    private $conexion;
    private $renderer;

    public function __construct($conexion, $renderer)
    {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
    }

    public function base() {
    if (!isset($_SESSION["user"])) {
        header("Location: /preguntados/login/login");
        exit;
    }

    $usuario = $_SESSION["user"];
    $sql = "SELECT * FROM users WHERE user = ?";
    $stmt = $this->conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datosUsuario = $resultado->fetch_assoc();

    $this->renderer->render("menu", ["usuario" => $datosUsuario]);
    }

}