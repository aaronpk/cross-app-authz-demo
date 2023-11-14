<?php
require_once(__DIR__.'/../vendor/autoload.php');

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers;
use App\Middleware;

session_set_cookie_params(86400*30);
session_start();

$app = AppFactory::create();

$app->post('/login', [Controllers\OpenID::class, 'start']);
$app->post('/logout', [Controllers\OpenID::class, 'logout']);
$app->get('/openid/callback/{id}', [Controllers\OpenID::class, 'redirect']);

$app->post('/oauth/token', [Controllers\TokenEndpoint::class, 'token']);


$app->group('', function(RouteCollectorProxy $group){
  switch($_ENV['SITE_TYPE']) {
    case 'todo':
      $group->get('/', [Controllers\Todos::class, 'index']);
      break;
    case 'wiki':
      $group->get('/', [Controllers\Wiki::class, 'index']);
      break;
  }
})->add(Middleware\AuthenticatedIfAvailable::class);

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
      $group->get('/wiki/', [Controllers\Wiki::class, 'home']);
      $group->get('/wiki/{page}', [Controllers\Wiki::class, 'page']);
      $group->get('/edit', [Controllers\Wiki::class, 'edit']);
      $group->post('/edit/save', [Controllers\Wiki::class, 'save']);

      $group->redirect('/logged-in', '/oauth/acdc');
      $group->get('/oauth/acdc', [Controllers\ACDC::class, 'get']);
      $group->post('/oauth/acdc', [Controllers\ACDC::class, 'post']);

      break;
  }


})->add(Middleware\Authenticated::class);

$app->run();
