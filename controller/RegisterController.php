<?php
class RegisterController
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

            $passwordErrors = $this->verifyPassword($userData["password"], $userData["passwordRepeated"]);
            if (!empty($passwordErrors)) {
                $this->renderer->render("register", ["errors" => $passwordErrors, 'noNavbar' => true, 'noFooter' => true]);
                return;
            }

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
            $errors[] = "Las contraseñas no coinciden";
        }
        if (strlen($password) < 8) {
            $errors[] = "La contraseña debe tener al menos 8 caracteres.";
        }
        return $errors;
    }

    private function verifyImage($file)
    {
        // Imagen por defecto
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return "/public/placeholder.png";
        }
        $maxSize = 2 * 1024 * 1024; // 2MB en bytes
        if ($file['size'] > $maxSize) {
            $sizeMB = round($file['size'] / (1024 * 1024), 2);
            throw new \Exception("La imagen es demasiado grande ({$sizeMB}MB). El tamaño máximo permitido es 2MB.");
        }

        $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $permitidos)) {
            throw new \Exception("Formato no permitido. Solo JPG, PNG o GIF.");
        }

        if (getimagesize($file['tmp_name']) === false) {
            throw new \Exception("El archivo no es una imagen válida.");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nuevoNombre = uniqid('pf_', true) . '.' . strtolower($extension);

        $destino = __DIR__ . "/../imagenes/perfiles/" . $nuevoNombre;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new \Exception("Error al subir la imagen.");
        }

        return "/imagenes/perfiles/" . $nuevoNombre;
    }

    public function check(){
        header('Content-Type: application/json; charset=utf-8');

        $campo = $_POST['campo'] ?? null;
        $valor = $_POST['valor'] ?? null;

        if(!$campo || !$valor){
            echo json_encode(["ok" => false, "mensaje" => "Datos invalidos"]);
            return;
        }

        if($campo === "username"){
            $existe = $this->model->verifyUsername($valor);
            echo json_encode([
                "ok" => !$existe,
                "mensaje" => $existe ? "El nombre de usuario ya esta registrado." : "Usuario disponible"
            ]);
            return;
        }

        if($campo === "email"){
            $existe = $this->model->verifyEmail($valor);
            echo json_encode([
                "ok"=> !$existe,
                "mensaje" => $existe ? "El email ya esta registrado." : "Email disponible"
            ]);
            return;
        }
    }

    public function verify()
    {
        if (!isset($_GET['token'])) {
            echo "Token inválido.";
            return;
        }

        $token = hash('sha256', $_GET['token']);

        $resultado = $this->model->verifyToken($_GET['token']);
        if($resultado){
            $this->renderer->render("verificacionExitosa", ["noNavbar" => true, "noFooter" => true]);
        }else{
            $this->renderer->render("verificacionFallida", ["error" => "Token invalido", "noNavbar" => true, "noFooter" => true]);
        }
    }
}