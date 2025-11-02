 <!-- <?php ?>
class EditorController {
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($conexion, $renderer, $model) {
        $this->conexion = $conexion;
        $this->renderer = $renderer;
        $this->model = $model;
    }

    // Helper: chequeo rol (editor o admin)
    private function requireEditor() {
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['editor','admin'])) {
            header('HTTP/1.1 403 Forbidden');
            // puedes redirigir a menú con mensaje
            header('Location: /menu');
            exit;
        }
    }

    // Página principal editor -> lista opciones
    public function base() {
        $this->requireEditor();
        $data = [];
        $data['nombreDeUsuario'] = $_SESSION['nombreDeUsuario'] ?? null;
        $this->renderer->render('editor/dashboard', $data); // crear vista
    }

    // Mostrar formulario crear pregunta (GET) o procesar creación (POST)
    public function createQuestion() {
        $this->requireEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // mostrar formulario - necesitas pasar categorías
            // Asumo que tienes CategoriaModel en Factory y puedes instanciarlo aquí si quieres
            $this->renderer->render('editor/createQuestion', []);
            return;
        }

        // POST -> procesar
        $categoriaId = intval($_POST['categoria'] ?? 0);
        $pregunta = trim($_POST['pregunta'] ?? '');
        $opciones = [
            'A' => trim($_POST['opcion_a'] ?? ''),
            'B' => trim($_POST['opcion_b'] ?? ''),
            'C' => trim($_POST['opcion_c'] ?? ''),
            'D' => trim($_POST['opcion_d'] ?? '')
        ];
        $correcta = strtoupper(trim($_POST['correcta'] ?? 'A'));
        $creatorId = $_SESSION['user_id'] ?? null;

        try {
            $newId = $this->model->createQuestion($categoriaId, $pregunta, $opciones, $correcta, $creatorId);
            // redirigir a edición de la nueva pregunta o al dashboard
            header("Location: /?controller=editor&method=editQuestion&id=".$newId);
            exit;
        } catch (Exception $e) {
            $this->renderer->render('editor/createQuestion', ['error' => $e->getMessage()]);
        }
    }

    // Editar pregunta (GET para mostrar, POST para guardar)
    public function editQuestion() {
        $this->requireEditor();
        $questionId = isset($_GET['id']) ? intval($_GET['id']) : null;
        if (!$questionId) {
            header('Location: /?controller=editor&method=base');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Traer pregunta actual desde DB
            $sql = "SELECT * FROM preguntas WHERE id = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $questionId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $this->renderer->render('editor/editQuestion', ['pregunta' => $result]);
            return;
        }

        // POST -> guardar cambios
        $categoriaId = intval($_POST['categoria'] ?? 0);
        $pregunta = trim($_POST['pregunta'] ?? '');
        $opciones = [
            'A' => trim($_POST['opcion_a'] ?? ''),
            'B' => trim($_POST['opcion_b'] ?? ''),
            'C' => trim($_POST['opcion_c'] ?? ''),
            'D' => trim($_POST['opcion_d'] ?? '')
        ];
        $correcta = strtoupper(trim($_POST['correcta'] ?? 'A'));

        try {
            $this->model->updateQuestion($questionId, $categoriaId, $pregunta, $opciones, $correcta);
            header('Location: /?controller=editor&method=base');
            exit;
        } catch (Exception $e) {
            $this->renderer->render('editor/editQuestion', ['error' => $e->getMessage()]);
        }
    }

    // Listar reportes pendientes
    public function reports() {
        $this->requireEditor();
        $reports = $this->model->getPendingReports();
        $this->renderer->render('editor/reports', ['reports' => $reports]);
    }

    // Revisar un reporte (accept/reject)
    public function reviewReport() {
        $this->requireEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /?controller=editor&method=reports');
            exit;
        }
        $reportId = intval($_POST['report_id'] ?? 0);
        $action = ($_POST['action'] ?? '') === 'accept' ? 'accepted' : 'rejected';
        $reviewerId = $_SESSION['user_id'];

        try {
            $this->model->reviewReport($reportId, $action, $reviewerId);
            header('Location: /?controller=editor&method=reports');
            exit;
        } catch (Exception $e) {
            $this->renderer->render('editor/reports', ['error' => $e->getMessage()]);
        }
    }

    // Sugerencias pendientes
    public function suggestions() {
        $this->requireEditor();
        $sugs = $this->model->getPendingSuggestions();
        $this->renderer->render('editor/suggestions', ['suggestions' => $sugs]);
    }

    // Aceptar/rehazar sugerencia
    public function reviewSuggestion() {
        $this->requireEditor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /?controller=editor&method=suggestions');
            exit;
        }
        $suggestionId = intval($_POST['suggestion_id'] ?? 0);
        $action = $_POST['action'] ?? 'reject';
        $reviewerId = $_SESSION['user_id'];

        try {
            if ($action === 'accept') {
                $this->model->acceptSuggestion($suggestionId, $reviewerId);
            } else {
                $this->model->rejectSuggestion($suggestionId, $reviewerId);
            }
            header('Location: /?controller=editor&method=suggestions');
            exit;
        } catch (Exception $e) {
            $this->renderer->render('editor/suggestions', ['error' => $e->getMessage()]);
        }
    }
} -->
