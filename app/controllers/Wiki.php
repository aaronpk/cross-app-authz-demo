<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ORM;
use League\CommonMark\CommonMarkConverter;

class Wiki {

  public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

    return render($response, 'wiki/index', [

    ]);
  }

  public function home(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

    $user = $request->getAttribute('user');

    $page = ORM::for_table('pages')
      ->where('org_id', $user->org_id)
      ->where('slug', '')
      ->find_one();

    if(!$page) {
      $page = ORM::for_table('pages')->create();
      $page->org_id = $user->org_id;
      $page->slug = '';
      $page->text = "Welcome to your new wiki! Edit this page to get started. Things to try:\n\n* Create lists with markdown\n* Link to other pages by wrapping the page name in double square brackets";
      $page->created_by = $user->id;
      $page->created_at = date('Y-m-d H:i:s');
      $page->save();
    }

    $html = $this->_renderWikiHTML($page);

    return render($response, 'wiki/page', [
      'page_html' => $html,
      'user' => $user,
    ]);
  }

  public function page(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface {

    $user = $request->getAttribute('user');

    $page = ORM::for_table('pages')
      ->where('org_id', $user->org_id)
      ->where('slug', $params['page'])
      ->find_one();

    if(!$page) {
      // TODO: Show empty page editor
      return $response->withStatus(404);
    }

    $html = $this->_renderWikiHTML($page);

    return render($response, 'wiki/page', [
      'page_html' => $html,
      'user' => $user,
    ]);
  }

  private function _renderWikiHTML(&$page) {

    $converter = new CommonMarkConverter([
      'html_input' => 'strip',
      'allow_unsafe_links' => false,
    ]);

    $text = $page->text;

    logger()->debug($text);

    // Preprocess

    // Convert wiki links to HTML links
    $text = preg_replace_callback('/\[\[(.+?)\]\]/', function($matches) {
      $name = $matches[1];
      return '['.$name.']('.$_ENV['BASE_URL'].'wiki/'.urlencode($name).')';
    }, $text);

    logger()->debug($text);

    $html = $converter->convert($text);

    return $html;
  }




  ///// TODO BELOW




  public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $params = (array)$request->getParsedBody();

    if(empty($params['name'])) {
      return $response
        ->withHeader('Location', '/dashboard')
        ->withStatus(302);
    }

    $user = $request->getAttribute('user');

    $todo = ORM::for_table('todos')->create();
    $todo->user_id = $user->id;
    $todo->org_id = $user->org_id;
    $todo->name = $params['name'];
    $todo->created_at = date('Y-m-d H:i:s');
    $todo->save();

    return $response
      ->withHeader('Location', '/dashboard')
      ->withStatus(302);
  }

  public function todo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {

    $user = $request->getAttribute('user');

    $todo = ORM::for_table('todos')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$todo) {
      return $response->withStatus(404);
    }

    $meta = '<meta property="og:title" content="TODO"/>';
    $meta = '<meta property="og:description" content="'.e($todo->name).'"/>';

    return render($response, 'wiki/page', [
      'meta' => $meta,
      'user' => $user,
      'todo' => $todo,
    ]);
  }

  public function todo_json(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {

    $user = $request->getAttribute('user');

    $todo = ORM::for_table('todos')
      ->where('user_id', $user->id)
      ->where('id', $args['id'])
      ->find_one();

    if(!$todo) {
      return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode([
      'id' => $todo->id,
      'name' => $todo->name,
      'created_at' => $todo->created_at,
      'completed_at' => $todo->completed_at,
    ]));
    return $response;
  }

}

