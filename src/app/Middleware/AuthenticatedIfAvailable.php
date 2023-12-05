<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use ORM;

class AuthenticatedIfAvailable {

  public function __invoke(Request $request, RequestHandler $handler): Response {

    $user = $this->_getUserFromSession();

    if($user)
      $request = $request->withAttribute('user', $user);

    return $handler->handle($request);
  }

  private function _getUserFromSession() {
    if(!isset($_SESSION['user_id'])) {
      return false;
    }

    $user = ORM::for_table('users')
      ->where('id', $_SESSION['user_id'])
      ->find_one();

    if(!$user) {
      return false;
    }

    return $user;
  }

}
