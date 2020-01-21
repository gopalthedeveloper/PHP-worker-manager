<?php 
	include 'includes/session.php';

	if(isset($_POST['id'])){
		$id = $_POST['id'];
		$sql = "SELECT *, E.id as empid FROM employees as E LEFT JOIN employee_salary as ES ON ES.employee_id=E.id WHERE E.employee_no = '$id'";
		$query = $conn->query($sql);
		$row = $query->fetch_assoc();

		echo json_encode($row);
	}
?>