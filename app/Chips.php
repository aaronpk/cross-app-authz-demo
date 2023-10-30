<?php
namespace App;
use ORM;

class Chips {


  public function getTokenForUser(&$user) {

    $class = str_replace('App\Chips\\', '', static::class);

    $token = ORM::for_table('external_tokens')
      ->where('app_name', $class)
      ->where('user_id', $user->id)
      ->find_one();

    if(!$token) {
      // TODO: Fetch token using ACDC

    }

    return $token;
  }

}

