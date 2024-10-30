<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg" href="http://ney.lh/css/Notitas_icono.svg">
    <title>To do list</title>
  </head>
  <body id="body">
    <div class="container">
      <header>
        <h1>To do list</h1>
        <strike>Texto tachado</strike>
        <h4>This is heading 4</h4>
      </header>
      <main>
        <div class="task">
          <?php
          foreach($view->tasks as $task){
          ?>
            <div class="active<?=$task->active?>">
              <?php
              echo "$task->id . $task->content<br>";
              ?>
            </div>
            <!-- <a href="<?=SITE_URL?>task/<?=$task->id?>/delete">Eliminar</a> -->
            <a
              Class="button-delete"
              hx-get= "<?=SITE_URL?>task/<?=$task->id?>/delete"
              hx-trigger= "click"
              hx-swap="outerHTML"
              hx-target="#body"
            >
              Eliminar
            </a>
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
      </main>
    </div>
      <script src="https://unpkg.com/htmx.org@2.0.3"
              integrity="sha384-0895/pl2MU10Hqc6jd4RvrthNlDiE9U1tWmX7WRESftEDRosgxNsQG/Ze9YMRzHq"
              crossorigin="anonymous">
      </script>
  </body>
</html>
