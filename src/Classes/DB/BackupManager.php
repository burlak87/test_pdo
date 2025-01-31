<?php

namespace App\Classes\DB\BackupManager;

class Backup
{
  // backup - предназначен для создания резервной копии базы данных, 
  public function backup($fileName = null, $action = null, $excludeTables = []) { 
    if ($this->connectionParams['driver'] == 'sqlite') { 
      throw new \Exception('SQLite database backup is not allowed. Download "'.$this->connectionParams['url'].'" file directly.'); 
    } 
    if (empty($fileName)) {$fileName = 'SunDB-Backup-'.date("dmYHis").'.sql';} else {$fileName .= '.sql';} 
    if (empty($action)) {$action = 'save';} 
    if ($action == 'save') { 
      header('Content-disposition: attachment; filename='.$fileName); 
      header('Content-type: application/force-download'); 
    } 
    $show = $this->pdo()->query('show tables')->fetchAll(); 
    $tables = []; 
    foreach ($show as $rows) { 
      $content = []; 
      $table = reset($rows); 
      if (!in_array($table, $excludeTables)) { 
        $create = $this->pdo()->query("show create table `$table`")->fetchAll(); 
        $content[] = $create[0]['Create Table'].";\n"; 
        $query = $this->pdo()->prepare("select * from `$table`"); 
        $query->execute(array()); 
        $select = $query->fetchAll(); 
        if ($query->rowCount() > 0) { 
          foreach ($select as $row) { 
            if (count($row) < 1) {continue;} 
            $header = "INSERT INTO `$table` VALUES ('";  
            $body = implode("', '", array_values($row));
            $footer = "');"; 
            $content[] = $header.$body.$footer; 
          } 
          if (count($content) < 1) {continue;} 
          $tables[$table] = implode("\n", $content); 
        } 
      } 
    } 
    if ($action == 'save') { 
      echo "# SunDB Database Backup File\n# Backup Date: ".date("Y-m-d H:i:s")."\n# Backup File: ".$fileName."\n\n\n"; 
      echo implode("\n\n", array_values($tables)); 
    } else { 
      echo nl2br(implode('<br><br>', array_values($tables))); 
    } 
  }
}

?>