<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/style.css">
    <link rel="icon" type="image/svg" href="https://project.kj5.top/static/img/Kingo.svg">
    <title>To do list</title>
  </head>
  <body id="body">
    <div class="container">
      <header>
        <img class="logo" src="static/img/Kingo.svg" alt="Logo Kingo" />
        <h1>To do list</h1>
        <strike>Texto tachado</strike>
        <h4>This is heading 4</h4>
      </header>
      <main>
        <div class="card">
          <div class="task">
            <?php
            foreach($view->tasks as $task){
            ?>
              <div class="active<?=$task->active?>">
                <?php
                echo "$task->id . $task->content<br>";
                ?>
              </div>
              <a
                class="button-delete"
                id="button"
                href="<?=SITE_URL?>task/<?=$task->id?>/delete"
                hx-boost="true"
              >
                Eliminar</a>
              <a
                class="button-finish"
                id="button"
                href="<?=SITE_URL?>task/<?=$task->id?>/finish"
                hx-boost="true"
              >
                Finalizar</a>
            <?php
            }
            ?>
          </div>
          <form id="new-task-form"  method="POST" action="<?=SITE_URL?>" hx-boost="true">
            <input id="text-box" type="text" name="content"
                   value="" placeholder=" Nueva tarea">
            <button
              class="button-save"
              id="button"
              type="submit"
              hx-boost="true"
            >
              Save
            </button>
          </form>
        </div>
      </main>
    </div>
      <script src="https://unpkg.com/htmx.org@2.0.3"
              integrity="sha384-0895/pl2MU10Hqc6jd4RvrthNlDiE9U1tWmX7WRESftEDRosgxNsQG/Ze9YMRzHq"
              crossorigin="anonymous">
      </script>
  </body>
</html>
