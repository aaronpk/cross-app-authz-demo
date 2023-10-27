  <div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold text-body-emphasis"><?= e($_ENV['APP_NAME']) ?></h1>

    <div class="col-sm-4 mx-auto">

      <p class="lead mb-4">
        Let's get writing!
      </p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">

        <form action="/login" method="post" style="width: 100%">

          <div class="mb-3">
            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="">
          </div>

          <button type="submit" class="btn btn-primary">Log In</button>

        </form>

      </div>
    </div>
  </div>
