<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use ORM;

class TokenEndpoint {

  public function token(Request $request, ResponseInterface $response): ResponseInterface {
    $params = (array)$request->getParsedBody();

    // Client Authentication
    $client = $this->_getClient($request);

    // Fail now if the client_id is invalid
    if(!$client) {
      return $this->_jsonError('unauthorized');
    }

    switch($params['grant_type'] ?? '') {

      case 'client_credentials':
        return $this->_clientCredentialsGrant($request);

      default:
        return $this->_jsonError('invalid_request', 400);
    }

  }

  private function _clientCredentialsGrant(Request &$request) {
    $client = $this->_getClient($request, true);
    if(!$client) {
      return $this->_jsonError('unauthorized');
    }

    $user = ORM::for_table('users')->where('id', $client->user_id)->find_one();

    $token = $this->_generateAccessToken($client, $user);

    return $this->_tokenResponse($token);
  }

  private function _generateAccessToken($client, $user, $scope='') {
    $token = ORM::for_table('tokens')->create();
    $token->user_id = $user->id;
    $token->client_id = $client->id;
    $token->scope = $scope;
    $token->created_at = date('Y-m-d H:i:s');
    $token->token = bin2hex(random_bytes(50));
    $token->save();

    return $token;
  }

  /* Look up the client from the client_id param or HTTP Basic Auth username */
  private function _getClient(Request $request, $requireAuth=false) {

    if($request->hasHeader('Authorization')) {
      $parts = explode(' ', $request->getHeaderLine('Authorization'));
      if($parts[0] != 'Basic') {
        return null;
      }

      $clientInfo = base64_decode($parts[1]);

      if(!preg_match('/^(.+):(.+)$/', ($clientInfo ?? ''), $match)) {
        return null;
      }

      if($requireAuth) {
        $client = ORM::for_table('clients')
          ->where('client_id', $match[1])
          ->where('client_secret', $match[2])
          ->find_one();
      } else {
        $client = ORM::for_table('clients')
          ->where('client_id', $match[1])
          ->find_one();
      }

      return $client;
    }

    $params = (array)$request->getParsedBody();

    if(isset($params['client_id'])) {
      if($requireAuth) {
        $client = ORM::for_table('clients')
          ->where('client_id', $params['client_id'])
          ->where('client_secret', ($params['client_secret'] ?? ''))
          ->find_one();
      } else {
        $client = ORM::for_table('clients')
          ->where('client_id', $params['client_id'])
          ->find_one();
      }

      return $client;
    }

    return null;
  }

  private function _tokenResponse($token) {
    $response = new Response();
    $tokenResponse = json_encode([
      'token_type' => 'Bearer',
      'access_token' => $token->token,
    ]);
    $response->getBody()->write($tokenResponse);
    return $response->withStatus(200);
  }

  private function _jsonError($error, $code=401) {
    $response = new Response();
    $error = json_encode(['error' => $error]);
    $response->getBody()->write($error);
    return $response->withStatus($code);
  }

}
