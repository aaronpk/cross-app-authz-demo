<?php
require_once(__DIR__.'/vendor/autoload.php');

// Find all users who logged in within the last 5 minutes
$users = ORM::for_table('users')
  ->where_gt('last_login', date('Y-m-d H:i:s', time()-300))
  ->find_many();

foreach($users as $user) {

  if($user->id_token) {

    // TODO: Do this for every configured app

    echo "Requesting ACDC...\n";

    $todo = new \App\Chips\Todo($user);
    $acdc = $todo->requestACDCWithIDToken($user->id_token);

    echo "Got ACDC from IdP\n";
    print_r($acdc);

    echo "Requesting Token...\n";

    $token = $todo->requestTokenWithACDC($acdc);

    echo "Got token from Todo app\n";
    print_r($token);

  }


}
