<?php
namespace Controllers;

use Libs\Request;
use Libs\Router;
use Libs\View;
use Models\Task;

// 0  false
// 1  true

class TaskController {
  public static function  home (): void
  {
    $tasks = Task::get();
    View::render('CreateTask', [
      'tasks' => $tasks
    ]);
  }

  public static function addTask (Request $request): void
  {
    $task = new Task;
    $task->content = $request->post->content;

    $task->save();

    Router::redirect("/");
  }

  public static function deleteTask (Request $request): void
  {
    $task = new Task;

    $task->id = $request->params->id;
    $task->delete();

    Router::redirect("/");
  }

  public static function finishTask (Request $request): void
  {
    // $task = new Task;

    /* $task->$status = 0; */

  }

}
