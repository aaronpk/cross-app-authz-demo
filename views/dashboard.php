  <div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold text-body-emphasis">TODO</h1>

    <div class="col-sm-4 mx-auto">

      <p class="lead mb-4">
        Your Todos
      </p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">

        <form action="/todos/create" method="post" class="row row-cols-lg-auto g-3 align-items-center">

          <div class="col">
            <div class="input-group">
              <input type="text" class="form-control" id="name" name="name" >
            </div>
          </div>

          <div class="col">
            <button type="submit" class="btn btn-primary">Add Task</button>
          </div>

        </form>

      </div>
    </div>
  </div>

  <div class="px-4">
    <div class="col-sm-4 mx-auto">

      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">

        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Task</th>
              <th></th>
            </tr>
          </thead>
          <?php foreach($todos as $todo): ?>
            <tr>
              <td><a href="/todo/<?= $todo->id ?>">#</a></td>
              <td><?= e($todo->name) ?></td>
              <td>x</td>
            </tr>
          <?php endforeach ?>
        </table>



      </div>
    </div>
  </div>
