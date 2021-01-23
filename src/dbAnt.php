<?php
  namespace vilshub\dbant;
  use vilshub\helpers\message;
  use \Exception;
  use vilshub\helpers\get;
  use vilshub\helpers\style;
  use vilshub\helpers\textProcessor;
  /**
   *
   */
   /**
    *
    */
   class dbAnt
   {
     function __construct($dbHandler){
       $this->dbHandler = $dbHandler;
     }
     private $dbHandler;
     private function isPrepared($query){
        $positional = substr_count($query, "?");
        $named = substr_count($query, ":");
        if($positional > 0 || $named > 0){
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
       if($run instanceof $testClassName){
         return array (
           "status"=>true,
           "rowCount"=>$run->rowCount(),
           "lastInsertId"=>$this->dbHandler->lastInsertId(),
           "data"=>$run
         );
       }else {
         return array(
           "status"=>true,
           "rowCount"=>$run->rowCount(),
           "lastInsertId"=>$this->dbHandler->lastInsertId(),
           "data"=>$run
         );
       }
     }
     public function run($query, $values=null){
       try {
         if(!is_string($query)){
           throw new Exception("method argument 1 must be a string");
         }
       } catch (\Exception $e) {
         trigger_error(message::write("error", get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage()));
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
                return array (
                  "status"=>$run,
                  "rowCount"=>$statement->rowCount(),
                  "lastInsertId"=>$this->dbHandler->lastInsertId(),
                  "data"=>$run
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
           trigger_error(message::write("error", get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage()));
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
          trigger_error(message::write("error", get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage()));
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
                 for ($x=0; $x<$total; $x++){
                   $run = $statement->execute($arrayOfValues[$x]);
                 }
                 return array("status" => true);
              }
            }
          } catch (\Exception $e) {
            trigger_error(message::write("error", get::nonStaticMethod(__CLASS__, __FUNCTION__).$e->getMessage()));
          }
        }
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
   }
