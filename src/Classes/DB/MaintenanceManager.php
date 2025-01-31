<?php

namespace App\Classes\DB\MaintenanceManager;

class MaintenanceManager
{
  // maintenance - предназначен для выполнения операций технического обслуживания всех таблиц в текущей базе данных
  public function maintenance() {        
    $tables = [];
    $show = $this->pdo()->query('show tables')->fetchAll();         
    foreach ($show as $rows) {
      if (!is_array($rows)) continue;            
      if (count($rows) < 1) continue;
      $tables[] = $this->connectionParams['dbname'] . '.' . reset($rows);        
    }
    if (count($tables) > 0) {            
      $tables = implode(', ', $tables);
      try {                
        $analyze = $this->pdo()->query("analyze table $tables"); 
        $check = $this->pdo()->query("check table $tables");                 
        $optimize = $this->pdo()->query("optimize table $tables"); 
        $repair = $this->pdo()->query("repair table $tables");             
      } catch (Exception $e) {
        throw new \Exception($e->getMessage());            
      }
      if ($analyze && $check && $optimize && $repair) {                
        return true;
      } else {                
        return false;
      }        
    } else {
      return false;       
    }
  }
}

?>