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

    public function base()
    {
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

        $data["jugadoresPorCiudad"] = $this->model->obtenerJugadoresPorCiudad();
        $data["jugPorCiudadJson"] = json_encode($data["jugadoresPorCiudad"]);
        $data["jugadoresPorEdad"] = $this->model->obtenerJugadoresPorEdad();
        $data["jugPorEdadJson"] = json_encode($data["jugadoresPorEdad"]);

        $data["partidasPorDia"] = $this->model->obtenerPartidasPorDia();
        $data["partidasPorDiaJson"] = json_encode($data["partidasPorDia"]);

        $data["partidasPorSemana"] = $this->model->obtenerPartidasPorSemana();
        $data["partidasPorSemanaJson"] = json_encode($data["partidasPorSemana"]);

        $data["partidasPorMes"] = $this->model->obtenerPartidasPorMes();
        $data["partidasPorMesJson"] = json_encode($data["partidasPorMes"]);

        $data["partidasPorAno"] = $this->model->obtenerPartidasPorAno();
        $data["partidasPorAnoJson"] = json_encode($data["partidasPorAno"]);

        $this->renderer->render("panelAdmin", $data);
    }
    public function generarPdfGraficos()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || !isset($data["imgPartidas"])) {
            http_response_code(400);
            echo "Faltan datos";
            exit;
        }

        $imgPartidas = $data["imgPartidas"];
        $imgCiudad = $data["imgCiudad"];
        $imgEdad = $data["imgEdad"];
        $imgPais = $data["imgPais"];
        $graficoDificultad = $data["graficoDificultad"];
        $periodo = $data['periodo']; // filtro elegido

        // Convertimos el periodo a texto amigable
        $periodosTexto = [
            'dia' => 'Últimos 30 días',
            'semana' => 'Últimas 12 semanas',
            'mes' => 'Últimos 12 meses',
            'ano' => 'Últimos años'
        ];
        $tituloPeriodo = $periodosTexto[$periodo] ?? 'Periodo desconocido';

        $dompdf = new Dompdf();
        $dompdf->set_option("isRemoteEnabled", true);
        $dompdf->set_option("isHtml5ParserEnabled", true);

        $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {color: black !important; font-family: Arial, sans-serif; text-align:center; }
            h1 { text-align:center; margin-bottom:30px; }
            h3 { margin-top:40px; margin-bottom:10px; }
            img { width: 80%; margin-bottom: 40px; }
        </style>
    </head>
    <body>
        <h1>Reporte de Gráficos</h1>

        <h3>Partidas jugadas: ' . $tituloPeriodo . '</h3>
        <img src="' . $imgPartidas . '" />

        <h3>Jugadores por ciudad</h3>
        <img src="' . $imgCiudad . '" />

        <h3>Jugadores por edad</h3>
        <img src="' . $imgEdad . '" />

        <h3>Jugadores por país</h3>
        <img src="' . $imgPais . '" />

        <h3>Porcentaje por dificultad de preguntas</h3>
        <img src="' . $graficoDificultad . '" />
    </body>
    </html>
    ';

        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4", "portrait");
        $dompdf->render();
        $dompdf->stream("reporte_graficos.pdf", ["Attachment" => true]);
        exit;
    }


    public function hacerEditor()
    {
        $idUsuario = $_GET["id"];
        if ($_SESSION['rol'] !== 3) {
            header("Location: /");
            exit();
        }
        $this->model->hacerEditor($idUsuario);
        header("Location: /perfil/base/" . $idUsuario);
        exit();
    }

    public function eliminarEditor()
    {
        $idUsuario = $_GET["id"];
        if ($_SESSION['rol'] !== 3) {
            header("Location: /");
            exit();
        }
        $this->model->eliminarEditor($idUsuario);
        header("Location: /perfil/base/" . $idUsuario);
        exit();
    }

}
