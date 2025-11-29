<?php
// MODO DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (empty($_GET['direccion'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó dirección']);
    exit;
}

$direccion_raw = $_GET['direccion'];
$direccion_encoded = urlencode($direccion_raw);

$url = "https://nominatim.openstreetmap.org/search?q={$direccion_encoded}&format=json&limit=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "PreguntadosApp/1.0 (tahielrecchia05@gmail.com)");
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($ch);


if (curl_errno($ch)) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["error" => "Error en cURL: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

/
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