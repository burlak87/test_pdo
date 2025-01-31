<?php

namespace App\Classes\DB\Database;

use PDO;
use PDOException;
use PDOStatement;

class Database 
{
  protected static $instance;
  protected PDO $connection;

  protected $_errors = array(
		'code' => null,
		'message' => null
	);

  public function __construct() 
  {
    $dbConfig = Config::getInstance()->getValue('db');
    $dsn = $dbConfig['host'] . ';' . 'dbname' . $dbConfig['name'];
    $this->connection - new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
  }

  public static function getInstance(): Database 
  {
    if (!self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  // Метод для сохранения запроса и проверки сохранения в таблице
  public function save($entity, $data = array())
	{
		$result = null;
		try
		{
			$schema = self::getTableSchema($entity);
			foreach ($data as $field => $value)
			{
				if (isset($schema[$field]) && $schema[$field]['extra'] == 'auto_increment')
				{
					unset($data[$field]);
				}
			}

			$this->instance->beginTransaction();

			$transaction = $this->instance->prepare(self::buildQuery($entity, 'save', $data, array()));
			if (!$transaction->execute())
			{
				throw new Exception('Data could not be saved :/');
			}

			$result = $this->findOne($entity, array(
				'conditions' => array(
					'id' => $this->instance->getLastInsertId()
				)
			));

			$this->instance->commit();
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;

			$this->instance->rollback();
		}

		return $result;
	}

  // извлечение данных из базы данных.
  public function findOne($entity, $options = array(), $assoc = true)
	{
		$result = false;
		try
		{
			$transaction = $this->instance->prepare(self::buildQuery($entity, 'find_one', array(), $options));
			$transaction->execute();

			$result = $transaction->fetchAll($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $result ? $result[0] : array();
	}

  public function findAll($entity, $options = array(), $assoc = true)
	{
		$result = false;
		try
		{
			$transaction = $this->instance->prepare(self::buildQuery($entity, 'find_all', array(), $options));
			$transaction->execute();

			$result = $transaction->fetchAll($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $result ? $result : array();
	}

  // обновление записи в базе данных
  public function update($entity, $data = array(), $conditions = array())
	{
		$result = null;
		try
		{
			if (!$conditions)
			{
				throw new Exception('No conditions where given. Can\'t update blidly');
			}

			$schema = self::getTableSchema($entity);

			foreach ($schema as $field => $description)
			{
				if (!isset($data[$field]) && $description['extra'] == 'auto_increment' || $description['key'] == 'PRI')
				{
					unset($data[$field]);
				}
			}

			$this->instance->beginTransaction();

			$transaction = $this->instance->prepare(self::buildQuery($entity, 'update', $data, $conditions));
			if (!$result = $transaction->execute())
			{
				throw new Exception('Data could not be saved :/');
			}

			$this->instance->commit();
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;

			$this->instance->rollback();
		}

		return $result;
	}

  //  удаление записей из базы данных.
  public function delete($entity, $options = array())
	{
		$result = false;
		try
		{
			if (!$options || !$options['conditions'])
			{
				throw new InvalidArgumentException('Options conditions are missing!');
			}

			$transaction = $this->instance->prepare(self::buildQuery($entity, 'delete', array(), $options));
			$result = $transaction->execute();
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $result;
	}

  public function deleteAll($entity)
	{
		$result = false;
		try
		{
			$transaction = $this->instance->prepare(self::buildQuery($entity, 'delete_all', array()));
			$result = $transaction->execute();
		}
		catch (Exception $e)
		{
			$this->errors = array(
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			) + $this->_errors;
		}

		return $result;
	}

  // выполняет построение SQL-запросов для различных CRUD действий в рамках работы с бд.
  private function buildQuery($table, $action = 'save', $data, $options = array())
	{
		$schema = self::getTableSchema($table);
		$query  = '';
		$action = strtolower($action);
		$insert = '';
		$where  = '';
		$limit  = isset($options['limit']) && $options['limit'] ? "LIMIT {$options['limit']}" : '';
		$fields = $schema ? implode(',', array_keys($schema)) : '*';
		$fields = isset($options['fields']) && $options['fields'] ? implode(',', $options['fields']) : $fields;
		$order  = isset($options['order']) && $options['order'] ? 'ORDER BY '.implode(', ', $options['order']) : '';

		if (isset($options['conditions']) && array_filter($options['conditions']))
		{
			$conditions_regex = '/(like|LIKE|<|>|!=|=|>=|<=)$/';
			foreach ($options['conditions'] as $condition => $value)
			{
				preg_match($conditions_regex, $condition, $spec_condition);

				if ($spec_condition)
				{
					$spec_condition = strtoupper($spec_condition[0]);
					$value          = $spec_condition == 'LIKE' ? '%$value%' : $value;
					$condition      = preg_replace($conditions_regex, '', $condition);

					$where .= " $condition $spec_condition '$value' AND";
				}
				else
				{
					$where .= " $condition='$value' AND";
				}
			}

			$where = $where ? 'WHERE'.preg_replace('/AND$/i', '', $where) : '';
		}

		if ($action == 'save')
		{
			$fields = array();
			$values = array();

			foreach ($data as $field => $value)
			{
				$fields[] = $field;
				$values[] = $value;
			}

			$insert = '('.implode(', ', $fields).') VALUES (\''.implode('\', \'', $values).'\')';
			$query  = "INSERT INTO $table $insert";
		}
		elseif ($action == 'update')
		{
			$update = 'SET ';
			foreach ($data as $field => $value)
			{
				$update .= "$field='$value', ";
			}

			$update = preg_replace('/,\s+$/', '', $update);

			$query  = "UPDATE $table $update $where";
		}
		elseif ($action == 'find_one')
		{
			$query = "SELECT $fields FROM $table $where $order LIMIT 1";
		}
		elseif ($action == 'find_all')
		{
			$query = "SELECT $fields FROM $table $where $order $limit";
		}
		elseif ($action == 'delete')
		{
			$query = "DELETE FROM $table $where";
		}
		elseif ($action == 'delete_all')
		{
			$query = "DELETE FROM $table";
		}
		$this->exect_queries[] = (string) preg_replace('/\s{2,}/', ' ', $query);

		return end($this->exect_queries);
	}
}

?>