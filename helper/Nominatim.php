<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // ⚠️ No mostrar errores en producción
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Validar parámetros
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan parámetros lat y lon"]);
    exit;
}

$lat = $_GET['lat'];
$lon = $_GET['lon'];

$url = "https://nominatim.openstreetmap.org/reverse?lat=$lat&lon=$lon&format=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "PreguntadosApp/1.0 (tuccieliasluis@gmail.com)");

// Desactivar verificación SSL (solo desarrollo local)
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => "Error en cURL: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Si Nominatim respondió algo inválido
if ($httpCode !== 200 || !$response) {
    echo json_encode([
        "error" => "Respuesta inválida de Nominatim",
        "http_code" => $httpCode,
        "raw" => $response
    ]);
    exit;
}

// 🔥 DEVOLVER SOLO EL JSON ORIGINAL (necesario para response.json())
echo $response;
exit;
