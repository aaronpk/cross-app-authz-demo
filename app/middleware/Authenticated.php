<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use ORM;

class Authenticated {

  public function __invoke(Request $request, RequestHandler $handler): Response {

    $user = $this->_getUserFromSession();
    if(!$user) {
      $user = $this->_getUserFromAccessToken($request);
    }

    if(!$user) {
      return $this->_errorResponse();
    }

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

  private function _getUserFromAccessToken(Request &$request) {
    $accessToken = $this->_getAccessTokenFromHeader();

    if(!$accessToken) {
      return false;
    }

    $tokenInfo = ORM::for_table('tokens')
      ->where('token', $accessToken)
      ->find_one();

    if(!$tokenInfo) {
      return false;
    }

    $scopes = explode(' ', ($tokenInfo['scope'] ?? ''));

    $request = $request->withAttribute('scope', $scopes);

    $user = ORM::for_table('users')
      ->where('id', $tokenInfo['user_id'])
      ->find_one();

    return $user;
  }

  private function _getAccessTokenFromHeader() {
    if(!isset($_SERVER['HTTP_AUTHORIZATION'])) {
      return false;
    }

    $accessToken = explode(' ', $_SERVER['HTTP_AUTHORIZATION'])[1];

    return $accessToken;
  }

  private function _errorResponse() {
    $response = new Response();
    return $response->withHeader('Location', '/')->withStatus(302);
  }

}
