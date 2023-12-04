<?php
namespace App\Controllers;

use ORM;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OpenID {

  public function start(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $params = (array)$request->getParsedBody();

    $email = (string)($params['email'] ?? '');

    // Look up org from input email address
    if(!preg_match('/.+@(.+)/', $email, $match)) {
      return render($response, 'login/error', [
        'error' => 'Invalid email address entered'
      ]);
    }

    $domain = $match[1];

    $org = ORM::for_table('orgs')->where('domain', $domain)->find_one();

    if(!$org) {
      // If no org exists, display an error
      return render($response, 'login/error', [
        'error' => 'Sorry we couldn\'t find a way to log in you in with this email address. There is no organization registered matching your email domain.'
      ]);
    }

    // Initiate the OpenID flow and redirect

    $_SESSION['state'] = bin2hex(random_bytes(10));
    $_SESSION['code_verifier'] = bin2hex(random_bytes(50));
    $code_challenge = base64_urlencode(hash('sha256', $_SESSION['code_verifier'], true));

    $url = $org->authorization_endpoint . '?' . http_build_query([
      'response_type' => 'code',
      'client_id' => $org->client_id,
      'redirect_uri' => $_ENV['BASE_URL'].'openid/callback/'.$org->id,
      'scope' => 'openid profile email',
      'state' => $_SESSION['state'],
      'code_challenge' => $code_challenge,
      'code_challenge_method' => 'S256',
      'login_hint' => $email,
    ]);

    return $response
      ->withHeader('Location', $url)
      ->withStatus(302);
  }

  public function redirect(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
    $params = $request->getQueryParams();

    if(!isset($params['code'])) {
      return render($response, 'login/error', [
        'error' => 'Error with OpenID login. Check the query string for details.'
      ]);
    }

    if($params['state'] != $_SESSION['state']) {
      return render($response, 'login/error', [
        'error' => 'Invalid state. Try logging in again.'
      ]);
    }

    $org = ORM::for_table('orgs')->where('id', $args['id'])->find_one();

    if(!$org) {
      return render($response, 'login/error', [
        'error' => 'No org found'
      ]);
    }


    $client = new \GuzzleHttp\Client([
      'timeout' => 10
    ]);

    try {
      $response = $client->request('POST', $org->token_endpoint, [
        'form_params' => [
          'grant_type' => 'authorization_code',
          'code' => $params['code'],
          'code_verifier' => $_SESSION['code_verifier'],
          'redirect_uri' => $_ENV['BASE_URL'].'openid/callback/'.$org->id,
          'client_id' => $org->client_id,
          'client_secret' => $org->client_secret,
        ]
      ]);
    } catch(\GuzzleHttp\Exception\TransferException $e) {
      // logger()->debug($e->getMessage());
      $details = null;
      if($e->hasResponse()) {
        $body = (string)$e->getResponse()->getBody();
        $details = json_decode($body, true);
        // logger()->debug((string)$e->getResponse()->getBody());
      }
      return render($response, 'login/error', [
        'error' => json_encode($details)
      ]);
    }


    $body = (string)$response->getBody();
    $info = json_decode($body, true);

    if(!$info || !isset($info['id_token'])) {
      return view('login/error', [
        'error' => 'OAuth Error',
        'error_description' => 'The OpenID Connect server returned an invalid response.',
      ]);
    }

    $id_token = $info['id_token'];
    $claims_component = explode('.', $id_token)[1];
    $userinfo = json_decode(base64_decode($claims_component), true);

    $user = ORM::for_table('users')
      ->where('org_id', $org->id)
      ->where('sub', $userinfo['sub'])
      ->find_one();

    if(!$user) {
      $user = ORM::for_table('users')->create();
      $user->org_id = $org->id;
      $user->sub = $userinfo['sub'];
    }

    $user->name = $userinfo['name'] ?? '';
    $user->email = $userinfo['email'] ?? '';
    $user->last_login = date('Y-m-d H:i:s');
    $user->id_token = $id_token;
    $user->save();

    $_SESSION['user_id'] = $user->id;

    return $response
      ->withHeader('Location', '/logged-in')
      ->withStatus(302);
  }

  public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    unset($_SESSION['user_id']);
    session_destroy();

    return $response
      ->withHeader('Location', '/')
      ->withStatus(302);
  }
}

