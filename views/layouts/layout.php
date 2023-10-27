<!doctype html>
<html lang="en">
<head>
  <title>TODO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="utf-8">

  <?= $meta ?? '' ?>

  <link rel="stylesheet" href="/bootstrap-5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="/bootstrap-5.3.2/css/bootstrap-responsive.min.css">
  <link rel="stylesheet" href="/css/style.css">

  <script src="/js/jquery-3.7.1.min.js"></script>
  <script src="/js/script.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-sm navbar-dark bg-dark" aria-label="Third navbar example">
    <div class="container-fluid">
      <a class="navbar-brand" href="/">TODO</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample03" aria-controls="navbarsExample03" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsExample03">
        <ul class="navbar-nav me-auto mb-2 mb-sm-0">
<!--           <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li>
          <li class="nav-item">
            <a class="nav-link disabled" aria-disabled="true">Disabled</a>
          </li>
 -->        </ul>
        <?php if(isset($user)): ?>
          <form action="/logout" method="post" id="logout-form">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link" href="#">Log Out</a>
              </li>
            </ul>
          </form>
        <?php endif ?>
      </div>
    </div>
  </nav>


<div class="page">

  <?= $content ?>

</div>

</body>
</html>
