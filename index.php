<?php 
/**
 * the capstone project:
 * @version 1.1.0 - changes
 * ----------------
 * - added date picker
 * - entity name is disabled (automatic)
 * - withdrawal logs added
 * - withdraw table added
 * - searcher now looks for description instead of entity name
 * - undefined index "mode" has been fixed
 * @version 1.0.0 - changes
 * - initial release
 */
require('vendor/autoload.php');
require('include/header.php');

// errors? say no more...
ini_set("display_errors", 0);

use Capstone\Model\Item;
use Capstone\Model\Withdraw;

$viewPageMode = 0; /* 0 = summary mode, 1 = stock card mode */
$withdrawSearch = null;

$items = Item::all();

/**
 * Presenting $_GET['key']
 * @since 1.0.0
 * This GET parameter is used to map certain form actions
 * for instance calling key=login will invoke our login action
 * -----------------------
 * login - is used to login our user
 * logout - does the opposite of login
 * item_store - lets us store an item to our inventory
 * withdraw_release - lets us issue an item to other department
 */

if(isset($_GET['key'])) {
	/** Login **/
	if($_GET['key'] == 'login') {
		$user = authenticate($_GET['username'], $_GET['password']);

		if(!$user) {
			$_SESSION['alert'] = [
				"message" => "Invalid login credentials",
				"error" => true
			];
		} else {
			$_SESSION['uid'] = $user->id;
			$_SESSION['alert'] = [
				"message" => "Welcome {$user->username}",
				"success" => true
			];
		}

		header("Location: index.php");
		die();
	}
	/** Login end**/
	/** Logout start **/
	elseif($_GET['key'] == 'logout') {
		
		unset($_SESSION["uid"]);
		
		$_SESSION['alert'] = [
			"message" => "Logged out successfully",
			"success" => true
		];

		header("location:index.php");
		die();
	}
	/** Logout end **/
	/** item_store start **/
	elseif($_GET['key'] == 'item_store') {
		/**
		 * we create a new instance of Item 
		 * afterwards we fill the model with the necessary values
		 */
		$newItem = new Capstone\Model\Item;
			$newItem->entity_name = $_GET['entity'] ?? "Cebu Technological University - Tuburan Campus";
			$newItem->rcc = $_GET['rcc'];
			$newItem->fund_cluster = null; /* useless */
			$newItem->supplier = $_GET['supplier'];
			$newItem->po_no = $_GET['po'];
			$newItem->requisition_office = $_GET['requisition_dept'];
			$newItem->iar_no = $_GET['iar_no'];
			$newItem->iar_date = $_GET['iar_date'];
			$newItem->invoice_no = $_GET['invoice_no'];
			$newItem->invoice_date = $_GET['invoice_date'];
			$newItem->stock_no = $_GET['stock_no'];
			$newItem->description = $_GET['description'];
			$newItem->unit = $_GET['unit'];
			$newItem->quantity = $_GET['quantity'];
			$newItem->inspection_officer = $_GET['inspection_officer'];
			$newItem->inspection_date = $_GET['inspection_date'];
			$newItem->supply_custodian = $_GET['supply_custodian'];
			$newItem->date_received = $_GET['date_received'];
			$newItem->date_issued = null;  			/* moved to withdraw table */
			$newItem->withdraw_name = null;			/* moved to withdraw table */
			$newItem->withdraw_designation = null;	/* moved to withdraw table */
			$newItem->withdraw_received_by = null;	/* moved to withdraw table */
			$newItem->withdraw_requested = null;	/* moved to withdraw table */
			$newItem->withdraw_purpose = null;		/* moved to withdraw table */
		$newItem->save();

		$_SESSION['alert'] = [
			"message" => "Item was kept successfully",
			"success" => true
		];

		header("location:index.php");
		die();
	}
	/** item_store end **/
	/** withdraw_release start **/
	elseif ($_GET['key'] == 'withdraw_release') {
		if($newItem = Item::find($_GET['item_id'])) {
			
			// check if stocks is greater or equal than the units being requested
			if($newItem->stock_no >= $_GET['withdraw_stock']) {
				$newItem->stock_no = ($newItem->stock_no - (int) $_GET['withdraw_stock']);
				$newItem->save();

				$withdraw = new Withdraw;
					$withdraw->item()->associate($newItem);
					$withdraw->date_issued =  date("Y-m-d");
					$withdraw->name =  $_GET['withdraw_name'];
					$withdraw->designation = $_GET['withdraw_designation'];
					$withdraw->receiver = $_GET['withdraw_received_by'];
					$withdraw->requestor = $_GET['withdraw_requested'];
					$withdraw->purpose = $_GET['withdraw_purpose'];
					$withdraw->stock = $_GET['withdraw_stock'];
				$withdraw->save();

				$_SESSION['alert'] = [
					"message" => "Item was released successfully",
					"success" => true
				];

				header("location:index.php?q=&page=withdraw&key=withdraw_search");
				die();
			}

		$_SESSION['alert'] = [
			"message" => "Insufficient stocks.",
			"error" => true
		];

		header("location:index.php?q=&page=withdraw&key=withdraw_search");
		die();
		} else {
			$_SESSION['alert'] = [
				"message" => "Something went wrong",
				"error" => true
			];

			header("location:index.php");
			die();
		} 
	}
	/** withdraw_release end */

}


if(isset($_GET['page'])) {
	if($_GET['page'] == 'view') {
		$mode = $_GET['mode'] ?? null;
		switch ($mode) {
			case 'stock_card':
				if(isset($_GET['key']) && $_GET['key'] == 'view_search') {
					$q = $_GET['q'];
					$items = Item::where('description', 'like', "%{$q}%")->where('stock_no', '>', 0)->get();
				} else {
					$items = Item::where('stock_no', '>', 0)->get();	
				}
				$viewPageMode = 1;
				break;
			case 'withdrawn': 
				if(isset($_GET['key']) && $_GET['key'] == 'view_search') {
					$q = $_GET['q'];
					$items = Withdraw::whereHas('item', function($qry) use ($q) {
						$qry->where('description', 'like', "%{$q}%");
					})->get();
				} else {
					$items = Withdraw::all();	
				}
				$viewPageMode = 2;
				break;
			case 'summary':
			default: // summary mode will be our default view
				if(isset($_GET['key']) && $_GET['key'] == 'view_search') {
					$q = $_GET['q'];
					$items = Item::where('description', 'like', "%{$q}%")->get();
				} else {
					$items = Item::all();
				}
				$viewPageMode = 0;
				break;
		}
	} elseif($_GET['page'] == 'withdraw' && isset($_GET['key'])) {
		$q = $_GET['q'];
		switch ($_GET['key']) {
			case 'withdraw_search':
				$withdrawSearch = Item::where('description', 'like', "%{$q}%")->where('stock_no', '>', 0)->get();
				break;
			default:
				$withdrawSearch = null;
				break;
		}
	}

}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Capstone</title>
		<link rel="stylesheet" href="assets/css/bootstrap.min.css"/>	
		<style type="text/css">
			.row {
				margin-left: 0;
				margin-right: 0;
			}
		</style>
		<?php if(!isset($_SESSION['uid'])) { ?>
		<link rel="stylesheet" href="assets/css/signin.css"/>
		<?php } ?>

	</head>
		<body>
		<?php if(isset($_SESSION['uid'])) { ?>
			
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">
				<nav class="navbar navbar-inverse">
	  				<ul class="nav navbar-nav">
	  					<li><a href="index.php">Home</a></li>
	  					<li><a href="index.php?q=&page=withdraw&key=withdraw_search">Withdraw</a></li>
	  					<li><a href="index.php?page=view">View</a></li>
	  				</ul>
	  				<ul class="nav navbar-nav pull-right">
	  					<li><a href="index.php?key=logout">Logout</a></li>
	  				</ul>
				</nav>
			</div>
			<div class="col-md-3"></div>
		</div>
	
		<?php } ?>

	<?php # NOTIFICATION START ?>
		<?php if(isset($_SESSION['alert'])): ?>
		<div class="row">
		<div class="col-md-4"></div>
		<div class="col-md-4">
		<?php if(isset($_SESSION['alert']['success'])): ?>								
			<div class="alert alert-success alert-dismissible fade in" role="alert"> 
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button> 
				<p><?=$_SESSION['alert']['message']?></p>
			</div>
		<?php elseif(isset($_SESSION['alert']['error'])): ?>	
			<div class="alert alert-danger alert-dismissible fade in" role="alert"> 
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button> 
				<p><?=$_SESSION['alert']['message']?></p>
			</div>
		<?php endif; ?>
		</div>
		<div class="col-md-4"></div>
		</div>
		<?php 
				unset($_SESSION['alert']);		
			endif;				
		?>	 
	<?php # NOTIFICATION END ?>

	<?php # show login form if user isn't logged in ?>
	<?php if(!isset($_SESSION['uid'])) { ?>
	<div class="row">
		<div class="container">
			<form class="form-signin">
        		<h2 class="form-signin-heading">Please sign in</h2>
        		<label for="inputUsername" class="sr-only">Email address</label>
        		<input type="text" id="inputUsername" name="username" class="form-control" placeholder="username" required autofocus>
        		<label for="inputPassword" class="sr-only">Password</label>
        		<input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
        		<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
        		<input type="hidden" name="key" value="login">
  	        </form>
    	</div> <!-- /container -->
	</div>
	<?php } else { ?>
		<?php if(!isset($_GET['page'])) { ?>
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">
				<h2>Inspection and Acceptance</h2>
				<form class="form-horizontal">
					<div class="row">
					<div class="row">
					<div class="form-group">
				    	<label for="entity" class="col-sm-3 control-label">Entity Name</label>
				    	<div class="col-sm-9">
				      		<input type="text" class="form-control" name="entity" id="entity" value="Cebu Technological University - Tuburan Campus" placeholder="Entity Name" disabled>
				    	</div>
				  	</div>
				  	</div>
					
					<div class="row">
					<div class="form-group">
				    	<label for="supplier" class="col-sm-3 control-label">Supplier Name</label>
				    	<div class="col-sm-9">
				      		<input type="text" class="form-control" name= "supplier" id="supplier" placeholder="Supplier">
				    	</div>
				  	</div>
					</div>

					<div class="row">
					<div class="form-group">
				    	<label for="po" class="col-sm-3 control-label">PO Date</label>
				    	<div class="col-sm-3">
				    		<div class='input-group date' id='po_date'>
					    		<input type="text" class="form-control" name= "po" id="po" placeholder="PO Date">
			                    <span class="input-group-addon">
			                        <span class="glyphicon glyphicon-calendar"></span>
			                    </span>
			                </div>
				    	</div>

				    	<label for="supplier" class="col-sm-3 control-label">RCC</label>
				    	<div class="col-sm-3">
				      		<input type="text" class="form-control" name= "rcc" id="rcc" placeholder="RCC">
				    	</div>
				  	</div>
				  	</div>
					
					<div class="row">
					<div class="form-group">
				    	<label for="requisition_dept" class="col-sm-3 control-label">Requisition Dept.</label>
				    	<div class="col-sm-9">
				      		<input type="text" class="form-control" name= "requisition_dept" id="requisition_dept" placeholder="Requisition Department">
				    	</div>
				  	</div>
					</div>
					</div><!-- parent row -->
					<div class="row">
						<div class="row">
						<div class="form-group">
					    	<label for="iar_no" class="col-sm-3 control-label">IAR Number</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name="iar_no" id="iar_no" placeholder="IAR Number">
					    	</div>
					    	<label for="iar_date" class="col-sm-3 control-label">IAR Date</label>
					    	<div class="col-sm-3">
						    	<div class='input-group date' id='iar_date'>
						    		<input type="text" class="form-control" name= "iar_date" id="iar_date" placeholder="IAR Date">
				                    <span class="input-group-addon">
				                        <span class="glyphicon glyphicon-calendar"></span>
				                    </span>
				                </div>
					    	</div>
					  	</div>
					  	</div>

					  	<div class="row">
						<div class="form-group">
					    	<label for="invoice_no" class="col-sm-3 control-label">Invoice Number</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name="invoice_no" id="invoice_no" placeholder="Invoice Number">
					    	</div>
					    	<label for="invoice_date" class="col-sm-3 control-label">Invoice Date</label>
					    	<div class="col-sm-3">
						    	<div class='input-group date' id='invoice_date'>
						    		<input type="text" class="form-control" name= "invoice_date" id="invoice_date" placeholder="Invoice Date">
				                    <span class="input-group-addon">
				                        <span class="glyphicon glyphicon-calendar"></span>
				                    </span>
				                </div>
					    	</div>
					  	</div>
					  	</div>
					</div><!-- parent row -->
					<div class="row">
						<div class="row">
						<div class="form-group">
					    	<label for="stock_no" class="col-sm-3 control-label">Stock #</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name= "stock_no" id="stock_no" placeholder="#">
					    	</div>
					    	<label for="quantity" class="col-sm-3 control-label">Quantity</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name= "quantity" id="quantity" placeholder="Quantity">
					    	</div>
					  	</div>
						</div>

						<div class="row">
						<div class="form-group">
					    	<label for="unit" class="col-sm-3 control-label">Unit</label>
					    	<div class="col-sm-9">
					      		<input type="text" class="form-control" name= "unit" id="unit" placeholder="Unit">
					    	</div>
					  	</div>
						</div>

						<div class="row">
						<div class="form-group">
					    	<label for="description" class="col-sm-3 control-label">Description</label>
					    	<div class="col-sm-9">
					      		<input type="text" class="form-control" name= "description" id="description" placeholder="Description">
					    	</div>
					  	</div>
						</div>

					</div><!-- parent row-->
					<div class="row">
						<div class="row">
						<div class="form-group">
					    	<label for="inspection_date" class="col-sm-3 control-label">Date Inspected</label>
					    	<div class="col-sm-3">
						    	<div class='input-group date' id='inspection_date'>
						    		<input type="text" class="form-control" name= "inspection_date" id="inspection_date" placeholder="Date Inspected">
						    		<span class="input-group-addon">
				                        <span class="glyphicon glyphicon-calendar"></span>
				                    </span>
				                </div>
					    	</div>
					    	<label for="inspection_officer" class="col-sm-3 control-label">Inspector</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name= "inspection_officer" id="inspection_officer" placeholder="Inspector">
					    	</div>
					  	</div>
						</div>

						<div class="row">
						<div class="form-group">
					    	<label for="date_received" class="col-sm-3 control-label">Date Received</label>
					    	<div class="col-sm-3">
					    		<div class='input-group date' id='received_date'>
									<input type="text" class="form-control" name= "date_received" id="date_received" placeholder="Date Received">
							   		<span class="input-group-addon">
				                        <span class="glyphicon glyphicon-calendar"></span>
				                    </span>
				                </div>
					      	</div>
					    	<label for="supply_custodian" class="col-sm-3 control-label">Property Custodian</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name= "supply_custodian" id="supply_custodian" placeholder="Property Custodian">
					    	</div>
					  	</div>
						</div>
					</div><!-- parent row -->
					<input type="hidden" name="key" value="item_store">
					<input type="submit" class="btn btn-primary btn-block btn-lg" value="Keep Item">
				</form>
			</div>
			<div class="col-md-3"></div>
		</div>		
		<?php } /* key isn't set */ 
			elseif($_GET['page'] == 'view') { ?>
		<?php if($viewPageMode == 0) { ?>
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">
					<form class="form-inline">
						<div class="form-group">
							<label for="q">Search an item: </label>
							<input type="text" class="form-control" name="q" placeholder="Search Item">
						</div>
						<input type="hidden" name="page" value="view">
						<input type="hidden" name="mode" value="summary">
						<input type="hidden" name="key" value="view_search">
						<button type="submit" class="btn btn-default">Search</button>
					</form>
					<a href="index.php?page=view&mode=summary" class="btn btn-primary pull-right">Summary View</a>
					<a href="index.php?page=view&mode=stock_card" class="btn btn-primary pull-right">Stock Card View</a>
					<a href="index.php?page=view&mode=withdrawn" class="btn btn-primary pull-right">Withdrawal Logs</a>
			</div>
			<div class="col-md-3"></div>
		</div>
		<div class="row">
			<table class="table table-striped">
  				<thead>
					<tr>
						<th>#</th>
						<th>Status</th>
						<th>RCC</th>
						<th>Supplier</th>
						<th>Requisition Office</th>
						<th>IAR #</th>
						<th>Invoice #</th>
						<th>Description</th>
						<th>Quantity</th>
						<th>Inspection Date</th>
						<th>Supply Arrived</th>
					</tr>
				</thead>
  				<tbody>
  					<?php foreach($items as $item){ ?>
						<tr>
							<td><?=$item->id?></td>
							<td><?= $item->stock_no > 0 ? "On Stock" : "Released" ?></td>
							<td><?=$item->rcc?></td>
							<td><?=$item->supplier?></td>
							<td><?=$item->requisition_office?></td>
							<td title="<?=$item->iar_date?>"><?=$item->iar_no?></td>
							<td title="<?=$item->invoice_date?>"><?=$item->invoice_no?></td>
							<td><?=$item->description ?></td>
							<td><?=$item->quantity?> <?=$item->units?></td>
							<td title="Inspected by: <?= $item->inspection_officer?>"><?=$item->inspection_date?></td>
							<td title="In custody of: <?= $item->supply_custodian?>"><?=$item->date_received?></td>
						</tr>
  					<?php } ?>
  				</tbody>
			</table>
		</div>
		<?php } elseif($viewPageMode == 1) { ?>
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">
					<form class="form-inline">
						<div class="form-group">
							<label for="q">Search an item: </label>
							<input type="text" class="form-control" name="q" placeholder="Search Item">
						</div>
						<input type="hidden" name="page" value="view">
						<input type="hidden" name="mode" value="stock_card">
						<input type="hidden" name="key" value="view_search">
						<button type="submit" class="btn btn-default">Search</button>
					</form>
					<a href="index.php?page=view&mode=summary" class="btn btn-primary pull-right">Summary View</a>
					<a href="index.php?page=view&mode=stock_card" class="btn btn-primary pull-right">Stock Card View</a>
					<a href="index.php?page=view&mode=withdrawn" class="btn btn-primary pull-right">Withdrawal Logs</a>
			</div>
			<div class="col-md-3"></div>
		</div>
			<div class="col-md-3"></div>
			<div class="col-md-6">
				<table class="table table-striped">
  				<thead>
					<tr>
						<th>Stock</th>
						<th>Description</th>
						<th>Quantity</th>
						<th>Storage Date</th>
					</tr>
				</thead>
  				<tbody>
  					<?php foreach($items as $item){ ?>
						<tr>
							<td><?=$item->stock_no?></td>
							<td><?=$item->description ?></td>
							<td><?=$item->quantity?> <?=$item->units?></td>
							<td title="In custody of: <?= $item->supply_custodian?>"><?=$item->date_received?></td>
						</tr>
  					<?php } ?>
  				</tbody>
				</table>
			</div>
			<div class="col-md-3"></div>		
		<?php } elseif($viewPageMode == 2)  { ?>
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-6">
					<form class="form-inline">
						<div class="form-group">
							<label for="q">Search an item: </label>
							<input type="text" class="form-control" name="q" placeholder="Search Item">
						</div>
						<input type="hidden" name="page" value="view">
						<input type="hidden" name="mode" value="withdrawn">
						<input type="hidden" name="key" value="view_search">
						<button type="submit" class="btn btn-default">Search</button>
					</form>
					<a href="index.php?page=view&mode=summary" class="btn btn-primary pull-right">Summary View</a>
					<a href="index.php?page=view&mode=stock_card" class="btn btn-primary pull-right">Stock Card View</a>
					<a href="index.php?page=view&mode=withdrawn" class="btn btn-primary pull-right">Withdrawal Logs</a>
			</div>
			<div class="col-md-3"></div>
		</div>
		<div class="row">
			<table class="table table-striped">
  				<thead>
					<tr>
						<th>#</th>
						<th>Supplier</th>
						<th>Requisition Office</th>
						<th>IAR #</th>
						<th>Invoice #</th>
						<th>Description</th>
						<th>Withdrawn units</th>
						<th>Withdrawn Date</th>
						<th>Requestor</th>
					</tr>
				</thead>
  				<tbody>
  					<?php foreach($items as $item){ ?>
						<tr>
							<td><?=$item->item->id?></td>
							<td><?=$item->item->supplier?></td>
							<td><?=$item->item->requisition_office?></td>
							<td title="<?=$item->item->iar_date?>"><?=$item->item->iar_no?></td>
							<td title="<?=$item->item->invoice_date?>"><?=$item->item->invoice_no?></td>
							<td><?=$item->item->description ?></td>
							<td title="<?= $item->item->stock_no ?> in stock"><?=$item->stock?></td>
							<td title="Withdrawn by: <?= $item->name?>, Received by: <?= $item->receiver?>"><?=$item->date_issued?></td>
							<td><?=$item->requestor?></td>
						</tr>
  					<?php } ?>
  				</tbody>
			</table>
		</div>
		<?php } ?>

		<?php } /* view page */ 
			elseif($_GET['page'] == 'withdraw') { ?>
			<div class="row">
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<form class="form-inline">
						<div class="form-group">
							<label for="q">Search item to be released: </label>
							<input type="text" class="form-control" name="q" placeholder="Search Item">
						</div>
						<input type="hidden" name="page" value="withdraw">
						<input type="hidden" name="key" value="withdraw_search">
						<button type="submit" class="btn btn-default">Search</button>
					</form>
				</div>
				<div class="col-md-3"></div>
			</div>
			<?php if(isset($_GET['mode']) && $_GET['mode'] == 'release' && isset($_GET['item_id'])) { ?>
				<?php $item = Item::find($_GET['item_id']); ?>
				<div class="col-md-3"></div>
				<div class="col-md-6">
					<div class="alert alert-success">You are releasing the item with following information: 
						<ul>
							<li>Entity Name: <?=$item->entity_name ?>, <?=$item->quantity?><?=$item->unit?></li>
							<li>Supplier: <?=$item->supplier?></li>
							<li>Invoice No: <?=$item->invoice_no?> </li>
							<li><?=$item->stock_no?> units on Stock</li>
						</ul>
					</div>
					<form class="form-horizontal">
						<div class="row">
						<div class="row">
						<div class="form-group">
					    	<label for="withdraw_purpose" class="col-sm-3 control-label">Purpose</label>
					    	<div class="col-sm-9">
					      		<input type="text" class="form-control" name="withdraw_purpose" id="withdraw_purpose" placeholder="Purpose">
					    	</div>
					  	</div>
					  	</div>

					  	<div class="form-group">
					    	<label for="withdraw_stock" class="col-sm-3 control-label">Withdrawn units</label>
					    	<div class="col-sm-9">
					      		<input type="text" class="form-control" name="withdraw_stock" id="withdraw_stock" placeholder="<?= $item->stock_no?> units on stock">
					    	</div>
					  	</div>
					  	</div>

						<div class="row">
						<div class="form-group">
					    	<label for="withdraw_name" class="col-sm-3 control-label">Name</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name="withdraw_name" id="withdraw_name" placeholder="Name">
					    	</div>
					    	<label for="withdraw_designation" class="col-sm-3 control-label">Designation</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name="withdraw_designation" id="withdraw_designation" placeholder="Designation">
					    	</div>
					  	</div>
					  	</div>

						<div class="row">
						<div class="form-group">
					    	<label for="withdraw_received_by" class="col-sm-3 control-label">Received By:</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name="withdraw_received_by" id="withdraw_received_by" placeholder="Received by">
					    	</div>
					    	<label for="withdraw_requested" class="col-sm-3 control-label">Requested By:</label>
					    	<div class="col-sm-3">
					      		<input type="text" class="form-control" name="withdraw_requested" id="withdraw_requested" placeholder="Requested by">
					    	</div>
					  	</div>
					  	</div>
							<input type="hidden" name="item_id" value="<?=$item->id?>">
							<input type="hidden" name="key" value="withdraw_release">
							<input type="submit" class="btn btn-block btn-lg btn-primary" value="Release">
					  	</div>
					</form>
				</div>
				<div class="col-md-3"></div>
			<?php } ?>
			<?php if(!empty($withdrawSearch)) { ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>#</th>
							<th>Entity Name</th>
							<th>Supplier</th>
							<th>Requisition Office</th>
							<th>Description</th>
							<th>Item in stock</th>
							<th>Quantity</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($withdrawSearch as $item) { ?>
						<tr>
							<td><?=$item->id?></td>
							<td><?=$item->entity_name?></td>
							<td><?=$item->supplier?></td>
							<td><?=$item->requisition_office?></td>
							<td><?=$item->description ?></td>
							<td><?=$item->stock_no?></td>
							<td><?=$item->quantity?> <?=$item->unit?></td>
							<td><a href="index.php?item_id=<?=$item->id?>&mode=release&page=withdraw">Release</a></td>
						</tr>
					<?php } ?>
					</tbody>
 				</table>
			<?php } ?>
			<?php } /* withdraw page */ ?>
	<?php } ?>

	<script type="text/javascript" src="assets/js/jquery.min.js"></script>
	<script type="text/javascript" src="assets/js/moment-with-locales.min.js"></script>
	<script type="text/javascript" src="assets/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="assets/js/bootstrap-datetimepicker.js"></script>
	<script type="text/javascript">
		$(function () {
            $('#po_date').datetimepicker({
            	format: 'YYYY-MM-DD'
            });
            $('#iar_date').datetimepicker({
            	format: 'YYYY-MM-DD'
            });
            $('#invoice_date').datetimepicker({
            	format: 'YYYY-MM-DD'
            });
            $('#inspection_date').datetimepicker({
            	format: 'YYYY-MM-DD'
            });
            $('#received_date').datetimepicker({
            	format: 'YYYY-MM-DD'
            });
        });
	</script>
	</body>
</html>