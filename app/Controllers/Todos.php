<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;

class Todos {

  public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

    return render($response, 'todo/index', [

    ]);
  }

  public function dashboard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

    $user = $request->getAttribute('user');
    $todos = ORM::for_table('todos')->where('user_id', $user->id)->find_many();

    return render($response, 'todo/dashboard', [
      'user' => $user,
      'todos' => $todos,
    ]);
  }

  public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $params = (array)$request->getParsedBody();

    if(empty($params['name'])) {
      return $response
        ->withHeader('Location', '/dashboard')
        ->withStatus(302);
    }

    $user = $request->getAttribute('user');

    $todo = ORM::for_table('todos')->create();
    $todo->user_id = $user->id;
    $todo->org_id = $user->org_id;
    $todo->name = $params['name'];
    $todo->created_at = date('Y-m-d H:i:s');
    $todo->save();

    return $response
      ->withHeader('Location', '/dashboard')
      ->withStatus(302);
  }

  public function todo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {

    $user = $request->getAttribute('user');

    $todo = ORM::for_table('todos')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$todo) {
      return $response->withStatus(404);
    }

    $meta = '<meta property="og:title" content="TODO"/>';
    $meta = '<meta property="og:description" content="'.e($todo->name).'"/>';

    return render($response, 'todo/todo', [
      'meta' => $meta,
      'user' => $user,
      'todo' => $todo,
    ]);
  }

  public function todo_json(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {

    $user = $request->getAttribute('user');

    $todo = ORM::for_table('todos')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$todo) {
      return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode([
      'id' => $todo->id,
      'name' => $todo->name,
      'created_at' => $todo->created_at,
      'completed_at' => $todo->completed_at,
    ]));
    return $response;
  }

}

