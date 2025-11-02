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

            // Validar y procesar imagen
            $userData['profilePic'] = $this->verifyImage($_FILES['profilePic'] ?? null);

            try {

                $this->model->registerUser($userData);

                $this->renderer->render("login", ['noNavbar' => true, 'noFooter' => true]);

            } catch (\Exception $e) {
                $messages = explode(" | ", $e->getMessage());
                foreach ($messages as $msg) {
                    $errors[] = $msg;
                }

                $this->renderer->render("register", ["errors" => $errors, 'noNavbar' => true, 'noFooter' => true]);
            }
        }
    }
    private function verifyPassword($password, $passwordRepeated)
    {
        $errors = [];
        if ($password !== $passwordRepeated) {
            $errors = "Las contraseñas no coinciden";
        }
        if (strlen($password) < 8) {
            $errors = "La contraseña debe tener al menos 8 caracteres.";
        }
        return $errors;
    }

    private function verifyImage($file)
    {
        // Imagen por defecto
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            // Ruta pública al placeholder
            return "/public/placeholder.png";
        }

        // Tamaño máximo 2MB
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new \Exception("La imagen no puede superar los 2MB.");
        }

        // Tipos permitidos
        $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $permitidos)) {
            throw new \Exception("Formato no permitido. Solo JPG, PNG o GIF.");
        }

        // Verificar que sea imagen real
        if (getimagesize($file['tmp_name']) === false) {
            throw new \Exception("El archivo no es una imagen válida.");
        }

        // Crear nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nuevoNombre = uniqid('pf_', true) . '.' . strtolower($extension);

        // Ruta destino en el servidor
        $destino = __DIR__ . "/../imagenes/perfiles/" . $nuevoNombre;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new \Exception("Error al subir la imagen.");
        }

        // Devolver ruta pública (con slash para evitar rutas relativas inesperadas)
        return "/imagenes/perfiles/" . $nuevoNombre;
    }

}