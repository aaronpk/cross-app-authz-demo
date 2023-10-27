<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use ORM;

class Authenticated {

  public function __invoke(Request $request, RequestHandler $handler): Response {

    if(!isset($_SESSION['user_id'])) {
      return $this->_errorResponse();
    }

    $user = ORM::for_table('users')->where('id', $_SESSION['user_id'])->find_one();
    if(!$user) {
      return $this->_errorResponse();
    }

    $request = $request->withAttribute('user', $user);

    return $handler->handle($request);
  }

  private function _errorResponse() {
    $response = new Response();
    return $response->withHeader('Location', '/')->withStatus(302);
  }

}
