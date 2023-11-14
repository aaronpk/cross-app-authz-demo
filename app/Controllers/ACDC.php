<?php
namespace App\Controllers;

use ORM;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ACDC {


  public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $params = $request->getQueryParams();

    $user = $request->getAttribute('user');
    $org = ORM::for_table('orgs')->where('id', $user->org_id)->find_one();

    $todo = new \App\Chips\Todo($user);

    $links = [];
    $links[] = [
      'url' => '/wiki/',
      'name' => 'Home',
    ];      

    return render($response, 'acdc/index', [
      'user' => $user,
      'org' => $org,
      'todo_token_endpoint' => $todo->token_endpoint,
      'navlinks' => $links,
    ]);
  }

  public function post(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $params = (array)$request->getParsedBody();
    $user = $request->getAttribute('user');

    $todo = new \App\Chips\Todo($user);

    switch($params['step']) {
      case 'acdc':

        $acdc = $todo->requestACDCWithIDToken($user->id_token);
        $text = $acdc['raw_response'];

        $response = $response->withHeader('Content-type', 'application/json');
        $response->getBody()->write(json_encode([
          'text' => $text,
          'response' => $acdc,
        ]));
        break;

      case 'token':

        $token = $todo->requestTokenWithACDC($params['acdc']);
        $text = $token['raw_response'];

        $response = $response->withHeader('Content-type', 'application/json');
        $response->getBody()->write(json_encode([
          'text' => $text,
          'response' => $token,
        ]));

        if(isset($token['access_token'])) {
          $externalToken = ORM::for_table('external_tokens')->create();
          $externalToken->user_id = $user->id;
          $externalToken->app_name = 'Todo';
          $externalToken->access_token = $token['access_token'];
          $externalToken->created_at = date('Y-m-d H:i:s');
          if(isset($token['expires_in'])) {
            $externalToken['expires_at'] = date('Y-m-d H:i:s', time()+$token['expires_in']);
          }
          $externalToken->save();
        }

        break;
    }

    return $response;
  }


}
