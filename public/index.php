<?php
require '../helpers.php';

require basePath('Framework/Router.php');
require basePath('Framework/Session.php');

Session::start();

$router = new Router;

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];


$routes = require basePath('routes.php');

$router->dispatch($url, $method);
