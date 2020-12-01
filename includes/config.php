<?php

// Database Configuration

$dbHost = "us-cdbr-east-02.cleardb.com";
$dbUser = "be08c9aed7a166";
$dbPW = "134a0414";
$dbName = "heroku_72e14ea7b1cccb4";

// Establish connection

$mysqli = mysqli_connect($dbHost, $dbUser, $dbPW, $dbName);

// Check connection
if(mysqli_connect_errno()){
 	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Website

$Website_Url = "faucetonfly.herokuapp.com";

?>
