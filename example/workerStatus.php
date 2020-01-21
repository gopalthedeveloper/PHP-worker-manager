<?php 
	include 'includes/session.php'; 
	include 'includes/header.php'; 
	include 'workerList.php';
	require_once 'classes/WorkerManager.php';

?>

<body class="hold-transition skin-blue sidebar-mini">
	<div class="wrapper">

		<?php include 'includes/navbar.php'; ?>
		<?php include 'includes/menubar.php'; ?>

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper">
			<!-- Content Header (Page header) -->
			<section class="content-header">
				<h1>
				Employee Csv update worker
				</h1>
				
				<ol class="breadcrumb">
					<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
					<li>Employees</li>
					<li class="active">Employee List</li>
				</ol>
			</section>
			<!-- Main content -->
			<section class="content">
				<?php
				if (isset($_SESSION['error'])) {
					echo "
            <div class='alert alert-danger alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-warning'></i> Error!</h4>
              " . $_SESSION['error'] . "
            </div>
          ";
					unset($_SESSION['error']);
				}
				if (isset($_SESSION['success'])) {
					echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              " . $_SESSION['success'] . "
            </div>
          ";
					unset($_SESSION['success']);
				}
				?>
				<div class="row">
					<div class="col-xs-12">
						<div class="box">
							
							<div class="box-body">
								<?php 
									require_once 'classes/WorkerManager.php';
									include 'workerList.php';
								
								
								$wokerManager  = WorkerManager::instance();
								$worker = reset($workers);
								$workerDetails = $wokerManager->getWorkerProcessesRedis($worker['name']);
								// $worker = 
								// print_r($workerDetails);
								?>
								<div class="row">
									<div class="col-md-6">
										<table  class="table	 table-bordered table-stripped">
											<tr>
												<th>Name</th>
												<td><?=$worker['name']?></td>
											</tr>
											<tr>
												<th>Min</th>
												<td><?=$worker['min']?></td>
											</tr>
											<tr>
												<th>Max</th>
												<td><?=$worker['max']?></td>
											</tr>
											<tr>
												<th>MaxIterarion</th>
												<td><?=$worker['maxIteration']?></td>
											</tr>
										</table>
									</div>
									<div class="col-md-6">
										<a href="controlWorker.php" class="btn-sm btn-primary pull-right">
											Reload workers
										</a>
									</div>

								</div>

								
								<table id="example1" class="table table-bordered">
									<thead>
										<th>Sl.no</th>
										<th>Name:Pid</th>
										<th>State</th>
										<th>Iteration</th>
										<th>Job Start time</th>
									</thead>
									<tbody>
										<?php
										$slNo =1;
										foreach ($workerDetails as $workerJobName => $worker) {											
										?>
											<tr>
												<td><?= $slNo++ ?></td>
												<td><?=$workerJobName?></td>
												<td><?=$worker['state']==0?'Idle':'running'?></td>
												<td><?=$worker['iteration']?></td>
												<td><?=$worker['jobStart']==0?'-':date('d M Y H:i',$worker['jobStart'])?></td>
											</tr>
										<?php
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>

		<?php include 'includes/footer.php'; ?>
		<?php include 'includes/employee_modal.php'; ?>
	</div>
	<?php include 'includes/scripts.php'; ?>
	<script>
		$(function() {
			$(document).on('click','.edit',function(e) {
				e.preventDefault();

				$('#edit').modal('show');
				var id = $(this).data('id');
				getRow(id);
			});

			$(document).on('click','.delete',function(e) {
				e.preventDefault();
				$('#delete').modal('show');
				let id = $(this).data('id');
				// $('.empid').val(id);
				getRow(id);
			});
		});

		function getRow(id) {
			$.ajax({
				type: 'POST',
				url: 'employee_row.php',
				data: {
					id: id
				},
				dataType: 'json',
				success: function(response) {
					$('.empid').val(response.empid);
					$('.employee_id').html(response.employee_id);
					$('.del_employee_name').html(response.firstname + ' ' + response.lastname);
					$('#employee_name').html(response.firstname + ' ' + response.lastname);
					$('#edit_firstname').val(response.firstname);
					$('#edit_lastname').val(response.lastname);
					$('#edit_address').val(response.address);
					$('#datepicker_edit').val(response.birthdate);
					$('#edit_contact').val(response.contact_info);
					$('#gender_val').val(response.gender).html(response.gender);
				}
			});
		}
	</script>
</body>

</html>