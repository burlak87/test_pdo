<?php

namespace App\Classes\Logging\ErrorLogger;

class ErrorLogger
{
	// позволяет задавать обработчик для ошибок, возникающих при выполнении запросов
	public function onError($callback) {
    if (is_string($callback)) {
      if (in_array(strtolower($callback), ['echo', 'print'])) {
        $callback = 'print_r';
      }
      if (function_exists($callback)) {
        $this->_error_callback = $callback;
      }
    } else {
      $this->_error_callback = $callback;
    }
  }

  // метод предназначен для логирования или отображения информации об ошибках, возникающих в процессе выполнения SQL-запросов.
	private function debug() {
    if (!empty($this->_error_callback)) {
      $error = ['Error' => $this->_error];

      if (!empty($this->_sql)) {
        $error['SQL Statement'] = $this->_sql;
      }

      if (!empty($this->_bind)) {
        $error['Bind Parameters'] = trim(print_r($this->_bind, true));
      }

      $backtrace = debug_backtrace();
      if (!empty($backtrace)) {
        $backtraces = [];
        foreach ($backtrace as $info) {
          if (isset($info['file']) && $info['file'] != __FILE__) {
            $backtraces[] = $info['file'] . ' at line ' . $info['line'];
          }
        }

        if ($backtraces) {
          $error['Backtrace'] = implode(PHP_EOL, $backtraces);
        }
      }

      $msg = 'SQL Error' . PHP_EOL . str_repeat('-', 50);
      foreach ($error as $key => $val) {
        $msg .= PHP_EOL . PHP_EOL . $key . ':' . PHP_EOL . $val;
      }

      $func = $this->_error_callback;
      $func(new \PDOException($msg));
    }
  }
}

?>