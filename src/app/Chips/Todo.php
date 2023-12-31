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
    'SCOPES',
  ];

  public function __construct(&$user) {
    foreach(self::$configItems as $item) {
      $this->_config[$item] = $_ENV[self::$configPrefix.'_'.$item];
    }

    $this->_user = $user;
  }

  public function __get($key) {
    if(isset($this->_config[strtoupper($key)])) {
      return $this->_config[strtoupper($key)];
    }
    return null;
  }

  public function regex() {
    return '/(https?:\/\/'.str_replace('.', '\.', $this->_config['HOSTNAME']).'\/todo\/(\d+))/';
  }

  public function matches($text) {
    $regex = $this->regex();

    logger()->debug('Checking for matches of regex: '.$regex);

    $matches = [];
    if(preg_match_all($regex, $text, $m, PREG_OFFSET_CAPTURE)) {
      return $m;
    }
    return [];
  }

  public function urlForTodo($id) {
    return 'http://'.$this->_config['HOSTNAME'].'/todo/'.$id.'_json';
  }

  public function saveExpandedLinks($text) {
    $matches = $this->matches($text);

    logger()->debug('Found '.count($matches).' Todo URLs to replace');

    if(count($matches)) {
      foreach($matches[1] as $i=>$m) {
        $todoId = $matches[2][$i][0];
        $url = $m[0];
        $offset = $m[1];

        logger()->debug('Fetching Todo ID: '.$todoId.' from URL: '.$url);

        // Get a cached token or fetch via ACDC grant
        $token = $this->getTokenForUser();

        if($token) {
          // Fetch the object
          $guzzle = new \GuzzleHttp\Client;
          try {
            $todoURL = $this->urlForTodo($todoId);
            logger()->debug('Fetching URL: '.$todoURL.' with access token: '.$token->access_token);

            $response = $guzzle->request('GET', $todoURL, [
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
            logger()->debug('HTTP error fetching Todo', ['exception' => $e->getMessage()]);
          }
        } else {
          // Couldn't get a token for this user
          logger()->debug('Couldn\'t find an access token for this user');
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

