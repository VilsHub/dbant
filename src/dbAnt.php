<?php
  namespace vilshub\DBAnt;
  use vilshub\helpers\Get;
  use \Exception;
  use vilshub\helpers\Style;
  use vilshub\validator\Validator;

  /**
   *
   */
   /**
    *
    */
   class DBAnt
   {
    private $dbHandler;
    function __construct(\PDO $dbHandler){
      $this->dbHandler = $dbHandler;
    }

    public function __get($propertyName){
      switch ($propertyName) {
        case 'link':
          return $this->dbHandler;
          break;
      }
    }
    
    private function isPrepared($query){
      if(preg_match("/=[ ]*\?/", $query) || preg_match("/=[ ]*:/", $query)){
        return true;
      }else {
        return false;
      }
    }
    private function preparedInfo($query){
        $positional = substr_count($query, "?");
        $named = substr_count($query, ":");
        $data = [];
        if($positional > 0){
          $data["type"] = "positional";
          $data["valuePoints"] = $positional;
        }else {
          $data["type"] = "named";
          $data["valuePoints"] = $named;
        }
        return $data;
    }
    private function runDirectQuery($query){
      $run =  $this->dbHandler->query($query);
      $testClassName = "PDOStatement";
      $data = $run->fetchAll();
      $records = count($data);
      if($records == 0){
        $parsedData = "";
      }else if($records > 0){
        $parsedData = $data;
      }

      $returnData =  array(
        "rowCount"=>$run->rowCount(),
        "lastInsertId"=>$this->dbHandler->lastInsertId(),
        "data"=>$parsedData,
        "status" => $run->rowCount() > 0
      );

      // if($run instanceof $testClassName){
      //   $returnData["status"] = true;
      // }else {
      //   $returnData["status"] = false;
      // }


      return $returnData;
    }

    public function run($query, $values=null){
      try {
        if(!is_string($query)){
          throw new Exception("method argument 1 must be a string");
        }
      } catch (\Exception $e) {
        trigger_error(Get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage());
      }
      if($this->isPrepared($query)){
        $preparedData = $this->preparedInfo($query);
        try {
          if (!is_array($values)){// Array not passed
            throw new Exception("method argument 2 must be an array");
          }else {
            $total =  count($values);
            if ($total != $preparedData["valuePoints"]){
            if ($total > $preparedData["valuePoints"]){
              throw new Exception("supplied values is more than prepared value points");
            }elseif ($total < $preparedData["valuePoints"]) {
              throw new Exception("supplied values is less than prepared value points");
            }
          }else {//equal values supplied
            if($preparedData["type"] == "positional"){
              //validate here
            }elseif ($preparedData["type"] == "named") {
              //validate here
            }

            //Execute
            $statement = $this->dbHandler->prepare($query);
            $run = $statement->execute($values);

            if($run){
              $data = $statement->fetchAll();
              $records = count($data);
              if($records == 0){
                $parsedData = "";
              }else if($records > 0){
                $parsedData = $data;
              }

              return array (
                "status"=>$run,
                "rowCount"=>$statement->rowCount(),
                "lastInsertId"=>$this->dbHandler->lastInsertId(),
                "data"=>$parsedData,
                "query"=>$query
              );
            }else {
              return array (
                "status"=>false,
                "rowCount"=>null,
                "lastInsertId"=>null,
                "data"=>null
              );
            }
            }
          }
        } catch (\Exception $e) {
          trigger_error(Get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage());
        }
      }else {
        return $this->runDirectQuery($query);
      };
    }
    public function batchRun($query, $arrayOfValues){
      try {
        if(!is_string($query)){
          throw new Exception("method argument 1 must be a string");
        }
      } catch (\Exception $e) {
        trigger_error(Get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage());
      }

      if($this->isPrepared($query)){
        $preparedData = $this->preparedInfo($query);

        try {
          if (!is_array($arrayOfValues)){// Array not passed
            throw new Exception("method argument 2 must be an array");
          }else {
            //Check if array is multi dimensional
            $total = count($arrayOfValues);

            for($x=0; $x<$total; $x++){
              if (!is_array($arrayOfValues[$x])){// Array not passed
                throw new Exception("method argument 2 must be a 2 dimensional array of parent being index array");
              }
            }

            $total =  count($arrayOfValues[0]);

            if ($total != $preparedData["valuePoints"]){
                if ($total > $preparedData["valuePoints"]){
                  throw new Exception("supplied values is more than prepared value points");
                }elseif ($total < $preparedData["valuePoints"]) {
                  throw new Exception("supplied values is less than prepared value points");
                }
              }else {//equal values supplied
                if($preparedData["type"] == "positional"){
                  //validate here
                }elseif ($preparedData["type"] == "named") {
                  //validate here
                }

                //Execute
                $statement = $this->dbHandler->prepare($query);
                
                //batch run prepared here
                $total = count($arrayOfValues);
                $this->startTransaction();
                $status = true;

                for ($x=0; $x<$total; $x++){

                    $run = $statement->execute($arrayOfValues[$x]);
                    if (!$run){
                      $status = false;
                    }
                  
                }

                if ($status == true){
                  $this->endTransaction();
                }else{
                  $this->rollBack();
                }

                return array("status" => $status);
            }
          }
        } catch (\Exception $e) {
          trigger_error(Get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage());
        }
      }
    }
    public function exist($query, $value){
      $status = $this->run($query, $value);
      return (int) $status["rowCount"] == 1?true:false;
    }
    public function tableExist($tableName){
      $sql = "SHOW TABLES LIKE '{$tableName}'";
      return $this->runDirectQuery($sql)["status"];
    }
    public function disableForeignKeyCheck(){
      $sql = "SET FOREIGN_KEY_CHECKS = 0";
      return $this->runDirectQuery($sql);
    }
    public function enableForeignKeyCheck(){
      $sql = "SET FOREIGN_KEY_CHECKS = 1";
      return $this->runDirectQuery($sql);
    }
    public function startTransaction(){
     $this->dbHandler->beginTransaction();
    }
    public function endTransaction(){
      $this->dbHandler->commit();
    }
    public function commit(){
      $this->dbHandler->commit();
    }
    public function rollBack(){
      $this->dbHandler->rollBack();
    }
    public function getRecord($sql, $values=null){
      $run = $this->run($sql, $values);
      return $run["rowCount"] > 0? $run["data"][0]:NULL;
    }
    public function totalRecords($sql){
      $run = $this->run($sql);
      return $run["rowCount"];
    }
    public function seed($tableName){
      $msg =  " Invalid method argument value, ".Style::color(__CLASS__."->", "black").Style::color($tableName, "black")." value must be a string specifying the table name";
      Validator::validateString($tableName, $msg);

      //check if table exist
      if (!$this->tableExist($tableName)){
        trigger_error("The target table '$tableName' does not exist for seeding");
        die;
      }
      
      $body = new class ($this, $tableName){
          private $db;
          private $tableName = null;
          public function __construct($db, $tableName) {
             $this->db = $db;
             $this->tableName = $tableName;
          }
          public function data($data, $recordSize){
            $columnNames    = array_keys($data);
            $preparedQuery  = $this->buildPreparedQuery($this->tableName, $columnNames);

            //Execute query
            for ($x=0; $x<$recordSize; $x++){
              $record = $this->getRecord($data, $x);
              $executeQuery = $this->db->batchRun($preparedQuery, [$record]);

              if ($executeQuery["status"] == false){
                trigger_error("Error occured while inserting the row with data: ". implode(", ", $record));
                die;
              }
            }
            
            return  true;
          }
          
          private function buildPreparedQuery($tableName, Array $columnNames){
            $total = count($columnNames); $n = 0 ;
      
            $sqlPrepared = "INSERT INTO `$tableName` SET \n";
            foreach ($columnNames as $columnName) {
              $n++;
              $eol = $n == $total?"":", ";
              $sqlPrepared .= "`$columnName` = ?$eol";
            }
      
            return $sqlPrepared;
          }

          private function getRecord($data, $index){
            $row = [];
            foreach ($data as $column) {
              $row[] = $column[$index];
            }

            return $row;
          }

      };

      return $body;
    }
   }
