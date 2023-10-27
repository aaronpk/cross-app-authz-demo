<?php
use Slim\Views\PhpRenderer;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

ORM::configure('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4');
ORM::configure('username', $_ENV['DB_USER']);
ORM::configure('password', $_ENV['DB_PASS']);
ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));

function render($response, $page, $data) {
  $renderer = new PhpRenderer(__DIR__.'/../views/');
  $renderer->setLayout('layouts/layout.php');
  return $renderer->render($response, "$page.php", $data);
};

function base64_urlencode($string) {
  return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
}

function base64_urldecode($input) {
  $remainder = \strlen($input) % 4;
  if ($remainder) {
      $padlen = 4 - $remainder;
      $input .= \str_repeat('=', $padlen);
  }
  return \base64_decode(\strtr($input, '-_', '+/'));
}

function e($text) {
  return htmlspecialchars($text);
}
