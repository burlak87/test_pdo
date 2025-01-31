<?php

namespace App\Classes\DB\SchemaManager;

class Schema
{
  private function getTableSchema($table)
	{
		$schema = array();

		$transaction = $this->db->prepare("SHOW COLUMNS FROM `$table`");
		$transaction->execute();

		foreach ($transaction->fetchAll(PDO::FETCH_ASSOC) as $field)
		{
			$schema[$field['Field']] = array_change_key_case($field, CASE_LOWER);
			unset($schema[$field['Field']]['Field']);
		}

		return $schema;
	}

  // проверка наличия в базе таблицы и в ней проверка существования столбца
  private function checkTableColumn($table, $column, $request = array()) 
	{
    $resultTable = $this->pdo()->query("SHOW TABLES LIKE '" . $table . "'");        
    if ($resultTable->rowCount() != 1) 
		{
      throw new \Exception('Table "' . $table . '" does not exist.');        
    }
		foreach ($column as $key => $value) 
		{
			$resultColumn = $this->pdo()->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA= '" . $this->connectionParams['dbname'] . "' AND TABLE_NAME = '" . $this->table . "' AND COLUMN_NAME = '" . $key . "'");
			if ($resultColumn->rowCount() != 1) 
			{
				throw new \Exception('Column "' . $key / '" does not exist.');
			}
		}
  }

	// возвращает количество записей в указанной таблице
  public function tableCount($table = null) {  
    $this->checkTable($table); 
    $query = $this->pdo()->query('select count(*) as total from ' . $table)->fetchAll(); 
    return (int) $query[0]['total']; 
	}

  // предназначен для сопоставления типов данных, используемых в базе данных, с типами, определенными в коде
	private function _mapDataType($driver_type) {
    $map = [];
    $driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
    switch ($driver) {
      case self::DRIVER_MYSQL:
        $map = [
          self::TYPE_INT => ['smallint', 'mediumint', 'int', 'bigint'],
          self::TYPE_BOOL => ['tinyint'],
          self::TYPE_FLOAT => ['float', 'double', 'decimal'],
          self::TYPE_DATETIME => ['datetime'],
          self::TYPE_DATE => ['date'],
          self::TYPE_SPATIAL => ['point', 'geometry', 'polygon', 'multipolygon', 'multipoint']
        ];

        break;
      case self::DRIVER_SQLITE:
        $map = [
          self::TYPE_INT => ['integer'],
          self::TYPE_FLOAT => ['real'],
          self::TYPE_BOOL => ['boolean']
        ];
    }

    foreach ($map as $type => $driver_types) {
      if (in_array(strtolower($driver_type), $driver_types)) {
        return $type;
        break;
      }
    }
    return self::TYPE_STRING;
  }

}

?>