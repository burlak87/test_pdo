<?php

namespace App\Classes\User;

class User
{
  public int $id;

  public function __construct(int $id)
  {
    $this->id = $id;
  }

  public function getId(): int 
  {
    return $this->id;
  }
}

?>