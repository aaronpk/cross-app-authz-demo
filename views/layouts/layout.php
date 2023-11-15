<!doctype html>
<html lang="en">
<head>
  <title><?= e($_ENV['APP_NAME']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="utf-8">

  <?= $meta ?? '' ?>

  <link rel="stylesheet" href="/bootstrap-5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="/bootstrap-5.3.2/css/bootstrap-responsive.min.css">
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/css/<?= $_ENV['SITE_TYPE'] ?>.css">

  <script src="/js/jquery-3.7.1.min.js"></script>
  <script src="/js/script.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-sm navbar-dark bg-dark" aria-label="Third navbar example">
    <div class="container-fluid">
      <a class="navbar-brand" href="/"><?= e($_ENV['APP_NAME']) ?></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample03" aria-controls="navbarsExample03" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsExample03">
        <ul class="navbar-nav me-auto mb-2 mb-sm-0">
          <?php if(isset($navlinks)): ?>
            <?php foreach($navlinks as $link): ?>
              <li class="nav-item">
                <a class="nav-link" href="<?= $link['url'] ?>"><?= e($link['name']) ?></a>
              </li>
            <?php endforeach ?>
          <?php endif ?>
        </ul>
        <?php if(isset($user)): ?>
            <ul class="navbar-nav">
              <?php if($_ENV['SITE_TYPE'] == 'wiki'): ?>
              <li class="nav-item">
                <form action="/delete-access-tokens" method="post" id="delete-access-tokens">
                  <a class="nav-link" href="#">Delete Access Tokens</a>
                </form>
              </li>
              <?php endif ?>
              <li class="nav-item">
                <form action="/logout" method="post" id="logout-form">
                  <a class="nav-link" href="#">Log Out</a>
                </form>
              </li>
            </ul>
        <?php endif ?>
      </div>
    </div>
  </nav>


<div class="page">

  <?= $content ?>

</div>

</body>
</html>
