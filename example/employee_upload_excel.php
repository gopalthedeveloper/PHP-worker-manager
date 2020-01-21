<?php
	require_once 'includes/common.php';
	include 'includes/conn.php';
	include 'classes/Common.php';
	
    
    use PhpAmqpLib\Connection\AMQPStreamConnection; 
	use PhpAmqpLib\Message\AMQPMessage; 

	// var_dump('excel',__DIR__.'/uploads/excel');die;


	if(isset($_POST['upload'])){
		$filename = Common::uploadFile('excel',__DIR__.'/uploads/excel');
		
		$date = date('Y-m-d H:i:s');
		$sql = "INSERT INTO `import`( `filename`, `created_at`) VALUES ('{$filename}','{$date}')";
		if($conn->query($sql)){
			$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest'); 
			$channel = $connection->channel(); 
			$channel->queue_declare('hello', false, false, false, false); 
			$msgString = $conn->insert_id;
		
			$msg = new AMQPMessage($msgString); 
			$channel->basic_publish($msg, '', 'hello'); 
			$channel->close(); 
			$connection->close(); 
		
			$_SESSION['success'] = 'Employee excel uploaded for process successfully';
		}
		else{
			$_SESSION['error'] = $conn->error;
		}

	}
	else{
		$_SESSION['error'] = 'Upload an excel from employee';
	}
    $conn->close();

	header('location: employee.php');
?>