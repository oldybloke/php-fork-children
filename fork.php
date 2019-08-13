<?php
/***********************************************************************
*	File Name	: spawnChildren.php
*	Site		: Any 
* 	Date		: 02-03-2019
*	Description	: Spawn children to do multi tasking
*	Author	 	: Oldybloke
*	Version	 	: 1.0
* 	Notes		: 08-08-2019: Added option to split children to 
* 			: work on different data source if needed
***********************************************************************/
date_default_timezone_set("Europe/London");
error_reporting(E_ALL);

 class workers {

 function __construct() {
  $this->useMySQL = true;
  $this->debug = true;
  $this->info = true;
    
  if($this->useMySQL) {
   $this->host = "localhost";
   $this->user = "dbuser";
   $this->password = "dbpass";
   $this->databasename = "dbname";
   $this->dbConnect();
  }

  $this->allowedChildren = 10;
  $this->useMultipleData = false;
 }

 public function setFColor($c,$s) {
  $color = [
   'Black' => '0;30',
   'DarkGray' => '1;30',
   'Blue' => '0;34',
   'LightBlue' => '1;34',
   'Green' => '0;32',
   'LightGreen' => '1;32',
   'Cyan' => '0;36',
   'LightCyan' => '1;36',
   'Red' => '0;31',
   'LightRed' => '1;31',
   'Purple' => '0;35',
   'LightPurple' => '1;35',
   'Brown' => '0;33',
   'Yellow' => '1;33',
   'LightGray' => '0;37',
   'White' => '1;37'];

  return "\033[" .$color[$c]. "m" .$s. "\033[0m";
 }
 
 
 public function setBColor($c,$s) {  
   $color = [
    'black' => '40',
    'red' => '41',
    'green' => '42',
    'yellow' => '43',
    'blue' => '44',
    'magenta' => '45',
    'cyan' => '46',
    'light_gray' => '47'];

  return "\033[" .$color[$c]. "m" .$s. "\033[0m";
 }
	 

 private function dbConnect() {
  if(!$this->link_id = mysqli_connect($this->host, $this->user, $this->password)) {
   $this->error('Connecting To Database', mysqli_errno($this->link_id), mysqli_error($this->link_id), debug_backtrace());
   return -1;
  }
  else {
   mysqli_select_db($this->link_id, $this->databasename);
   return $this->link_id;
  }
 }

 /**********************************
    CLOSE CONNECTION TO DATABASE 
 **********************************/
 private function closeDatabase() {
  @mysqli_close($this->link_id);
  $this->link_id = '';
 }


 /**********************************
           EXECUTE QUERY 
 **********************************/
 private function executeQuery($query) {	  
  $this->dbConnect();
  if(!$result = mysqli_query($this->link_id, $query)) {
   $this->error($query, mysqli_errno($this->link_id), mysqli_error($this->link_id), debug_backtrace());
   $this->closeDatabase();
   return -1;
  }
  else {
   $this->closeDatabase ();
   return $result;
  }
 }

 /**********************************
   ESCAPE ALL ENTRIES TO DATABASE 
 **********************************/
 private function escapeWrapper($string) {
  $this->dbConnect();
  $string = mysqli_real_escape_string($this->link_id,$string);
  $this->closeDatabase();	
  return $string;	
 }

 /**********************************
    INSERT ROW AND RETURN NEW ID 
 **********************************/
 private function fetchInsertId($query) { 
  $this->link_id = $this->dbConnect();
  if($this->debug) { 
   print($query. "\n"); 
  }

  if(!$result = mysqli_query($this->link_id, $query)) {
   $this->error($query, mysqli_errno($this->link_id), mysqli_error($this->link_id), debug_backtrace());
   $this->closeDatabase();
   print(mysqli_error($this->link_id). "<br />");
   return -1;
  }
  else { 
   $result = mysqli_insert_id($this->link_id);
   $this->closeDatabase();
   
   if($this->debug) {
    print($result. "<br />");
   }

   return $result;
  }
 }

 /**********************************
       THROW ERROR CORRECTLY 
 **********************************/
 private function error($query, $errno, $error, $debug) {
  $this->closeDatabase();
  $message = $this->setFColor("Red", "[ERROR]\t") . $this->setFColor("White", " . $errno . " - " . $error . ");
  return $message;
 }

  /*****************************
     CHECK FOR ANY NEW DATA
  *****************************/ 
  public function check() {
	  	  
   if($this->debug) { 
	print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("Green", "[PARENT]\t") . $this->setFColor("White", "Checking for data\n")); 			
   }

   $sql = "SELECT * FROM `spawntest` WHERE `STATUS` ='N'";
   $result = $this->executeQuery($sql);
  return mysqli_num_rows($result);
  }


  /*****************************
     FETCH NEW DATA FOR CHILD
  *****************************/ 
  private function fetch($i) {
	  
   if($this->debug) { 
    print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Fetching new data\n")); 
   } 	

   /*****************************
      SPLIT CHILD ON SEPARATE 
       DB TABLES / PROJECTS
   *****************************/   
   if($this->useMultipleData) {

    if($i % 3 == 0) {
     $table = "5K";
    }
    elseif($i % 2 == 0) {
     $table = "5J";
    }
    else {
     $table = "5H";
    }

    if($this->debug) { 
     print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Use new data source:\t" .$table. "\n")); 
    }
    
   }  
   /*****************************
      SPLIT CHILD ON SEPARATE 
       DB TABLES / PROJECTS
   *****************************/
   
   $sql = "SELECT * FROM `spawntest` WHERE `STATUS` ='N' ORDER BY RAND() LIMIT 1";
   $sql = $this->executeQuery($sql);
   $data = mysqli_fetch_assoc($sql);
   return $data;  
  }


  /*****************************
     LOCK DATA FOR THIS CHILD
  *****************************/ 
  private function lock($data,$i) { // Example: Lock db row so other children dont use it.

   if($this->debug) { 
	print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Locking data\n")); 
   }
	
   $update = "UPDATE `spawntest` SET `STATUS` ='L' WHERE `ID` = " .$data['ID']. " LIMIT 1";
   $this->executeQuery($update);
  return true;  
  }


  /*****************************
    START WORKING ON NEW DATA
  *****************************/ 
  private function start($data, $i) { // Crunch the data this child was given
   
   sleep(rand(1,3));
   
  return $data;
  }
  

  /*****************************
       PROCESS CHILD DATA
  *****************************/ 
  private function process($data,$i) { // Process the work results from the child.

   if($this->debug) { 
	print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Processing data...\n")); 
   }
   
   return true;
  }


  /*****************************
      MARK DATA AS COMPLETED
  *****************************/ 
  private function completed($data,$i) { // Update db and remove lock.

   if($this->debug) { 
	print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Completing data...\n")); 
   }
   
   $update = "UPDATE `spawntest` SET `STATUS` ='Y' WHERE `ID` = " .$data['ID']. " LIMIT 1";
   $this->executeQuery($update);
  return true;
  }
    
  
  /*****************************
       SPAWN A NEW CHILD
  *****************************/
  public function spawn($i) {
   $date = date('H:i:s');
   
   if($this->debug) { 
    print($this->setFColor("Red", "[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Starting @ " .$date. "\n")); 
   }

 
   /*****************************
         FETCH NEW DATA
   *****************************/
   $data = $this->fetch($i);
   /*****************************
         FETCH NEW DATA
   *****************************/
				

   /*****************************
         LOCK CURRENT DATA
   *****************************/
   $this->lock($data,$i);
   /*****************************
         LOCK CURRENT DATA
   *****************************/				


   /*****************************
      START NEW CHILD WORKING
   *****************************/
   $data = $this->start($data,$i);
   /*****************************
      START NEW CHILD WORKING
   *****************************/


   /*****************************
       PROCESS CHILD RESULTS
   *****************************/
   $this->process($data,$i);
   /*****************************
       PROCESS CHILD RESULTS
   *****************************/
   
   
   /*****************************
       MARK DATA AS COMPLETED
   *****************************/
   $this->completed($data,$i);
   /*****************************
       MARK DATA AS COMPLETED
   *****************************/


   /*****************************
     GIVE THE CHILD A BREATHER
   *****************************/  
   sleep(1);
   /*****************************
     GIVE THE CHILD A BREATHER
   *****************************/

   if($this->debug) { 
	print($this->setFColor("Red","[DEBUG]\t") . $this->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $this->setFColor("White", "Checking for more data\n")); 
   }			
  return $i;
  }

 } // End of class
 

 /*****************************
    START A NEW WORKER CLASS
 *****************************/ 
 $worker = new workers();
 /*****************************
    START A NEW WORKER CLASS
 *****************************/ 

 if($worker->info) { 
  print($worker->setFColor("Yellow","[INFO]\t") . $worker->setFColor("Green", "[PARENT]\t") . $worker->setFColor("White", "Starting...") . "\n"); 
 }

 if($worker->debug) { 
  print($worker->setFColor("Red","[DEBUG]\t") . $worker->setFColor("Green", "[PARENT]\t") . $worker->setFColor("White", "Spawning " .$worker->allowedChildren. " children\n")); 
 }  

 for($i=1;$i<=$worker->allowedChildren;++$i) {

  
 /*****************************
    CREATE PARENT PROCESS ID
 *****************************/   
  $pid = pcntl_fork();
 /*****************************
    CREATE PARENT PROCESS ID
 *****************************/   
 
  $increment = array();

  if(!$pid) {

   while($worker->check() > 0) {
    $worker->spawn($i);
   }

   if($worker->debug) { 
    print($worker->setFColor("Red","[DEBUG]\t") . $worker->setFColor("LightGreen", "[CHILD " .$i. "]\t") . $worker->setFColor("White", "No more data, stopping child:\t" .$i. "\n")); 
   }
   
   exit($i);			
  }
  
 }    

 while(pcntl_waitpid(0, $status) != -1) {
  $status = pcntl_wexitstatus($status);
  
  if($worker->info) {
   print($worker->setFColor("Yellow","[INFO]\t") . $worker->setFColor("LightGreen", "[CHILD " .$status. "]\t") . $worker->setFColor("White", "completed\n"));
  }
 
 }

 if($worker->info) {
  print($worker->setFColor("Yellow","[INFO]\t") . $worker->setFColor("Green", "[PARENT]\t") . $worker->setFColor("White", "shutting down...") . "\n");
 }
?>
