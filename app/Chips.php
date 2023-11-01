<?php
namespace App;
use ORM;

abstract class Chips {

  protected $_config;
  protected $_user;

  public function getTokenForUser() {

    $class = str_replace('App\Chips\\', '', static::class);

    $token = ORM::for_table('external_tokens')
      ->where('app_name', $class)
      ->where('user_id', $this->_user->id)
      ->find_one();

    if(!$token) {
      // TODO: Fetch token using ACDC

    }

    return $token;
  }

  public function requestACDCWithIDToken($idToken) {
    $user = $this->_user;
    $org = ORM::for_table('orgs')->where('id', $user->org_id)->find_one();

    $client = new \GuzzleHttp\Client([
      'timeout' => 10
    ]);

    // Exchange the ID Token for an ACDC code 

    try {
      $params = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
        'requested_token_type' => 'urn:ietf:params:oauth:grant-type:jwt-acdc',
        'audience' => $this->_config['CLIENT_ID'],
        'scope' => '',
        'subject_token_type' => 'urn:ietf:params:oauth:token-type:id_token',
        'subject_token' => $user->id_token,
        'client_id' => $org->client_id,
        'client_secret' => $org->client_secret,
      ];
      $response = $client->request('POST', $org->token_endpoint, [
        'form_params' => $params
      ]);
      $body = (string)$response->getBody();
    } catch(\GuzzleHttp\Exception\TransferException $e) {
      if($e->hasResponse()) {
        $body = (string)$e->getResponse()->getBody();
        $details = json_decode($body, true);
      }      
    }

    $info = json_decode($body, true);
    $info['raw_response'] = $body;


    // Fake an ACDC
    // $acdc = base64_urlencode(json_encode(['typ'=>'acdc+jwt']))
    //   .'.'.base64_urlencode(json_encode([
    //     'iss' => $org->issuer,
    //     'sub' => $user->sub,
    //     'aud' => '0oad0ulqgzalQFzI45d7',
    //     'azp' => $this->_config['CLIENT_ID'],
    //     'exp' => time()+600,
    //     'iat' => time(),
    //     'scopes' => [],
    //   ])).'.'.base64_urlencode('signature');

    // $info = [
    //   'acdc' => $acdc,
    //   'token_type' => 'urn:ietf:params:oauth:token-type:jwt-acdc',
    // ];

    // $info['raw_response'] = json_encode($info);


    return $info;
  }

  public function requestACDCWithSAML($samlAssertion) {

  }

  public function requestTokenWithACDC($acdc) {

    $client = new \GuzzleHttp\Client([
      'timeout' => 10
    ]);

    try {
      $params = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-acdc',
        'acdc' => $acdc,
        'client_id' => $this->_config['CLIENT_ID'],
        'client_secret' => $this->_config['CLIENT_SECRET'],
      ];
      $response = $client->request('POST', $this->_config['TOKEN_ENDPOINT'], [
        'form_params' => $params
      ]);
      $body = (string)$response->getBody();
    } catch(\GuzzleHttp\Exception\TransferException $e) {
      if($e->hasResponse()) {
        $body = (string)$e->getResponse()->getBody();
        $details = json_decode($body, true);
      }
    }

    $info = json_decode($body, true);
    $info['raw_response'] = $body;

    return $info;
  }

}

