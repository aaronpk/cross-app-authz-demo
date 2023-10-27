<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Main {

  public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {


    return render($response, 'index', [

    ]);
  }

}

