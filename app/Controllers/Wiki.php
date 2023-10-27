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
      ->where('slug', 'home')
      ->find_one();

    if(!$page) {
      $page = ORM::for_table('pages')->create();
      $page->org_id = $user->org_id;
      $page->slug = 'home';
      $page->text = "Welcome to your new wiki! Edit this page to get started. Things to try:\n\n* Create lists with [[markdown]]\n* Link to other pages by wrapping the page name in [[double square brackets]]";
      $page->created_by = $user->id;
      $page->created_at = date('Y-m-d H:i:s');
      $page->save();

      $page = ORM::for_table('pages')->create();
      $page->org_id = $user->org_id;
      $page->slug = 'markdown';
      $page->text = "# Markdown Reference\nEdit this page to view the source\n\n* List\n* of\n* items\n\nLink to an internal page: [[home]]\n\n[External link](http://example.com)\n\n# Heading\n\n## Heading Level 2\n\n### Heading Level 3\n\n#### Heading Level 4\n\n**Bold**";
      $page->created_by = $user->id;
      $page->created_at = date('Y-m-d H:i:s');
      $page->save();
    }

    return $this->page($request, $response, ['page' => 'home']);
  }

  public function page(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface {

    $user = $request->getAttribute('user');

    $page = ORM::for_table('pages')
      ->where('org_id', $user->org_id)
      ->where('slug', $params['page'])
      ->find_one();

    if(!$page) {
      return $response->withStatus(302)
        ->withHeader('Location', '/edit?page='.urlencode($params['page']));
    }

    $html = $this->_renderWikiHTML($page);

    $links[] = [
      'url' => '/edit?page='.urlencode($params['page']),
      'name' => 'Edit',
    ];

    return render($response, 'wiki/page', [
      'page_html' => $html,
      'user' => $user,
      'navlinks' => $links,
    ]);
  }

  private function _renderWikiHTML(&$page) {

    $text = $page->text;

    // Convert wiki links to HTML links
    $text = preg_replace_callback('/\[\[(.+?)\]\]/', function($matches) {
      $name = $matches[1];
      return '['.$name.']('.$_ENV['BASE_URL'].'wiki/'.urlencode($name).')';
    }, $text);

    $converter = new CommonMarkConverter([
      'html_input' => 'strip',
      'allow_unsafe_links' => false,
    ]);

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

