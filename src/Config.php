<?php

namespace App;

class Config 
{
  protected static array $instance = [];
  protected $config;

  private function __construct(string $file) 
  {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
    if (file_exists($path)) {
      $this->config = require $path;
    } else {
      die('По указанному пути нету файла');
    }
  }

  public static function getInstance(string $file = 'config/app.php') 
  {
    if (!self::$instance[$file]) {
      self::$instance[$file] = new self($file);
    }

    return self::$instance[$file];
  }

  public function getValue(string $name) 
  {
    return $this->config[$name];
  }
}

?>