<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg" href="http://ney.lh/css/Notitas_icono.svg">
    <title>To do list</title>
  </head>
  <body>
    <div class="container">
      <header>
        <h1>To do list</h1>
      </header>
      <main>
        <div class="task">
          <?php
          foreach($view->tasks as $task){
            echo "$task->id . $task->content<br>";
          ?>
            <a href="<?=SITE_URL?>task/<?=$task->id?>/delete">Eliminar</a>
            <!-- <a href="<?=SITE_URL?>task/<?=$task->id?>/wait">Espera</a> -->
            <a href="<?=SITE_URL?>task/<?=$task->id?>/finish">Finalizar</a>
          <?php
          }
          ?>
        </div>
        <form id="new-task-form"  method="POST" action="">
          <input id="text-box" type="text" name="content"
                 value="" placeholder="Nueva tarea">
          <button type="submit">Save</button>
        </form>
    </div>
      </main>
  </body>
</html>
