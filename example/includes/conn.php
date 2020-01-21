<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	include __DIR__.'/../vendor/autoload.php';

	$conn = new mysqli('localhost', 'mobiotics', 'mobiotics123', 'employeeManagement');

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
	
?>