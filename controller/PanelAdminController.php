<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
class PanelAdminController
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
    private function requireAdmin()
    {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], [3])) {
            header('HTTP/1.1 403 Forbidden');
            // redirigir a menú usando URL explícita para entornos sin .htaccess
            header('Location: /index.php?controller=panelAdmin');
            exit;
        }
    }
    public function base()
    {
        $this->requireAdmin();
        $data = [];
        // Proveer datos de sesión a la vista para que el navbar tenga la misma estructura que en otras pantallas
        if (isset($_SESSION['user_id'])) {
            $data['sesion'] = [
                'id' => $_SESSION['user_id'],
                'nombreDeUsuario' => $_SESSION['nombreDeUsuario'] ?? null,
                'fotoDePerfil' => $_SESSION['fotoDePerfil'] ?? '/public/placeholder.png',
                'rol' => $_SESSION['rol'] ?? null
            ];
        }
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        // cargar tabla
        $data["jugadoresPorPais"] = $this->model->obtenerJugadoresPorPais();
        $data["jugPorPaisJson"] = json_encode($data["jugadoresPorPais"]);
        $porcentajes = $this->model->obtenerPorcentajesPorDificultad();
        $data["porcentajesJson"] = json_encode($porcentajes);
        $this->renderer->render("panelAdmin", $data);
    }
    public function generarPdfGraficos()
    {
        $this->requireAdmin();

        // Limpiar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || !isset($data["grafico1"]) || !isset($data["grafico2"]) || !isset($data["grafico3"])) {
            http_response_code(400);
            echo "Faltan datos";
            exit;
        }

        $img1 = $data["grafico1"];
        $img2 = $data["grafico2"];
        $img3 = $data["grafico3"];

        $dompdf = new Dompdf();
        $dompdf->set_option("isRemoteEnabled", true);
        $dompdf->set_option("isHtml5ParserEnabled", true);

        $dompdf->set_option("isRemoteEnabled", true);

        $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {color: black !important; font-family: Arial, sans-serif; text-align:center; }
            h1 { text-align:center; margin-bottom:30px; }
            h3 { margin-top:40px; margin-bottom:10px; }
            img { width: 75%; margin-bottom: 40px; }
            
        </style>
    </head>
    <body>
        <h1>Reporte de Gráficos</h1>

        <h3>Tabla: Jugadores por país</h3>
        <img src="' . $img3 . '" />

        <h3>Jugadores por país</h3>
        <img src="' . $img1 . '" />

        <h3>Porcentaje por dificultad de preguntas</h3>
        <img src="' . $img2 . '" />
    </body>
    </html>
    ';

        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4", "portrait");
        $dompdf->render();

        // Descargar PDF
        $dompdf->stream("reporte_graficos.pdf", ["Attachment" => true]);
        exit;
    }


}
