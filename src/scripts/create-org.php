<?php
require_once(__DIR__.'/../vendor/autoload.php');

// Args:
// 1: email domain
// 2: issuer domain
// 3: client ID
// 4: client secret

if(count($argv) != 5) {
  echo "Usage: scripts/create-org.php <EMAIL_DOMAIN> <ISSUER_DOMAIN> <CLIENT_ID> <CLIENT_SECRET>\n";
  die();
}

$emailDomain = $argv[1];

if(!preg_match('/^[a-z0-9].+\..+$/', $emailDomain)) {
  echo "Invalid email domain: $emailDomain\n";
  die();
}

echo "Email domain: $emailDomain\n";


$issuerDomain = $argv[2];

if(!preg_match('/^[a-z0-9].+\..+$/', $issuerDomain)) {
  echo "Invalid issuer domain: $issuerDomain\n";
  echo "Enter only the domain, no https\n";
  die();
}

$issuerURL = 'https://'.$issuerDomain;

echo "Issuer URL: $issuerURL\n";

$clientID = $argv[3];
$clientSecret = $argv[4];

// Fetch the OpenID config to find the metadata

$metadataURL = $issuerURL.'/.well-known/openid-configuration';

try {
  $guzzle = new \GuzzleHttp\Client;
  $response = $guzzle->request('GET', $metadataURL);
  $responseBody = (string)$response->getBody();
  $metadata = json_decode($responseBody, true);
} catch(Exception $e) {
  echo "Error fetching OpenID server configuration from\n";
  echo $metadataURL."\n";
  echo $e->getMessage();
  die();
}

$authorizationEndpoint = $metadata['authorization_endpoint'];
$tokenEndpoint = $metadata['token_endpoint'];


$org = ORM::for_table('orgs')->create();
$org->domain = $emailDomain;
$org->issuer = $issuerURL;
$org->client_id = $clientID;
$org->client_secret = $clientSecret;
$org->authorization_endpoint = $authorizationEndpoint;
$org->token_endpoint = $tokenEndpoint;
$org->save();

echo "\n";
echo "Successfully created org!\n";
echo "\n";
echo "OpenID Connect Redirect URI:\n";
echo $_ENV['BASE_URL'].'openid/callback/'.$org->id."\n";
echo "\n";
echo "You can now log in\n";
echo $_ENV['BASE_URL']."\n";



