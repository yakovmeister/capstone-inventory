<?php

namespace Capstone\Model;

class Item extends \Illuminate\Database\Eloquent\Model 
{
	public $timestamps = false;
	
	protected $fillable = [
		'entity_name',
		'rcc',
		'fund_cluster',
		'supplier',
		'po_no',
		'requisition_office',
		'iar_no',
		'iar_date',
		'invoice_no',
		'invoice_date',
		'stock_no',
		'description',
		'unit',
		'quantity',
		'inspection_officer',
		'inspection_date',
		'supply_custodian',
		'date_received',
		'date_issued',				// not in use and to be depreciated
		'withdraw_name',			// not in use and to be depreciated
		'withdraw_designation',		// not in use and to be depreciated
		'withdraw_received_by',		// not in use and to be depreciated
		'withdraw_requested'		// not in use and to be depreciated
	];

	public function withdrawn()
	{
		return $this->hasMany("Capstone\\Model\\Withdraw");
	}
}

