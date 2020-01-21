<?php include 'includes/session.php'; ?>
<?php include 'includes/header.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
	<div class="wrapper">

		<?php include 'includes/navbar.php'; ?>
		<?php include 'includes/menubar.php'; ?>

		<!-- Content Wrapper. Contains page content -->
		<div class="content-wrapper">
			<!-- Content Header (Page header) -->
			<section class="content-header">
				<h1>
					Employee List
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
							<div class="box-header with-border">
								<!-- <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm btn-flat"><i class="fa fa-plus"></i> New</a> -->
								<a href="#upload_excel"  data-toggle="modal" class="btn btn-primary btn-sm pull-right">
									<i class="fa fa-upload"></i> Upload CSV
								</a>
							</div>`
							<div class="box-body">
								<table id="example1" class="table table-bordered">
									<thead>
										<th>Employee ID</th>
										<th>Name</th>
										<th>Member Since</th>
										<th>Birthday</th>
										<th>Salary</th>
										<th>Tools</th>
									</thead>
									<tbody>
										<?php
										$sql = "SELECT E.*,ES.salary FROM employees as E left join employee_salary as ES on ES.employee_id= E.id";
										$query = $conn->query($sql);
										while ($row = $query->fetch_assoc()) {
										?>
											<tr>
												<td><?php echo $row['employee_no']; ?></td>
												<td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
												<td><?php echo date('M d, Y', strtotime($row['created_on'])) ?></td>
												<td><?php echo date('M d, Y', strtotime($row['birthdate'])) ?></td>
												<td><?php echo number_format($row['salary'],2); ?></td>
												<td>
													<?php /*
													<button class="btn btn-success btn-sm edit btn-flat" data-id="<?php echo $row['employee_no']; ?>"><i class="fa fa-edit"></i> Edit</button>*/ ?>
													<button class="btn btn-danger btn-sm delete btn-flat" data-id="<?php echo $row['employee_no']; ?>"><i class="fa fa-trash"></i> Delete</button>
												</td>
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