<?php

// Database Configuration

$dbHost = "ec2-54-147-126-202.compute-1.amazonaws.com";
$dbUser = "akyrdaqzzaeqia";
$dbPW = "afad8efb1a034ec769aef9bb8b5d28f863a65c1ab5b4a547562e5cd8aed09580";
$dbName = "dcj0paevvf1cd0";

// Establish connection

$mysqli = mysqli_connect($dbHost, $dbUser, $dbPW, $dbName);

// Check connection
if(mysqli_connect_errno()){
 	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Website

$Website_Url = "faucetonfly.herokuapp.com";

?>
