<?php 


$sName = "localhost"; //server name

$uName = "root";

$pass = ""; //password


$db_name = "online_book_store_db"; //database name

/** created the database by using PDO (php data object)*/
try {
    $conn = new PDO("mysql:host=$sName;dbname=$db_name", 
                    $uName, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
  echo "Connection failed : ". $e->getMessage();
}