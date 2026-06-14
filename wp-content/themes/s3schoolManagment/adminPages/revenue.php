<?php
/**
* Template Name: Admin Revenue
*/
global $wpdb; global $s3sRedux; global $cashSubHeadId;

$activeExp = $activeRev = "";

function getIncomeSchoolHeadname(){
	global $wpdb;
	$query = $wpdb->get_results( "SELECT id,sub_head_name FROM ct_sub_head WHERE relation_to = 2 AND head_id IN (1,4)" );
	return $query;
}
function getExpenseHeadname(){
	global $wpdb;
	$query = $wpdb->get_results( "SELECT id,sub_head_name FROM ct_sub_head WHERE (relation_to = 2 AND head_id = 3) OR head_id = 5" );
	return $query;
}

function getIncomeHeadname(){
	global $wpdb;
	$query = $wpdb->get_results( "SELECT id,sub_head_name FROM ct_sub_head WHERE (relation_to = 2 AND head_id = 2) OR head_id = 5" );
	return $query;
}

function getSubHeadNameById($id){
	global $wpdb;
	$name_qry = "SELECT sub_head_name FROM ct_sub_head WHERE id = $id";
	$name = $wpdb->get_results( $name_qry );
	return @$name[0]->sub_head_name;
}

function getHeadName($id){
	$HeadName = '';
	if($id == 1){
		$HeadName = 'Cash';
	}else if($id == 2){
		$HeadName = 'Income';
	}else if($id == 3){
		$HeadName = 'Expense';
	}else if($id == 4){
		$HeadName = 'Bank';	
	}else if($id == 5){
		$HeadName = 'Income & Expense';
	}
	return $HeadName;
}
function getPreviousBalance($date, $subHeadId){
	global $wpdb;
	
	$Balance = $wpdb->get_results( "SELECT SUM(credit)-SUM(debit) AS total FROM ct_ledger WHERE sub_head_id = '$subHeadId' AND date(date) < '$date' " );
	$Total = $Balance[0]->total;
	return $Total == null ? 0 : $Total;
}
function getDatewiseIncomeTotal($fromdate, $subHeadId, $todate=null){
	global $wpdb;
	$Total = 0;
	if($todate){
		$Balance = $wpdb->get_results( "SELECT SUM(credit) AS total FROM ct_ledger WHERE sub_head_id = '$subHeadId' AND date(date) >= '$fromdate' AND date(date) <= '$todate'" );
		$Total = $Balance[0]->total;
		
	}else
	{
		$Balance = $wpdb->get_results( "SELECT SUM(credit) AS total FROM ct_ledger WHERE sub_head_id = '$subHeadId' AND date(date) = '$fromdate' " );
		$Total = $Balance[0]->total;		
	}
	return $Total == null ? 0 : $Total;
}
function getDatewiseExpenseTotal($fromdate, $subHeadId, $todate=null){
	global $wpdb;
	$Total = 0;
	if($todate){
		$Balance = $wpdb->get_results( "SELECT SUM(debit) AS total FROM ct_ledger WHERE sub_head_id = '$subHeadId' AND date(date) >= '$fromdate' AND date(date) <= '$todate'" );
		$Total = $Balance[0]->total;
		
	}else
	{
		$Balance = $wpdb->get_results( "SELECT SUM(debit) AS total FROM ct_ledger WHERE sub_head_id = '$subHeadId' AND date(date) = '$fromdate' " );
		$Total = $Balance[0]->total;		
	}
	return $Total == null ? 0 : $Total;
}
function saveLeadger($sub_head_id,$credit,$debit,$reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id,$date=null){
	// save ledger table
	global $wpdb;
	if($date){
		$date = date('Y-m-d H-i-s', strtotime($date));
	}else{
		$date = date('Y-m-d H-i-s');
	}
	
	$insert = $wpdb->insert(
		'ct_ledger',
		array(
			'sub_head_id' 		=> $sub_head_id,
			'credit' 	=> $credit,
			'debit' => $debit,
			'reference' 	=> $reference,
			'monthly_fee_id' 	=> $monthly_fee_id,
			'yearly_fee_id' 	=> $yearly_fee_id,
			'exam_fee_id' 	=> $exam_fee_id,
			'date' 	=> $date,
			// 'exam_id' 	=> $_POST['revDate'],
			'created_by' 	=> get_current_user_id(),
			'created_at' 	=> date('Y-m-d H-i-s')
		)
	);
	
}
if (isset($_POST['addIncome'])) {
	// add incom
	$insert = saveLeadger($_POST['subHeadId'] ,$_POST['incomeAmount'],0, $_POST['incomeNote'],null,null,null,$_POST['incomeDate']);
	if($_POST['subHeadId'] != $cashSubHeadId){
		// add cash
		$insert = saveLeadger($cashSubHeadId ,$_POST['incomeAmount'],0, $_POST['incomeNote'],null,null,null,$_POST['incomeDate']);
	
	}
	// $message = ms3message(null, 'Added');
}

if (isset($_POST['initialBalance'])) {
	// add incom
	$insert = saveLeadger($_POST['subHeadId'] ,$_POST['incomeAmount'],0, $_POST['incomeNote'],null,null,null,$_POST['incomeDate']);

	$message = ms3message(null, 'Saved');
}

if (isset($_POST['addExpense'])) {
	$insert = saveLeadger($_POST['subHeadId'] ,0,$_POST['expenseAmount'], $_POST['expenseNote'],null,null,null,$_POST['expenseDate']);
	// reduce cash
	$insert = saveLeadger($cashSubHeadId,0,$_POST['expenseAmount'], $_POST['expenseNote'],null,null,null,$_POST['expenseDate']);
	// $message = ms3message(null, 'Added');
}

if (isset($_POST['debitCredit'])) {
	$debit = saveLeadger($_POST['subHeadIdFrom'] ,0,$_POST['debitCreditAmount'], $_POST['debitCreditNote'],null,null,null,$_POST['debitCreditDate']);
	$credit = saveLeadger($_POST['subHeadIdTo'] ,$_POST['debitCreditAmount'],0, $_POST['debitCreditNote'],null,null,null,$_POST['debitCreditDate']);
	// $message = ms3message(null, 'Added');
}
/* addCategory */
if (isset($_POST['addCategory'])) {
	if ($_POST['addCategory'] == 'save') {
		$insert = $wpdb->insert(
			'ct_sub_head',
			array(
				'relation_to' 	=> $_POST['relation_to'],
				'head_id' 	=> $_POST['head_id'],
				'status' 	=> $_POST['status'],
				'type' 	=> $_POST['type'],
				'sub_head_name' 	=> $_POST['sub_head_name'],
				'is_editable' 	=> $_POST['is_editable'],
				'sort_order' 	=> $_POST['sort_order']
			)
		);
		$message = ms3message($insert, 'Added');
	}elseif ($_POST['addCategory'] == 'update') {

		$update = $wpdb->update(
			'ct_sub_head',
			array(
				'relation_to' 	=> $_POST['relation_to'],
				'head_id' 	=> $_POST['head_id'],
				'status' 	=> $_POST['status'],
				'type' 	=> $_POST['type'],
				'sub_head_name' 	=> $_POST['sub_head_name'],
				'is_editable' 	=> $_POST['is_editable'],
				'sort_order' 	=> $_POST['sort_order']
			),
			array( 'id' => $_POST['id'])
		);
		$message = ms3message($update, 'Updated');
	}
}

/*Delete Cat*/
if (isset($_POST['delCat'])) {
	$delete = $wpdb->delete( 'ct_sub_head', array( 'id' => $_POST['id'] ) );
	$wpdb->delete( 'ct_ledger', array( 'sub_head_id' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}


/*Add Revinew*/
if (isset($_POST['addRevinew'])) {

	$insert = $wpdb->insert(
		'ct_revenue',
		array(
			'revCat' 		=> $_POST['revCat'],
			'revMemo' 	=> $_POST['revMemo'],
			'revAmount' => $_POST['revAmount'],
			'revNote' 	=> $_POST['revNote'],
			'revDate' 	=> $_POST['revDate']
		)
	);

	$message = ms3message($insert, 'Added');
}

/*Delete Revinew*/
if (isset($_POST['delRevinew'])) {
	$delete = $wpdb->delete( 'ct_revenue', array( 'revId' => $_POST['delRevinew'] ) );
	$message = ms3message($delete, 'Deleted');
}
?>

<?php if ( ! is_admin() ) { get_header(); ?>
	
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>
<div class="row text-center">
			<h2> Accounts</h2>
			<div class="col-md-9" style="margin: 10px">	
				<?php  if(wp_get_current_user()->roles[0] == 'um_accounts-user' ){?>
    			    <a href="?page=revenue&view=debitCredit" class="btn btn-secondary pull-right" style="background:#4dc28f;" >Debit Credit</a>
    			<?php } ?>
				<a href="?page=revenue&view=expense" class="btn btn-secondary pull-right" style="background:#4dc28f;">Expense Entry</a>
				<a href="?page=revenue" class="btn btn-secondary pull-right" style="background:#4dc28f;">Income Entry</a>
			</div>
		</div>
	<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<div class="col-md-12" style="margin-top: 5px">
		<h2 class="resmangh2" style="padding-bottom: 10px;">
			
			
		</h2>
	</div>
	<div class="col-md-12" style="margin-top: 5px">
		<h2 class="resmangh2" style="padding-bottom: 10px;">
		    <?php if(wp_get_current_user()->roles[0] == 'um_headmaster'  || wp_get_current_user()->roles[0] == 'administrator'){?>
				
			<a href="?page=revenue&view=summary-report" class="btn btn-primary pull-right" >Summary Report</a>
			<a href="?page=revenue&view=transaction-report" class="btn btn-primary pull-right" >Transaction Report</a>
			<a href="?page=revenue&view=income-report" class="btn btn-primary pull-right" >Income Report</a>
			<a href="?page=revenue&view=expense-report" class="btn btn-primary pull-right" >Expense Report</a>
			<a href="?page=revenue&view=columnary" class="btn btn-primary pull-right" >Columnary Report</a>
			<a href="?page=revenue&view=columnaryIncome" class="btn btn-primary pull-right" >Columnary Income</a>
			<a href="?page=revenue&view=columnaryExpense" class="btn btn-primary pull-right" >Columnary Expense</a>
			<a href="?page=revenue&view=cashbook" class="btn btn-primary pull-right" >Cash Book</a>
			<a href="?page=revenue&view=category" class="btn btn-primary pull-right">Category</a>
			<a href="?page=revenue&view=initialBalance" class="btn btn-primary pull-right" >Initial Balance</a>
			<a href="?page=revenue&view=debitCredit" class="btn btn-primary pull-right" >Debit Credit</a>
			<?php } ?>


		</h2>
	</div>
		<!-- Show Status message -->
  	<?php if(isset($message)){ echo ms3showMessage($message); } ?>
  	<?php if(!isset($_GET['view'])){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Income</h3></div>
			  <div class="panel-body">
			  	<div class="">
				  <form action="" method="POST">
			      	<div class="modal-body">
			        	<div class="row">
				        	<div class="form-group col-md-6">
						    		<label>Category:</label>
						    		<select class="form-control" name="subHeadId">
						    			<?php
						    				$cats = $wpdb->get_results( "SELECT * FROM ct_sub_head
											WHERE (relation_to = 2 and head_id = 2) OR head_id = 5 ORDER BY sub_head_name ASC" );
					  						foreach ($cats as $cat) {
					  							echo "<option value='".$cat->id."'>".$cat->sub_head_name."</option>";
					  						}
						    			?>
						    		</select>
						    	</div>

						    	<div class="form-group col-md-6">
						    		<label>Amount:</label>
						    		<input class="form-control" type="number" name="incomeAmount" placeholder="Amount" required>
						    	</div>
									
									<div class="form-group col-md-6">
										<label>Date:</label>
						    		<input class="form-control" type="Date" name="incomeDate" value="<?php echo date('Y-m-d'); ?>">
						    	</div>

			        	</div>
					    	<div class="form-group">
					    		<label>Note:</label>
					    		<textarea class="form-control" name="incomeNote" placeholder="Note"></textarea>
					    	</div>
				      </div>
				      <div class="modal-footer">
				        <button type="submit" class="btn btn-success" name="addIncome" value="save">Save</button>
				      </div>
			      </form>
		  		
		  			
			  	</div>
				</div>
			</div>
  	<?php } elseif($_GET['view'] == 'initialBalance'){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Initial Balance</h3></div>
			  <div class="panel-body">
			  	<div class="">
				  <form action="" method="POST">
			      	<div class="modal-body">
			        	<div class="row">
				        	<div class="form-group col-md-6">
						    		<label>Category:</label>
						    		<select class="form-control" name="subHeadId">
						    			<?php
						    				$cats = $wpdb->get_results( "SELECT * FROM ct_sub_head
											WHERE (relation_to = 2 and head_id in (SELECT id FROM ct_head WHERE head_name IN ('Income', 'Bank', 'Cash'))) OR head_id = 5 ORDER BY sub_head_name ASC" );
					  						foreach ($cats as $cat) {
					  							echo "<option value='".$cat->id."'>".$cat->sub_head_name."</option>";
					  						}
						    			?>
						    		</select>
						    	</div>

						    	<div class="form-group col-md-6">
						    		<label>Amount:</label>
						    		<input class="form-control" type="number" name="incomeAmount" placeholder="Amount" required>
						    	</div>
									
									<div class="form-group col-md-6">
										<label>Date:</label>
						    		<input class="form-control" type="Date" name="incomeDate" value="<?php echo date('Y-m-d H-i-s'); ?>">
						    	</div>

			        	</div>
					    	<div class="form-group">
					    		<label>Note:</label>
					    		<textarea class="form-control" name="incomeNote" placeholder="Note"></textarea>
					    	</div>
				      </div>
				      <div class="modal-footer">
				        <button type="submit" class="btn btn-success" name="addIncome" value="save">Save</button>
				      </div>
			      </form>
		  		
		  			
			  	</div>
				</div>
			</div>
		<?php } elseif($_GET['view'] == 'debitCredit'){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Debit Credit</h3></div>
			  <div class="panel-body">
			  	<div class="">
				  <form action="" method="POST">
			      	<div class="modal-body">
			        	<div class="row">
				        		<div class="form-group col-md-6">
						    		<label>Debit From:</label>
						    		<select class="form-control" name="subHeadIdFrom">
						    			<?php
						    				$cats = $wpdb->get_results( "SELECT * FROM ct_sub_head
											WHERE relation_to = 2 and head_id in (SELECT id FROM ct_head WHERE head_name IN ('Income', 'Bank', 'Cash')) ORDER BY sub_head_name ASC" );
					  						foreach ($cats as $cat) {
					  							echo "<option value='".$cat->id."'>".$cat->sub_head_name."</option>";
					  						}
						    			?>
						    		</select>
						    	</div>
				        		<div class="form-group col-md-6">
						    		<label>Credit To:</label>
						    		<select class="form-control" name="subHeadIdTo">
						    			<?php
						    				
					  						foreach ($cats as $cat) {
					  							echo "<option value='".$cat->id."'>".$cat->sub_head_name."</option>";
					  						}
						    			?>
						    		</select>
						    	</div>

						    	<div class="form-group col-md-6">
						    		<label>Amount:</label>
						    		<input class="form-control" type="number" name="debitCreditAmount" placeholder="Amount" required>
						    	</div>
									
									<div class="form-group col-md-6">
										<label>Date:</label>
						    		<input class="form-control" type="Date" name="debitCreditDate" value="<?php echo date('Y-m-d'); ?>">
						    	</div>

			        	</div>
					    	<div class="form-group">
					    		<label>Note:</label>
					    		<textarea class="form-control" name="debitCreditNote" placeholder="Note"></textarea>
					    	</div>
				      </div>
				      <div class="modal-footer">
				        <button type="submit" class="btn btn-success" name="debitCredit" value="Send">Send</button>
				      </div>
			      </form>
		  		
		  			
			  	</div>
				</div>
			</div>
  	
		<?php }elseif($_GET['view'] == 'expense'){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Expense</h3></div>
			  <div class="panel-body">
			  	<div class="">
				  <form action="" method="POST">
			      	<div class="modal-body">
			        	<div class="row">
				        	<div class="form-group col-md-6">
						    		<label>Category:</label>
						    		<select class="form-control" name="subHeadId">
						    			<?php
						    				$cats = $wpdb->get_results( "SELECT * FROM ct_sub_head
											WHERE (relation_to = 2 and head_id = 3) OR head_id = 5 ORDER BY sub_head_name ASC" );
					  						foreach ($cats as $cat) {
					  							echo "<option value='".$cat->id."'>".$cat->sub_head_name."</option>";
					  						}
						    			?>
						    		</select>
						    	</div>

						    	<div class="form-group col-md-6">
						    		<label>Amount:</label>
						    		<input class="form-control" type="number" name="expenseAmount" placeholder="Amount" required>
						    	</div>
									
									<div class="form-group col-md-6">
										<label>Date:</label>
						    		<input class="form-control" type="Date" name="expenseDate" value="<?php echo date('Y-m-d'); ?>">
						    	</div>

			        	</div>
					    	<div class="form-group">
					    		<label>Note:</label>
					    		<textarea class="form-control" name="expenseNote" placeholder="Note"></textarea>
					    	</div>
				      </div>
				      <div class="modal-footer">
				        <button type="submit" class="btn btn-success" name="addExpense" value="save">Save</button>
				      </div>
			      </form>
		  		
		  			
			  	</div>
				</div>
			</div>
		<?php }elseif($_GET['view'] == 'category'){  ?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Category <button class="btn btn-success pull-right" id="addCategoryBtn">Add Category</button></h3>
			  </div>
				  <div class="panel-body">
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th class="number">#</th>
									<th>Category Name</th>
									<th >Category Type</th>
									<th >Sort Order</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$expense = $wpdb->get_results( "SELECT * FROM `ct_sub_head` where isHidden is null order by id desc limit 100" );
									foreach ($expense AS $key => $exp) {
										?>
										<tr>
	  									<td><?= $key+1 ?></td>
	  									<td class="name"><?= $exp->sub_head_name ?></td>
	  									<td class="type"><?= getHeadName($exp->head_id)?></td>
	  									<td class="head_id" style="display:none"><?=$exp->head_id?></td>
	  									<td class="cat_id" style="display:none"><?=$exp->id?></td>
	  									<td class="relation_to" style="display:none"><?=$exp->relation_to?></td>
	  									<td class="cattype" style="display:none"><?=$exp->type?></td>
	  									<td class="status" style="display:none"><?=$exp->status?></td>
	  									<td class="is_editable" style="display:none"><?=$exp->is_editable?></td>
	  									<td class="sort_order" ><?=$exp->sort_order?></td>
											<td>
												<ul class="list-unstyled list-inline pull-right">
													<li>
														<button type="button" class="btn btn-sm btn-info editCategory">
									        		<span class="dashicons dashicons-welcome-write-blog"></span>
									        	</button>
													</li>
													<!-- <li>
			  										<form method="POST" action="">
			  											<input type="hidden" class="catID" name="id" value="<?= $exp->id ?>">
										        	<button type="submit" class="btn btn-sm btn-danger" name="delCat">
										        		<span class="dashicons dashicons-trash"></span>
										        	</button>
										        </form>
													</li> -->
												</ul>
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

			<!-- Add Category Modal -->
			<div id="addCatModal" class="modal fade" role="dialog">
			  <div class="modal-dialog">
			    <div class="modal-content">
			      <div class="modal-header">
			        <button type="button" class="close" data-dismiss="modal">&times;</button>
			        <h4 class="modal-title">Add Category</h4>
			      </div>
			      
			      <form action="" method="POST">
			      	<input type="hidden" name="id" class="catId">
			      	<div class="modal-body">
			        	<div class="form-group">
			        		<label>Category Name</label>
			        		<input class="form-control name" type="text" name="sub_head_name">
			        	</div>
			        	<div class="form-group">
			        		<label>Income Or Expense Or Bank ?</label>
			        		<p><label><input type="radio" name="head_id" value="2" checked> Income</label></p>
			        		<p><label><input type="radio" name="head_id" value="3"> Expense</label> </p>
			        		<p><label><input type="radio" name="head_id" value="4"> Bank</label> </p>
			        		<p><label><input type="radio" name="head_id" value="5"> Income & Expense</label> </p>
			        	</div>
			        	<div class="form-group">
			        		<label>Relation To</label>
			        		<p><label><input type="radio" name="relation_to" value="1" checked> Student</label></p>
			        		<p><label><input type="radio" name="relation_to" value="2"> School</label> </p>
			        		<p><label><input type="radio" name="relation_to" value="3"> Other</label> </p>
			        	</div>
			        	<div class="form-group">
			        		<label>Select Type</label>
			        		<p><label><input type="radio" name="type" value="1" checked> Monthly</label></p>
			        		<p><label><input type="radio" name="type" value="2"> Yearly</label> </p>
			        		<p><label><input type="radio" name="type" value="3"> Exam</label> </p>
			        		<p><label><input type="radio" name="type" value="4"> Other</label> </p>
			        	</div>
			        	<div class="form-group">
			        		<label>Select Status</label>
			        		<p><label><input type="radio" name="status" value="1" checked> Active</label></p>
			        		<p><label><input type="radio" name="status" value="0"> Inactive</label> </p>
			        	</div>
			        	<div class="form-group">
			        		<label>Editable Field?</label>
			        		<p><label><input type="radio" name="is_editable" value="1" > Yes</label></p>
			        		<p><label><input type="radio" name="is_editable" value="0" checked> No</label> </p>
			        	</div>
						<div class="form-group">
			        		<label>Sort Order</label>
			        		<input class="form-control sort_order" type="number" name="sort_order">
			        	</div>
				      </div>
				      <div class="modal-footer">
				        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				        <button type="submit" class="btn btn-success addCatSubmit" name="addCategory" value="save">Save</button>
				      </div>
			      </form>

				  </div>
			  </div>
			</div>

		
		<?php }elseif($_GET['view'] == 'summary-report'){  ?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Summary Report </h3>
			  </div>
				  <div class="panel-body">
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th class="number">#</th>
									<th>Accounts Name</th>
									<th >Balance</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$query = $wpdb->get_results( "SELECT * FROM `view_ledger_summary` order by sub_head_name asc" );
									foreach ($query AS $key => $val) {
										?>
										<tr>
	  									<td><?= $key+1 ?></td>
	  									<td><?= $val->sub_head_name ?></td>
	  									<td><?= number_format( $val->balance,2)?> TK</td>
	  								</tr>
										<?php
									}
								?>
							</tbody>
						</table>
				  </div>
				</div>
			</div>
		<?php }elseif($_GET['view'] == 'transaction-report'){  $transactionList = [];?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Transaction Report </h3>
			  </div>
				  <div class="panel-body">
					<form action="" method="POST" class="form-inline">
						<div class="row pl10" style="padding-left: 10px;">
						<div class="form-group result-class">
							  <label>Select Head Name</label><br>
							  <select class="form-control" name="sub_head_id" required>
							  <?php
								  $list = getIncomeSchoolHeadname();
								  echo "<option value=''>Select Head Name</option>";
	  
								  foreach ($list as $val) {?>
								  <option value="<?=$val->id?>" ><?= $val->sub_head_name ?></option>;
								<?php  }
							  ?>
							  </select>
						  </div>
							
							<div class="form-group">
									<label>From Date </label><br>
									<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							<div class="form-group">
									<label>To Date </label><br>
									<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							
							
						  <div class="form-group">
							  <input class="form-control btn-success" name="transaction-report" type="submit" style ="margin-top:30px" value="Search">
						  </div>
						</div>			  		
						  </form>
					</div>
					<?php
					if(isset($_POST['transaction-report']) ){
						$sub_head_id		= $_POST['sub_head_id'];
						$from_date			= $_POST['from-date'];
						$to_date			= $_POST['to-date'];
						$previousBalance = $wpdb->get_results( "SELECT SUM(credit)-SUM(debit) AS previousBalance FROM ct_ledger WHERE sub_head_id = '$sub_head_id' AND date(date) < '$from_date' " );
				// 		$transactionList = $wpdb->get_results( "SELECT * FROM ct_ledger WHERE sub_head_id = '$sub_head_id' AND date(date) >= '$from_date' AND date(date) <= '$to_date'" );
						$previousBalance = $previousBalance[0]->previousBalance;
						$transactionList = $wpdb->get_results(
                                                $wpdb->prepare(
                                                    "SELECT ct.*, sm.display_name 
                                                     FROM ct_ledger ct
                                                     JOIN sm_users sm ON ct.created_by = sm.ID
                                                     WHERE ct.sub_head_id = %d 
                                                       AND DATE(ct.date) >= %s 
                                                       AND DATE(ct.date) <= %s",
                                                    $sub_head_id, $from_date, $to_date
                                                )
                                            );
							
						?>

<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
							table th, table td {
								border:1px solid #000;
								padding:0.5em;
							}
							.table-bordered{
								border-collapse: collapse;
							}
							table{
									width:100%;
									
									overflow-wrap: break-word;
								}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin-bottom: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<span style="font-size: 17px;">Accounts Transaction Report</span>
					  		<br><span style="font-size: 17px;">FROM <?= date('d-m-Y',strtotime($from_date)) ?> TO <?= date('d-m-Y',strtotime($to_date)) ?> </span>
							  <p style="margin: 0;">Previous Balance: <?= $previousBalance == null? '0' : $previousBalance ?> TK</p> 

				  		</div>	
						  <?php } ?>
				  <div class="panel-body">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th class="number">#</th>
									<th>Date</th>
									<th>Reference</th>
									<th>Accountant Name</th>
									<th >Credit</th>
									<th >Debit</th>
									<th >Balance</th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach (@$transactionList AS $key => $val) {
										?>
										<tr>
	  									<td><?= $key+1 ?></td>
	  									<td><?= date('d-m-Y',strtotime($val->date)) ?></td>
	  									<td><?=  $val->reference?> </td>
	  									<td><?=  $val->display_name?> </td>
	  									<td><?= number_format( $val->credit,2)?> TK</td>
	  									<td><?= number_format( $val->debit,2)?> TK</td>
										  <?php
										  	if($key == 0){
												//   echo $previousBalance;exit;
												$newBalance = $previousBalance + $val->credit - $val->debit;
											  }else{
												$newBalance = $newBalance + $val->credit - $val->debit;
											  }
												
										  ?>
	  									<td><?= number_format( $newBalance,2)?> TK</td>
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
			</div>
			</div>
		<?php }elseif($_GET['view'] == 'income-report'){  $transactionList = [];?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Income Report </h3>
			  </div>
				  <div class="panel-body">
					<form action="" method="POST" class="form-inline">
						<div class="row pl10" style="padding-left: 10px;">
						<div class="form-group result-class">
							  <label>Select Head Name</label><br>
							  <select class="form-control" name="sub_head_id" required>
							  <?php
								  $list = getIncomeHeadname();
								  echo "<option value=''>Select Head Name</option>";
	  
								  foreach ($list as $val) {?>
								  <option value="<?=$val->id?>" ><?= $val->sub_head_name ?></option>;
								<?php  }
							  ?>
							  </select>
						  </div>
							
							<div class="form-group">
									<label>From Date </label><br>
									<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							<div class="form-group">
									<label>To Date </label><br>
									<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							
							
						  <div class="form-group">
							  <input class="form-control btn-success" name="income-report" type="submit" style ="margin-top:30px" value="Search">
						  </div>
						</div>			  		
						  </form>
					</div>
					<?php
					if(isset($_POST['income-report']) ){
						$sub_head_id		= $_POST['sub_head_id'];
						$from_date			= $_POST['from-date'];
						$to_date			= $_POST['to-date'];
						$previousBalance = $wpdb->get_results( "SELECT SUM(credit)-SUM(debit) AS previousBalance FROM ct_ledger WHERE sub_head_id = '$sub_head_id' AND date(date) < '$from_date' " );
						$transactionList = $wpdb->get_results( "SELECT * FROM ct_ledger WHERE sub_head_id = '$sub_head_id' AND date(date) >= '$from_date' AND date(date) <= '$to_date'" );
						$previousBalance = $previousBalance[0]->previousBalance;
													
						?>

<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
								table th, table td {
									border:1px solid #000;
									padding:0.5em;
								}
								.table-bordered{
									border-collapse: collapse;
								}
								table{
									width:100%;
									
									overflow-wrap: break-word;
								}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<p style="margin: 0;">Income Report</p>
					  		<p style="margin: 0;">Account Name: <?= getSubHeadNameById($sub_head_id) ?></p>
					  		<p style="margin: 0;">FROM <?= date('d-m-Y',strtotime($from_date)) ?> TO <?= date('d-m-Y',strtotime($to_date)) ?></p>					  		
					  		<p style="margin: 0;">Previous Balance: <?= $previousBalance == null? '0' : $previousBalance ?> TK</p>
				  		</div>
				  		<br>
				<?php } ?>
				  <div class="panel-body">
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th class="number">#</th>
									<th>Date</th>
									<th>Reference</th>
									<th >Credit</th>
									<th >Debit</th>
									<th >Balance</th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach (@$transactionList AS $key => $val) {
										?>
										<tr>
	  									<td><?= $key+1 ?></td>
	  									<td><?= date('d-m-Y',strtotime($val->date)) ?></td>
	  									<td><?=  $val->reference?> </td>
	  									<td><?= number_format( $val->credit,2)?> TK</td>
	  									<td><?= number_format( $val->debit,2)?> TK</td>
										  <?php
										  	if($key == 0){
												//   echo $previousBalance;exit;
												$newBalance = $previousBalance + $val->credit - $val->debit;
											  }else{
												$newBalance = $newBalance + $val->credit - $val->debit;
											  }
												
										  ?>
	  									<td><?= number_format( $newBalance,2)?> TK</td>
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
				</div>
			</div>
		<?php }elseif($_GET['view'] == 'expense-report'){  $transactionList = [];?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Expense Report </h3>
			  </div>
				  <div class="panel-body">
					<form action="" method="POST" class="form-inline">
						<div class="row pl10" style="padding-left: 10px;">
						<div class="form-group result-class">
							  <label>Select Head Name</label><br>
							  <select class="form-control" name="sub_head_id" required>
							  <?php
								  $list = getExpenseHeadname();
								  echo "<option value=''>Select Head Name</option>";
	  
								  foreach ($list as $val) {?>
								  <option value="<?=$val->id?>" ><?= $val->sub_head_name ?></option>;
								<?php  }
							  ?>
							  </select>
						  </div>
							
							<div class="form-group">
									<label>From Date </label><br>
									<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							<div class="form-group">
									<label>To Date </label><br>
									<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							
							
						  <div class="form-group">
							  <input class="form-control btn-success" name="expense-report" type="submit" style ="margin-top:30px" value="Search">
						  </div>
						</div>			  		
						  </form>
					</div>
					<?php
					if(isset($_POST['expense-report']) ){
						$sub_head_id		= $_POST['sub_head_id'];
						$from_date			= $_POST['from-date'];
						$to_date			= $_POST['to-date'];
						$previousBalance = $wpdb->get_results( "SELECT SUM(credit)-SUM(debit) AS previousBalance FROM ct_ledger WHERE sub_head_id = '$sub_head_id' AND date(date) < '$from_date' " );
						$transactionList = $wpdb->get_results( "SELECT * FROM ct_ledger WHERE sub_head_id = '$sub_head_id' AND date(date) >= '$from_date' AND date(date) <= '$to_date'" );
						$previousBalance = $previousBalance[0]->previousBalance;
													
						?>

						<div class="container-fluid maxAdminpages">
			
							<div class="row">
								<div class="col-md-12">
								  <button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
							  </div>
							  <div class="col-md-12">
								  <div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
									  <div class="printArea" style="margin: 10px 30px 0;">
										  <style type="text/css">
											  table tr{ page-break-inside: avoid !important; }
											  table tr a{ text-decoration: none;color: #000; }
											  @page { size: 210mm 297mm !important; margin: 0 !important; }
										  </style>
										  <style>
					
												table th, table td {
													border:1px solid #000;
													padding:0.5em;
												}
												.table-bordered{
													border-collapse: collapse;
												}
												table{
													width:100%;
													
													overflow-wrap: break-word;
												}
										</style>
				
										  <div style="text-align: center; position: relative;">
											  <img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
											  <h2 style="margin: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
											  <p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
											  <p style="margin: 0;">Expense Report</p>
											  <p style="margin: 0;">Account Name: <?= getSubHeadNameById($sub_head_id) ?></p>
											  <p style="margin: 0;">FROM <?= date('d-m-Y',strtotime($from_date)) ?> TO <?= date('d-m-Y',strtotime($to_date)) ?></p>					  		
											  <p style="margin: 0;">Previous Balance: <?= $previousBalance == null? '0' : $previousBalance ?> TK</p>
										  </div>
										  <br>
				<?php } ?>
				  <div class="panel-body">
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th class="number">#</th>
									<th>Date</th>
									<th>Reference</th>
									<th >Credit</th>
									<th >Debit</th>
									<th >Balance</th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach (@$transactionList AS $key => $val) {
										?>
										<tr>
	  									<td><?= $key+1 ?></td>
	  									<td><?= date('d-m-Y',strtotime($val->date)) ?></td>
	  									<td><?=  $val->reference?> </td>
	  									<td><?= number_format( $val->credit,2)?> TK</td>
	  									<td><?= number_format( $val->debit,2)?> TK</td>
										  <?php
										  	if($key == 0){
												//   echo $previousBalance;exit;
												$newBalance = $previousBalance + $val->credit - $val->debit;
											  }else{
												$newBalance = $newBalance + $val->credit - $val->debit;
											  }
												
										  ?>
	  									<td><?= number_format( $newBalance,2)?> TK</td>
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
			</div>
			</div>
		<?php }elseif($_GET['view'] == 'columnary'){  ?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Columnary Report </h3>
			  </div>
				  <div class="panel-body">
					<form action="" method="POST" class="form-inline">
						<div class="row pl10" style="padding-left: 10px;">
						
							
							<div class="form-group">
									<label>From Date </label><br>
									<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							<div class="form-group">
									<label>To Date </label><br>
									<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							
							
						  <div class="form-group">
							  <input class="form-control btn-success" name="columnary" type="submit" style ="margin-top:30px" value="Search">
						  </div>
						</div>			  		
						  </form>
					</div>

					<?php
					
					if(isset($_POST['columnary']) ){
						$from_date			= $_POST['from-date'];
						$to_date			= $_POST['to-date'];
						$datediff = strtotime( $to_date) - strtotime($from_date);
						$datediff = round($datediff / (60 * 60 * 24));
						$opening_balance = 0;
						// $dDiff = $from_date->diff($to_date);
  						// echo $dDiff->format('%r%a');
						//   $date = strtotime("+1 day", strtotime($from_date));
						// print_r(date('Y-m-d', strtotime($from_date . ' +1 day')));exit;
						$feeHeadIncome = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 2 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
						$feeHeadExpense = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 3 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
						$reportArray = [];
						$previousReportArray = [];
						
						for($i = 0; $i <= $datediff; $i++){
							$sumOfTotal = 0;
							$sumOfTotalExpense = 0;
							$date = date('Y-m-d', strtotime($from_date . ' +'.$i.' day'));
							// for income
							foreach ($feeHeadIncome as $key => $val) {						
								
								if($i==0){
									$prevDate = date('Y-m-d', strtotime($from_date . ' -1 day'));
									$previousReportArray[$key]['sub_head_name'] = $val->sub_head_name;
									$previousReportArray[$key]['previous_balance'] = getPreviousBalance($prevDate, $val->id);
									$opening_balance += $previousReportArray[$key]['previous_balance'];
								}
								$reportArray[$date]['details'][$key]['current_balance'] = getDatewiseIncomeTotal($date, $val->id);
								$sumOfTotal +=	$reportArray[$date]['details'][$key]['current_balance'];

							}
							$reportArray[$date]['total'] = $sumOfTotal;


							// expense
							foreach ($feeHeadExpense as $key => $val) {						
								
								if($i==0){
									$reportArrayExpense[$date]['expense_head_name'][$key]['sub_head_name'] = $val->sub_head_name;									
								}
								$reportArrayExpense[$date]['details'][$key]['current_balance'] = getDatewiseExpenseTotal($date, $val->id);
								$sumOfTotalExpense +=	$reportArrayExpense[$date]['details'][$key]['current_balance'];

							}
							$reportArrayExpense[$date]['total'] = $sumOfTotalExpense;
						}

					
													
						?>
					
					<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

    <div id="printArea" class="col-md-12">
    <style type="text/css"> @page { size: auto;  margin: 0px; }
					  				.feeTbl th, .feeTbl td {
											text-align: center;
											vertical-align: middle !important;
											font-size: 11px;
											padding: 3px 2px !important;
											-webkit-print-color-adjust: exact;
										}
					  			</style>
                                  <style>
@media print {
  .col-md-6 {
        /* float: left !important; */
        width: 100% !important;

   }
   table {
        border: solid #000 !important;
        border-width: 1px 0 0 1px !important;
    }
    th, td {
        border: solid #000 !important;
        border-width: 0 1px 1px 0 !important;
		padding: 3px 2px !important;
    }
	.table-bordered{
				border-collapse: collapse;
			}
	.no-border{
		border: 0px !important;
	}
	table{
		width:100%;
		
		overflow-wrap: break-word;
	}
	
}

</style>
<style>
	@media print {
		.footer-ad-text{
			font-size: 12px; 		
			display: inline-block;
			position: fixed;
			vertical-align: bottom;
			width: 100%;
			bottom: 0;
			padding: 5px;
			margin-right: 5px;
		}

		}
</style>
					
		
				  <div class="panel-body text-center">
				  	<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
						<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
						<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
						<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
						<p style="margin: 0px">Columnary Report</p>
						<p style="margin: 0px">FROM <?= date('d-m-Y',strtotime(@$from_date)) ?> TO <?= date('d-m-Y',strtotime(@$to_date)) ?></p>
					</div>
					   
				</div>
		
				  <div class="panel-body text-center" style="justify-content: center;">
					  <div class="no-border" style="text-align: center;">
						  Income
					  </div>
					  <div style="text-align: -webkit-center;">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th>Date</th>
									<th>Total</th>
									<?php 
										foreach ($previousReportArray as $key => $head) {
									?>
									<th><?= $head['sub_head_name']?></th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Opening Balance</td>
									<td><?= $opening_balance ?></td>
									<?php 
										foreach ($previousReportArray as $key => $prev) {
									?>
										<td > <?= $prev['previous_balance'] ?></td>
									<?php } ?>
								</tr>
								<?php 
									$grandTotalIncome = 0;
									foreach ($reportArray as $key => $income) {
										$grandTotalIncome += $income['total'];
									?>
									<?php if($income['total'] > 0){ ?>
								<tr>
									<td><?= date('d-m-Y', strtotime($key)) ?></td>
									<td><?= $income['total'] ?></td>
									<?php 
										foreach ($reportArray[$key]['details'] as  $val) {
											
									?>
									<td><?= $val['current_balance'] ?></td>
									<?php } ?>
									</tr>
									<?php } ?>
									<?php } ?>
								
							</tbody>
						</table>
					</div>
					<div style="text-align: -webkit-center;">
					  <span class="text-center">Expense</span>
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th>Date</th>
									<th>Total</th>
									<?php 
										foreach ($reportArrayExpense[$from_date]['expense_head_name'] as $key => $head) {
											// echo '<pre>';
											// print_r($head);
									?>
									<th><?= $head['sub_head_name']?></th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								
								<?php 
								$grandTotalExp =0;
									foreach ($reportArrayExpense as $key => $exp) {
										// echo '<pre>';
										// print_r($income);exit;
										$grandTotalExp += $exp['total'];
								?>
								<?php if($exp['total'] > 0){ ?>
									<tr>
										<td><?= date('d-m-Y', strtotime($key)) ?></td>
										<td><?= $exp['total'] ?></td>
										<?php 
											foreach ($reportArrayExpense[$key]['details'] as  $val) {
												
										?>
										<td><?= $val['current_balance'] ?></td>
										<?php } ?>
									</tr>
								<?php } ?>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div style="text-align: -webkit-center;">
					  <span class="text-center">Columnary Summary</span>
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th>Head</th>
									<th>Amount</th>
									<th>Head</th>
									<th>Amount</th>
									
								</tr>
							</thead>
							<tbody>
								
								<tr>
									<td>Opening Balance</td>
									<td><?= number_format($opening_balance,2)?></td>
									<td>Total Expense</td>
									<td><?= number_format($grandTotalExp,2)?></td>
									
								</tr>
								<tr>
									<td>Total Income</td>
									<td><?= number_format($grandTotalIncome,2)?></td>
									<td rowspan="2" class="text-center" style="vertical-align:middle">In Hand</td>
									<td rowspan="2" class="text-center" style="vertical-align:middle"><?= $inhand =  number_format( ($opening_balance +  $grandTotalIncome - $grandTotalExp),2) ?></td>
								</tr>
								<tr>
									<td>Total Balance</td>
									<td><?= number_format( ($opening_balance +  $grandTotalIncome),2) ?></td>
								</tr>
								<tr>
									<td colspan="2" class="text-center">Closing Balance</td>
									<td colspan="2" class="text-center"><?= $inhand?></td>
								</tr>
							</tbody>
						</table>
					</div>
				  </div>
				  
				  <!-- footer text -->
				  <div class="row footer-ad-text">
					  <div class="col-md-7" style="float: left;" >
						  <span>Generated By: BORNOMALA - Education Management System</span><br>
						  <span>Developed By: MS3 Technology BD. Phone:  +88017442-21385. </span><br>
						  <span class="text-center">www.ms3technology.com.bd</span>
					  </div>
					  <div class="col-md-5 text-right" style="float: right; padding-right: 20px;">
						  <span>&copy; Copyright </span><br>
						  <span><?= $s3sRedux['institute_name'] ?></span><br>
						  <span><?= $_SERVER['HTTP_HOST']?></span>
					  </div>
				  </div>
				  <!-- footer text end -->
				</div>
			</div>
			<?php } ?>
		<?php }elseif($_GET['view'] == 'columnaryIncome'){  ?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Columnary Income Report </h3>
			  </div>
				  <div class="panel-body">
					<form action="" method="POST" class="form-inline">
						<div class="row pl10" style="padding-left: 10px;">
						
							
							<div class="form-group">
									<label>From Date </label><br>
									<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							<div class="form-group">
									<label>To Date </label><br>
									<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							
							
						  <div class="form-group">
							  <input class="form-control btn-success" name="columnaryIncome" type="submit" style ="margin-top:30px" value="Search">
						  </div>
						</div>			  		
						  </form>
					</div>

					<?php
					
					if(isset($_POST['columnaryIncome']) ){
						$from_date			= $_POST['from-date'];
						$to_date			= $_POST['to-date'];
						$datediff = strtotime( $to_date) - strtotime($from_date);
						$datediff = round($datediff / (60 * 60 * 24));
						$opening_balance = 0;
						// $dDiff = $from_date->diff($to_date);
  						// echo $dDiff->format('%r%a');
						//   $date = strtotime("+1 day", strtotime($from_date));
						// print_r(date('Y-m-d', strtotime($from_date . ' +1 day')));exit;
						$feeHeadIncome = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 2 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
						// $feeHeadExpense = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 3 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
						$reportArray = [];
						$previousReportArray = [];
						
						for($i = 0; $i <= $datediff; $i++){
							$sumOfTotal = 0;
							$sumOfTotalExpense = 0;
							$date = date('Y-m-d', strtotime($from_date . ' +'.$i.' day'));
							// for income
							foreach ($feeHeadIncome as $key => $val) {						
								
								if($i==0){
									$prevDate = date('Y-m-d', strtotime($from_date . ' -1 day'));
									$previousReportArray[$key]['sub_head_name'] = $val->sub_head_name;
									$previousReportArray[$key]['previous_balance'] = getPreviousBalance($prevDate, $val->id);
									$opening_balance += $previousReportArray[$key]['previous_balance'];
								}
								$reportArray[$date]['details'][$key]['current_balance'] = getDatewiseIncomeTotal($date, $val->id);
								$sumOfTotal +=	$reportArray[$date]['details'][$key]['current_balance'];

							}
							$reportArray[$date]['total'] = $sumOfTotal;


							
						}

					
													
						?>
					
					<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

    <div id="printArea" class="col-md-12">
    <style type="text/css"> @page { size: auto;  margin: 0px; }
					  				.feeTbl th, .feeTbl td {
											text-align: center;
											vertical-align: middle !important;
											font-size: 11px;
											padding: 3px 2px !important;
											-webkit-print-color-adjust: exact;
										}
					  			</style>
                                  <style>
@media print {
  .col-md-6 {
        /* float: left !important; */
        width: 100% !important;

   }
   table {
        border: solid #000 !important;
        border-width: 1px 0 0 1px !important;
		width: 100%;
    }
    th, td {
        border: solid #000 !important;
        border-width: 0 1px 1px 0 !important;
		padding: 3px 2px !important;
    }
	.table-bordered{
				border-collapse: collapse;
			}
	.no-border{
		border: 0px !important;
	}
	
}

</style>
<style>
	@media print {
		.footer-ad-text{
			font-size: 12px; 		
			display: inline-block;
			position: fixed;
			vertical-align: bottom;
			width: 100%;
			bottom: 0;
			padding: 5px;
			margin-right: 5px;
		}

		}
</style>
					
		
				  <div class="panel-body text-center">
				  	<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
						<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
						<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
						<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
						<p style="margin: 0px">Columnary Income Report</p>
						<p style="margin: 0px">FROM <?= date('d-m-Y',strtotime(@$from_date)) ?> TO <?= date('d-m-Y',strtotime(@$to_date)) ?></p>
					</div>
					   
				</div>
		
				  <div class="panel-body text-center" style="justify-content: center;">
					  <div class="no-border" style="text-align: center;">
						  Income
					  </div>
					  <div style="text-align: -webkit-center;">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th>Date</th>
									<th>Total</th>
									<?php 
										foreach ($previousReportArray as $key => $head) {
									?>
									<th><?= $head['sub_head_name']?></th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Opening Balance</td>
									<td><?= $opening_balance ?></td>
									<?php 
										foreach ($previousReportArray as $key => $prev) {
									?>
										<td > <?= $prev['previous_balance'] ?></td>
									<?php } ?>
								</tr>
								<?php 
									$grandTotalIncome = 0;
									foreach ($reportArray as $key => $income) {
										$grandTotalIncome += $income['total'];
									?>
									<?php if($income['total'] > 0){ ?>
								<tr>
									<td><?= date('d-m-Y', strtotime($key)) ?></td>
									<td><?= $income['total'] ?></td>
									<?php 
										foreach ($reportArray[$key]['details'] as  $val) {
											
									?>
									<td><?= $val['current_balance'] ?></td>
									<?php } ?>
									</tr>
									<?php } ?>
									<?php } ?>
								
							</tbody>
						</table>
					</div>
					
					<div style="text-align: -webkit-center;">
						<table class="table table-bordered table-striped">
							
							<tbody>
								<tr>
									<td>Total</td>
									<td><?= number_format( ($opening_balance +  $grandTotalIncome),2) ?></td>
								</tr>
								
							</tbody>
						</table>
					</div>
				  </div>
				  
				  <!-- footer text -->
				  <div class="row footer-ad-text">
					  <div class="col-md-7" style="float: left;" >
						  <span>Generated By: BORNOMALA - Education Management System</span><br>
						  <span>Developed By: MS3 Technology BD. Phone:  +88017442-21385. </span><br>
						  <span class="text-center">www.ms3technology.com.bd</span>
					  </div>
					  <div class="col-md-5 text-right" style="float: right; padding-right: 20px;">
						  <span>&copy; Copyright </span><br>
						  <span><?= $s3sRedux['institute_name'] ?></span><br>
						  <span><?= $_SERVER['HTTP_HOST']?></span>
					  </div>
				  </div>
				  <!-- footer text end -->
				</div>
			</div>
			<?php } ?>
			<?php }elseif($_GET['view'] == 'columnaryExpense'){  ?>
				<div class="panel panel-info">
				  <div class="panel-heading">
					  <h3>Columnary Expense Report </h3>
				  </div>
					  <div class="panel-body">
						<form action="" method="POST" class="form-inline">
							<div class="row pl10" style="padding-left: 10px;">
							
								
								<div class="form-group">
										<label>From Date </label><br>
										<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
								</div>
								<div class="form-group">
										<label>To Date </label><br>
										<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
								</div>
								
								
							  <div class="form-group">
								  <input class="form-control btn-success" name="columnaryExpense" type="submit" style ="margin-top:30px" value="Search">
							  </div>
							</div>			  		
							  </form>
						</div>
	
						<?php
						
						if(isset($_POST['columnaryExpense']) ){
							$from_date			= $_POST['from-date'];
							$to_date			= $_POST['to-date'];
							$datediff = strtotime( $to_date) - strtotime($from_date);
							$datediff = round($datediff / (60 * 60 * 24));
							$opening_balance = 0;
							// $dDiff = $from_date->diff($to_date);
							  // echo $dDiff->format('%r%a');
							//   $date = strtotime("+1 day", strtotime($from_date));
							// print_r(date('Y-m-d', strtotime($from_date . ' +1 day')));exit;
							//$feeHeadIncome = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 2 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
							$feeHeadExpense = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 3 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
							$reportArray = [];
							$previousReportArray = [];
							
							for($i = 0; $i <= $datediff; $i++){
								$sumOfTotal = 0;
								$sumOfTotalExpense = 0;
								$date = date('Y-m-d', strtotime($from_date . ' +'.$i.' day'));
								
	
	
								// expense
								foreach ($feeHeadExpense as $key => $val) {						
									
									if($i==0){
										$reportArrayExpense[$date]['expense_head_name'][$key]['sub_head_name'] = $val->sub_head_name;									
									}
									$reportArrayExpense[$date]['details'][$key]['current_balance'] = getDatewiseExpenseTotal($date, $val->id);
									$sumOfTotalExpense +=	$reportArrayExpense[$date]['details'][$key]['current_balance'];
	
								}
								$reportArrayExpense[$date]['total'] = $sumOfTotalExpense;
							}
	
						
														
							?>
						
						<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
	
		<div id="printArea" class="col-md-12">
		<style type="text/css"> @page { size: auto;  margin: 0px; }
										  .feeTbl th, .feeTbl td {
												text-align: center;
												vertical-align: middle !important;
												font-size: 11px;
												padding: 3px 2px !important;
												-webkit-print-color-adjust: exact;
											}
									  </style>
									  <style>
	@media print {
	  .col-md-6 {
			/* float: left !important; */
			width: 100% !important;
	
	   }
	   table {
			border: solid #000 !important;
			border-width: 1px 0 0 1px !important;
			width: 100%;
		}
		th, td {
			border: solid #000 !important;
			border-width: 0 1px 1px 0 !important;
			padding: 3px 2px !important;
		}
		.table-bordered{
					border-collapse: collapse;
				}
		.no-border{
			border: 0px !important;
		}
		
	}
	
	</style>
	<style>
		@media print {
			.footer-ad-text{
				font-size: 12px; 		
				display: inline-block;
				position: fixed;
				vertical-align: bottom;
				width: 100%;
				bottom: 0;
				padding: 5px;
				margin-right: 5px;
			}
	
			}
	</style>
						
			
					  <div class="panel-body text-center">
						  <div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
							<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
							<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
							<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
							<p style="margin: 0px">Columnary Expense Report</p>
							<p style="margin: 0px">FROM <?= date('d-m-Y',strtotime(@$from_date)) ?> TO <?= date('d-m-Y',strtotime(@$to_date)) ?></p>
						</div>
						   
					</div>
			
					  <div class="panel-body text-center" style="justify-content: center;">
						  
						<div style="text-align: -webkit-center;">
						  <span class="text-center">Expense</span>
							<table class="table table-bordered table-striped">
								<thead>
									<tr>
										<th>Date</th>
										<th>Total</th>
										<?php 
											foreach ($reportArrayExpense[$from_date]['expense_head_name'] as $key => $head) {
												// echo '<pre>';
												// print_r($head);
										?>
										<th><?= $head['sub_head_name']?></th>
										<?php } ?>
									</tr>
								</thead>
								<tbody>
									
									<?php 
									$grandTotalExp =0;
										foreach ($reportArrayExpense as $key => $exp) {
											// echo '<pre>';
											// print_r($income);exit;
											$grandTotalExp += $exp['total'];
									?>
									<?php if($exp['total'] > 0){ ?>
										<tr>
											<td><?= date('d-m-Y', strtotime($key)) ?></td>
											<td><?= $exp['total'] ?></td>
											<?php 
												foreach ($reportArrayExpense[$key]['details'] as  $val) {
													
											?>
											<td><?= $val['current_balance'] ?></td>
											<?php } ?>
										</tr>
									<?php } ?>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<div style="text-align: -webkit-center;">
							<table class="table table-bordered table-striped">
								
								<tbody>
									
									<tr>
										<td>Total Expense</td>
										<td><?= number_format($grandTotalExp,2)?></td>
										
									</tr>									
								</tbody>
							</table>
						</div>
					  </div>
					  
					  <!-- footer text -->
					  <div class="row footer-ad-text">
						  <div class="col-md-7" style="float: left;" >
							  <span>Generated By: BORNOMALA - Education Management System</span><br>
							  <span>Developed By: MS3 Technology BD. Phone:  +88017442-21385. </span><br>
							  <span class="text-center">www.ms3technology.com.bd</span>
						  </div>
						  <div class="col-md-5 text-right" style="float: right; padding-right: 20px;">
							  <span>&copy; Copyright </span><br>
							  <span><?= $s3sRedux['institute_name'] ?></span><br>
							  <span><?= $_SERVER['HTTP_HOST']?></span>
						  </div>
					  </div>
					  <!-- footer text end -->
					</div>
				</div>
				<?php } ?>
		<?php }elseif($_GET['view'] == 'cashbook'){  ?>
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>Cash Book Report </h3>
			  </div>
				  <div class="panel-body">
					<form action="" method="POST" class="form-inline">
						<div class="row pl10" style="padding-left: 10px;">
						
							
							<div class="form-group">
									<label>From Date </label><br>
									<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							<div class="form-group">
									<label>To Date </label><br>
									<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
							</div>
							
							
						  <div class="form-group">
							  <input class="form-control btn-success" name="cashbook" type="submit" style ="margin-top:30px" value="Search">
						  </div>
						</div>			  		
						  </form>
					</div>

					<?php
					
					if(isset($_POST['cashbook']) ){
						$from_date			= $_POST['from-date'];
						$to_date			= $_POST['to-date'];
						$previousBalance = getPreviousBalance($from_date, $cashSubHeadId);
						$feeHeadIncome = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 2 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
						$feeHeadExpense = $wpdb->get_results( "SELECT ct_sub_head.id,ct_sub_head.sub_head_name,  ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.head_id = 3 OR ct_sub_head.head_id = 5 ORDER BY ct_sub_head.sort_order ASC" );
						$reportArray = [];
						
					
							// for income
							$sumOfIncomeTotal = 0;
							$sumOfTotalExpense = 0;
							foreach ($feeHeadIncome as $key => $val) {	
								$reportArray['income'][$key]['sub_head_name'] = $val->sub_head_name;
								$reportArray['income'][$key]['amount'] = getDatewiseIncomeTotal($from_date,  $val->id, $to_date);
								$sumOfIncomeTotal +=	$reportArray['income'][$key]['amount'];

							}
							$reportArray['income_total'] = $sumOfIncomeTotal;


							// expense
							foreach ($feeHeadExpense as $key => $val) {	
								
								$reportArray['expense'][$key]['sub_head_name'] = $val->sub_head_name;
								$reportArray['expense'][$key]['amount'] = getDatewiseExpenseTotal($from_date, $val->id, $to_date);
								$sumOfTotalExpense +=	$reportArray['expense'][$key]['amount'];

							}
							$reportArray['expense_total'] = $sumOfTotalExpense;
						
							// echo '<pre>';
							// print_r($reportArray);exit;
						
													
						?>
					
					<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

    <div id="printArea" class="col-md-12">
    <style type="text/css"> @page { size: auto;  margin: 0px; }
					  				.feeTbl th, .feeTbl td {
											text-align: center;
											vertical-align: middle !important;
											font-size: 11px;
											padding: 3px 2px !important;
											-webkit-print-color-adjust: exact;
										}
					  			</style>
                                  <style>
@media print {
  .col-md-6 {
        /* float: left !important; */
        width: 100% !important;

   }
   table {
        border: solid #000 !important;
        border-width: 1px 0 0 1px !important;
    }
    th, td {
        border: solid #000 !important;
        border-width: 0 1px 1px 0 !important;
		padding: 3px 2px !important;
    }
	.table-bordered{
				border-collapse: collapse;
			}
	.no-border{
		border: 0px !important;
	}
	
}

</style>
<style>
	@media print {
		.footer-ad-text{
			font-size: 12px; 		
			display: inline-block;
			position: fixed;
			vertical-align: bottom;
			width: 100%;
			bottom: 0;
			padding: 5px;
			margin-right: 5px;
		}
		.border-less{
			border: none;
			text-align: center;
		}
		table{
			width: 100%;
			margin: auto;
		}
		.text-right{
			text-align: right;
		}

		}
</style>
					
		
				  <div class="panel-body text-center">
				  	<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
						<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
						<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
						<span style="font-size:15px;">Cash Book</span><br>
						<span style="font-size:15px;">FROM <?= date('d-m-Y',strtotime(@$from_date)) ?> TO <?= date('d-m-Y',strtotime(@$to_date)) ?></span>
					</div>
					   
				</div>
		
				  <div class="panel-body text-center" style="justify-content: center;">

					<div style="text-align: -webkit-center;">
						<table class="table table-bordered text-center">
							<tr class="border-less">
								<td>Cash In</td>
								<td>Cash Out</td>
							</tr>
							<tr style="vertical-align: top;">
								<!-- income -->
								<td>
									<table class="table table-bordered table-striped">
										<thead>
											<tr>
												<th class="text-center">sl</th>
												<th class="text-center">Account Name</th>
												<th class="text-center">Amount</th>
												
											</tr>
										</thead>
										<tbody>
											
											<tr>
												<td></td>
												<td >Balance B/D</td>
												<td class="text-right"><?= number_format($previousBalance,2) ?></td>
												
											</tr>
											<?php
												$i = 1;
												$totalIncome = 0;
												foreach($reportArray['income'] as $key=>$val){
												    $totalIncome += $val['amount'];
											?>
												<tr>
													<td><?= $i?></td>
													<td class="text-left"><?= $val['sub_head_name']?></td>
													<td class="text-right"><?= number_format( $val['amount'],2)?></td>
													
												</tr>
											<?php
												$i++;}
											?>
											
											
										</tbody>
									</table>
								</td>
								<!-- expense -->
								<td>
									<table class="table table-bordered table-striped">
										<thead>
											<tr>
												<th class="text-center">sl</th>
												<th class="text-center">Account Name</th>
												<th class="text-center">Amount</th>
												
											</tr>
										</thead>
										<tbody>
											
										<?php
												$i = 1;
												$totalExpense = 0;
												foreach($reportArray['expense'] as $key=>$val){
												    $totalExpense += $val['amount'];
											?>
												<tr>
													<td><?= $i?></td>
													<td class="text-left"><?= $val['sub_head_name']?></td>
													<td class="text-right"><?= number_format( $val['amount'],2)?></td>
													
												</tr>
											<?php
												$i++;}
											?>											
										</tbody>
									</table>
								</td>
							</tr>
							
							<tr>
								<td>
									<table class="table table-bordered">
									    <tr>
											<td class="text-right"> Total</td>
											<td class="text-right"><?= number_format( ($reportArray['income_total']),2) ?></td>
										</tr>
										<tr>
											<td class="text-right"><b>Grand Total</b></td>
											<td class="text-right"><b><?= number_format( ($previousBalance + $reportArray['income_total']),2) ?></b></td>
										</tr>
									</table>
								</td>
								<td>
									<table class="table table-bordered">
									    <tr>
											<td class="text-right"> Total</td>
											<td class="text-right"><?= number_format( ( $reportArray['expense_total']),2) ?></td>
										</tr>
										<tr>
											<td class="text-right"><b>Grand Total</b></td>
											<td class="text-right"><b><?= number_format( ( $reportArray['expense_total']),2) ?></b></td>
										</tr>
									</table>
								</td>
							</tr>
							
							<tr>
								<td>&nbsp;</td>
								<td></td>
							</tr>
							<tr>
								<td></td>
								<td>
									<table class="table table-bordered">
										<tr>
											<td class="text-right"><b> Balance C/D</b></td>
											<td class="text-right"><b><?= number_format( ($previousBalance + $reportArray['income_total'] - $reportArray['expense_total']),2) ?></b></td>
										</tr>
									</table>
									
									
								</td>
							</tr>
							
						</table>
						
					</div>
				  </div>
				  
				  <!-- footer text -->
				  <div class="row footer-ad-text">
					  <div class="col-md-7" style="float: left;" >
						  <span>Generated By: BORNOMALA - Education Management System</span><br>
						  <span>Developed By: MS3 Technology BD. Phone:  +88017442-21385. </span><br>
						  <span class="text-center">www.ms3technology.com.bd</span>
					  </div>
					  <div class="col-md-5 text-right" style="float: right; padding-right: 20px;">
						  <span>&copy; Copyright </span><br>
						  <span><?= $s3sRedux['institute_name'] ?></span><br>
						  <span><?= $_SERVER['HTTP_HOST']?></span>
					  </div>
				  </div>
				  <!-- footer text end -->
				</div>
			</div>
			<?php } ?>
		<?php } ?>
		
		

	</div>


<?php if ( ! is_admin() ) {  ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<script type="text/javascript">
	(function($) {
		$catmodal = $('#addCatModal');
		
		$('#addCategoryBtn').click(function(event) {
			$catmodal.find('.modal-title').text('Add Category');
			$catmodal.find('.name').val('');
			$catmodal.find('.addCatSubmit').val('save');
			$catmodal.modal('show');
		});

		$('.editCategory').click(function(event) {
			$catmodal.find('.modal-title').text('Edit Category');
			$tr 	= $(this).closest('tr');

			$catmodal.find('.addCatSubmit').val('update');
			$catmodal.find('.name').val($tr.find('.name').text());
			// alert($tr.find('.head_id').text());
			$("input[name=head_id][value=" + $tr.find('.head_id').text() + "]").prop('checked', true);
			$("input[name=relation_to][value=" + $tr.find('.relation_to').text() + "]").prop('checked', true);
			$("input[name=type][value=" + $tr.find('.cattype').text() + "]").prop('checked', true);
			$("input[name=status][value=" + $tr.find('.status').text() + "]").prop('checked', true);
			$("input[name=is_editable][value=" + $tr.find('.is_editable').text() + "]").prop('checked', true);
			$catmodal.find('.sort_order').val($tr.find('.sort_order').text());
			$catmodal.find('.catId').val($tr.find('.cat_id').text());

			// $('input:radio[name=head_id]').filter([value=$tr.find('.head_id').text()]).attr('checked', true);
			// $('input:radio[name=head_id]').val([$tr.find('.head_id').val()]);
			// $catmodal.find('.catId').val($tr.find('.catID').val());
			// $catmodal.find("input[value='"+$tr.find('.type').text()+"']").click();
			$catmodal.modal('show');
		});


	})( jQuery );
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>