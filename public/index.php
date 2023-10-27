<?php
require_once(__DIR__.'/../vendor/autoload.php');

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers;
use App\Middleware;

session_start();

$app = AppFactory::create();

$app->get('/', [Controllers\Main::class, 'index']);
$app->post('/login', [Controllers\OpenID::class, 'start']);
$app->post('/logout', [Controllers\OpenID::class, 'logout']);
$app->get('/openid/callback/{id}', [Controllers\OpenID::class, 'redirect']);

$app->group('', function(RouteCollectorProxy $group){

  $group->get('/dashboard', [Controllers\Todos::class, 'dashboard']);
  $group->get('/todo/{id}.json', [Controllers\Todos::class, 'todo_json']);
  $group->get('/todo/{id}_json', [Controllers\Todos::class, 'todo_json']);
  $group->get('/todo/{id}', [Controllers\Todos::class, 'todo']);
  $group->post('/todos/create', [Controllers\Todos::class, 'create']);
  $group->post('/todos/edit', [Controllers\Todos::class, 'edit']);

})->add(Middleware\Authenticated::class);

$app->run();
