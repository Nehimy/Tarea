<?php

use Controllers\TaskController;
use Libs\Router;

Router::get('/', [TaskController::class, 'home']);
Router::post('/', [TaskController::class, 'addTask']);
Router::get('/task/{id}/delete',[TaskController::class, 'deleteTask']);
Router::get('/task/{id}/finish',[TaskController::class, 'finishTask']);
