<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/routes/router.php';
 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$response = [
    'status' => 'error',
    'message' => 'Requisição inválida',
    'method' => $method,
    'received' => null,
];

if ($method === 'POST') {

    $uri = ltrim(parse_url($_SERVER['REQUEST_URI'])['path'], '/');

    error_log("Received request for URI: $uri");
    error_log("Available routes: " . json_encode($routes));

    if(!array_key_exists($uri, $routes)){
        throw new Exception("URL não existe");
    }

    $action = $routes[$uri];
    $action();
}
} catch (Exception $e) {
    echo $e->getMessage();
}