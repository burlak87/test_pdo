<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/autoload.php');

$contentType = $_SERVER['DOCUMENT_ROOT'];

if(empty($contentType)) {
  die();
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', $request_uri);

$route = [
  'registration' => [
    'controller' => '\App\Controllers\AuthController', 
    'action' => 'registration'
  ],
  'authorization' => [
    'controller' => '\App\Controllers\AuthController', 
    'action' => 'authorization'
  ],
  'logout' => [
    'controller' => '\App\Controllers\AuthController', 
    'action' => 'logout'
  ],
  'users' => '\App\Controllers\UserController'
];

$controllerName = $routes[$uri_parts[2]]['controller'] ?? NULL;
$action = $routes[$uri_parts[2]]['action'];

if(empty($controllerName)) {
  http_response_code(404);
  header('Content-Type: application/json');
  die(json_encode([
    'status' => false,
    'error' => 'NOT FOUND URI'
  ]));
}

if(!class_exists($controllerName)) {
  http_response_code(500);
  header('Content-Type: application/json');
  die(json_encode([
    'status' => false,
    'error' => 'Server Error'
  ]));
}

$entityId = isset($uri_parts[3]) ? (int)$uri_parts[3] : null;

$controllerInstance = new $controllerName;
$controllerInstance->processRequest($_SERVER['REQUEST_METHOD'], $action, $entityId);

?>