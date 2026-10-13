<?php

try {
function load(string $class, string $function){
    $classnamespace = "Classes\\$class";

    if(!class_exists($classnamespace)){
        throw new Exception('Classe inexistente');
    }

    $classOb = new $classnamespace;

    if(!method_exists($classOb, $function)){
        throw new Exception('Método inexistente');
    }

    $jsonRaw = file_get_contents('php://input');
    $data = json_decode($jsonRaw, true);

    if (!is_array($data)) {
        $response['message'] = 'Corpo inválido ou ausente. Envie JSON válido.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }


    echo $classOb->$function($data);
}

$routes = [
    'user/new' => fn() => load('User', 'newUser'),
    'user/login' => fn() => load('User', 'login'),
    'questions/fase' => fn() => load('User', 'getQuestion'),
];

}
catch(Exception $e){
    echo $e->getMessage();
}