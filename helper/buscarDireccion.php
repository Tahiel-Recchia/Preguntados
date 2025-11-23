<?php
// MODO DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Validar
if (empty($_GET['direccion'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó dirección']);
    exit;
}

// --- INICIO DE LA SOLUCIÓN ---

// 2. Preparar la URL para Nominatim
$direccion_raw = $_GET['direccion']; // Contiene "Lima, Perú"
$direccion_encoded = urlencode($direccion_raw); // Lo convierte a "Lima%2C+Per%C3%BA"

// Ahora usamos la variable codificada para construir la URL
$url = "https://nominatim.openstreetmap.org/search?q={$direccion_encoded}&format=json&limit=1";

// --- FIN DE LA SOLUCIÓN ---


// 3. Usar cURL para la conexión
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "PreguntadosApp/1.0 (tahielrecchia05@gmail.com)");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($ch);

// 4. Manejar errores de cURL
if (curl_errno($ch)) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["error" => "Error en cURL: " . curl_error($ch)]); // Esto fue lo que vimos
    curl_close($ch);
    exit;
}

// 5. Manejar errores de Nominatim
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    header('Content-Type: application/json');
    http_response_code($httpCode);
    echo json_encode(["error" => "Respuesta inválida de Nominatim, código: " . $httpCode]);
    exit;
}

// 6. Si salió bien, devolver la respuesta JSON
header('Content-Type: application/json');
echo $response;

?>