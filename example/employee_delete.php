<?php
	include 'includes/session.php';

	if(isset($_POST['delete'])){
		$id = $_POST['id'];
		$sql = "DELETE FROM employees WHERE id = '$id'";
		$conn->begin_transaction();
		if($conn->query($sql)){
			$sql = "DELETE FROM employee_salary WHERE employee_id = '$id'";
			if($conn->query($sql)){
				$_SESSION['success'] = 'Employee deleted successfully';
				$conn->commit();

			}
		}
		else{
			$_SESSION['error'] = $conn->error;
		}
	}
	else{
		$_SESSION['error'] = 'Select item to delete first';
	}

	header('location: employee.php');
	
?>