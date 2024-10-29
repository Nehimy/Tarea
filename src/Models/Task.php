<?php
namespace Models;

use Libs\Model;

class Task extends Model {
    public string $content;
    public ?int   $id;
    public bool $active= true;
}
