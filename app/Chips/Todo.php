<?php
namespace App\Chips;
use ORM;

class Todo extends \App\Chips {

  public static $configPrefix = 'TODO';
  public static $configItems = [
    'HOSTNAME',
    'TOKEN_ENDPOINT',
    'CLIENT_ID',
    'CLIENT_SECRET',
  ];

  public function __construct(&$user) {
    foreach(self::$configItems as $item) {
      $this->_config[$item] = $_ENV[self::$configPrefix.'_'.$item];
    }

    $this->_user = $user;
  }

  public function regex() {
    return '/(https:\/\/'.str_replace('.', '\.', $this->_config['HOSTNAME']).'\/todo\/(\d+))[^0-9]/';
  }

  public function matches($text) {
    $matches = [];
    if(preg_match_all($this->regex(), $text, $m, PREG_OFFSET_CAPTURE)) {
      return $m;
    }
    return [];
  }

  public function urlForTodo($id) {
    return 'https://'.$this->_config['HOSTNAME'].'/todo/'.$id.'.json';
  }

  public function saveExpandedLinks($text) {
    $matches = $this->matches($text);

    if(count($matches)) {
      foreach($matches[1] as $i=>$m) {
        $todoId = $matches[2][$i][0];
        $url = $m[0];
        $offset = $m[1];

        // Get a cached token or fetch via ACDC grant
        $token = $this->getTokenForUser();

        if($token) {
          // Fetch the object
          $guzzle = new \GuzzleHttp\Client;
          try {
            $response = $guzzle->request('GET', $this->urlForTodo($todoId), [
              'headers' => [
                'Authorization' => 'Bearer '.$token->access_token,
              ]
            ]);
            $responseBody = (string)$response->getBody();
            $data = json_decode($responseBody, true);

            if($data) {

              $link = ORM::for_table('link_expansions')
                ->where('url', $url)
                ->find_one();
              if(!$link) {
                $link = ORM::for_table('link_expansions')->create();
                $link->url = $url;
                $link->created_at = date('Y-m-d H:i:s');
              }

              $link->data = json_encode($data, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
              $link->updated_at = date('Y-m-d H:i:s');
              $link->save();

            }
          } catch(\GuzzleHttp\Exception\TransferException $e) {
            // Something went wrong
          }
        } else {
          // Couldn't get a token for this user
        }
      }
    }

  }

  public function chipHTML($url) {
    $link = ORM::for_table('link_expansions')
      ->where('url', $url)
      ->find_one();
    if(!$link) {
      return '<a href="'.$url.'">'.$url.'</a>';
    }

    $data = json_decode($link->data, true);

    return '<span class="chip app-todo"><a href="'.$url.'"><img src="/images/t0.png"> '.$data['name'].'</a></span>';
  }

}

