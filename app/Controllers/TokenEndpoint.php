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

    logger()->debug('Processing token request', ['grant_type' => $params['grant_type']]);

    switch($params['grant_type'] ?? '') {

      case 'client_credentials':
        return $this->_clientCredentialsGrant($request);

      case 'urn:ietf:params:oauth:grant-type:jwt-acdc':
        return $this->_acdcGrant($request);

      default:
        return $this->_jsonError('invalid_request', 400);
    }

  }

  private function _clientCredentialsGrant(Request &$request) {
    // Require client authentication
    $client = $this->_getClient($request, true);
    if(!$client) {
      return $this->_jsonError('unauthorized');
    }

    $user = null;
    $org  = null;

    // TODO: Accept user or org parameters if the user/org has already authorized this client

    $token = $this->_generateAccessToken($client, $user, $org);

    return $this->_tokenResponse($token);
  }

  private function _acdcGrant(Request $request) {
    // Require client authentication
    $client = $this->_getClient($request, true);
    if(!$client) {
      return $this->_jsonError('unauthorized');
    }

    $params = (array)$request->getParsedBody();

    if(!isset($params['acdc'])) {
      return $this->_jsonError('invalid_grant', 400);
    }

    if(!preg_match('/(.+)\.(.+)\.(.+)/', $params['acdc'], $match)) {
      return $this->_jsonError('invalid_grant', 400);
    }
    $headerComponent = $match[1];
    $claimsComponent = $match[2];
    $signature = $match[3];

    $claims = json_decode(base64_urldecode($claimsComponent), true);

    // JWT signature is not yet verified

    // TODO: Validate JWT signature by looking up public key of issuer

    // FUTURE: Don't bother validating JWTs from issuers we don't know about.
    // The issuer should always match an issuer in the orgs table.

    // Validate exp, iat
    if($claims['exp'] > time()) {
      return $this->_jsonError('invalid_grant', 400, 'ACDC expired');
    }

    // azp (authorized party) - which client the ACDC was issued to by the IdP.
    // azp is the client ID in the context of this application, mapping was done by the IdP.
    // Validate azp matches client authentication.
    if($claims['azp'] != $client->client_id) {
      return $this->_jsonError('invalid_grant', 400, 'azp does not match client authentication');
    }

    // Tenancy is established by the iss + client_id pair in the orgs table

    // Look up the org by the iss + aud of the token
    $org = ORM::for_table('orgs')
      ->where('issuer', $claims['iss'])
      ->where('client_id', $claims['aud'])
      ->find_one();

    if(!$org) {
      return $this->_jsonError('invalid_grant', 400, 'Unknown tenant');
    }

    // Look up user
    // We should know about them already. What happens if not?
    $user = ORM::for_table('users')
      ->where('sub', $claims['sub'])
      ->find_one();

    if(!$user) {
      // FUTURE: If there is an `email` claim, look up the user that way too
    }

    if(!$user) {
      return $this->_jsonError('invalid_grant', 400, 'The user identified by sub was not found');
    }

    $scope = implode(' ', $claims['scopes']);

    // All validations succeeded, issue an access token
    $accessToken = $this->_generateAccessToken($client, $user, $org, $scope);

    return $this->_tokenResponse($accessToken);
  }

  private function _generateAccessToken($client, $user, $org, $scope='') {
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

  private function _jsonError($error, $code=401, $description=null) {
    $response = new Response();
    $data = ['error' => $error];
    if($description)
      $data['error_description'] = $description;
    $error = json_encode($data);
    $response->getBody()->write($error);
    return $response->withStatus($code);
  }

}
