<?php
require_once(__DIR__.'/../vendor/autoload.php');

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers;
use App\Middleware;

session_start();

$app = AppFactory::create();

$app->post('/login', [Controllers\OpenID::class, 'start']);
$app->post('/logout', [Controllers\OpenID::class, 'logout']);
$app->get('/openid/callback/{id}', [Controllers\OpenID::class, 'redirect']);

$app->post('/oauth/token', [Controllers\TokenEndpoint::class, 'token']);


switch($_ENV['SITE_TYPE']) {
  case 'todo':
    $app->get('/', [Controllers\Todos::class, 'index']);
    break;
  case 'wiki':
    $app->get('/', [Controllers\Wiki::class, 'index']);
    break;
}

$app->group('', function(RouteCollectorProxy $group){

  switch($_ENV['SITE_TYPE']) {
    case 'todo':
      $group->redirect('/logged-in', '/dashboard');
      $group->get('/dashboard', [Controllers\Todos::class, 'dashboard']);
      $group->get('/todo/{id}.json', [Controllers\Todos::class, 'todo_json']);
      $group->get('/todo/{id}_json', [Controllers\Todos::class, 'todo_json']);
      $group->get('/todo/{id}', [Controllers\Todos::class, 'todo']);
      $group->post('/todos/create', [Controllers\Todos::class, 'create']);
      $group->post('/todos/edit', [Controllers\Todos::class, 'edit']);
      break;
    case 'wiki':
      $group->redirect('/logged-in', '/home');
      $group->get('/home', [Controllers\Wiki::class, 'home']);

      break;
  }


})->add(Middleware\Authenticated::class);

$app->run();
