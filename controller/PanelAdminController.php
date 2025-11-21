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
        $this->renderer->render("panelAdmin", $data);
    }

    public function descargarpdf()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->requireAdmin();

        $jugadores = $this->model->obtenerJugadoresPorPais();

        // Limpiar TODO output previo
        while (ob_get_level()) {
            ob_end_clean();
        }

        ob_start();

        $ruta = __DIR__ . '/../helper/graficosSoloPdf.php';
        if (!file_exists($ruta)) {
            die("ERROR: No se encuentra la vista del PDF");
        }

        $datos = $jugadores;

        include $ruta;

        $html = ob_get_clean();

        if (trim($html) == "") {
            die("ERROR: HTML vacío en la plantilla PDF");
        }

        $dompdf = new Dompdf();
        $dompdf->set_option("isRemoteEnabled", true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4", "portrait");
        $dompdf->render();

        // Descargar
        $dompdf->stream("jugadores_por_pais.pdf", ["Attachment" => true]);
        exit;
    }

}
