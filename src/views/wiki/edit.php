<div class="px-4 py-4">
  <div class="col-sm-4 mx-auto">

    <h1>Editing: <?= e($page->slug) ?></h1>

    <form action="/edit/save" method="post">

      <textarea name="text" class="form-control" style="width: 100%; height: 400px;"><?= e($page->text) ?></textarea>

      <input type="hidden" name="slug" value="<?= e($page->slug) ?>">

      <br>
      <button type="submit" class="btn btn-primary">Save</button>

    </form>

  </div>
</div>
