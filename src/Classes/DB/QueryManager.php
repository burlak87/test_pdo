<?php

namespace App\Classes\DB\QueryManager;

Class QueryManager
{
	private $_sql;
  private $_bind;

	// служит для получения фактического SQL-запроса на основании текущей информации о выполненном запросе.
	public function getQuery() {
    $info = $this->getInfo();
    $query = $info['statement'];

    if (!empty($info['bind'])) {
      foreach ($info['bind'] as $field => $value) {
        $query = str_replace(':'.$field, $this->quote($value), $query);
      }
    }

    return $query;
  }

  // получение всех выполненных SQL-запросов
  public function getExectQueries()
	{
		return $this->exect_queries;
	}

  // предназначен для сбора информации о последнем выполненном SQL-запросе и его параметрах. 
	public function getInfo() {
    $info = [];

    if (!empty($this->_sql)) {
      $info['statement'] = $this->_sql;
    }

    if (!empty($this->_bind)) {
      $info['bind'] = $this->_bind;
    }

    return $info;
  }
  
	// получение последнего выполненного SQL-запроса.
  public function getLastQuery()
	{
		return end($this->exect_queries);
	}

  // возвращает последний идентификатор, созданный автоинкрементным полем после выполнения последнего вставляемого запроса
  public function getLastInsertId()
  {
    return $this->connection->lastInsertId();
  }

  // предназначен для обработки входных данных, которые потом могут быть использованы для привязки параметров в SQL-запросах.
  private function cleanup($bind = '') {
    if (!is_array($bind)) {
      if (!empty($bind)) {
        $bind = [$bind];
      } else {
        $bind = [];
      }
    }

    return $bind;
  }
}

?>