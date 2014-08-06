<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

// Set the pre and post save functions
global $pre_save, $post_save, $budget;

require_once $AppUI->getModuleClass('finances');

$pre_save[] = "finances_presave";
$post_save[] = "finances_postsave";

/**
 * presave functions are called before the session storage of tab data
 * is destroyed.  It can be used to save this data to be used later in
 * the postsave function.
 */
function finances_presave() {
	global $budget;
	$budget = new CBudget();
	$budget->Tax					= dPgetParam($_POST, 'TVA');
	$budget->only_financial 		= (dPgetParam($_POST, 'only_financial') == 'on' ? 1 : 0);
	$budget->display_tax			= dPgetParam($_POST, 'display_tax');
	if($budget->display_tax){
		$budget->equipment_investment	=	number_format(dPgetParam($_POST, 'equipment_investment')/($budget->Tax/100 + 1),2,'.','');
		$budget->intangible_investment	=	number_format(dPgetParam($_POST, 'intangible_investment')/($budget->Tax/100 + 1),2,'.','');
		$budget->service_investment		=	number_format(dPgetParam($_POST, 'service_investment')/($budget->Tax/100 + 1),2,'.','');
		$budget->equipment_operation	=	number_format(dPgetParam($_POST, 'equipment_operation')/($budget->Tax/100 + 1),2,'.','');
		$budget->intangible_operation	=	number_format(dPgetParam($_POST, 'intangible_operation')/($budget->Tax/100 + 1),2,'.','');
		$budget->service_operation		=	number_format(dPgetParam($_POST, 'service_operation')/($budget->Tax/100 + 1),2,'.','');
	}else{
		$budget->equipment_investment	=	dPgetParam($_POST, 'equipment_investment');
		$budget->intangible_investment	=	dPgetParam($_POST, 'intangible_investment');
		$budget->service_investment		=	dPgetParam($_POST, 'service_investment');
		$budget->equipment_operation	=	dPgetParam($_POST, 'equipment_operation');
		$budget->intangible_operation	=	dPgetParam($_POST, 'intangible_operation');
		$budget->service_operation		=	dPgetParam($_POST, 'service_operation');
	}
}

/**
 * postsave functions are only called after a succesful save.  They are
 * used to perform database operations after the event.
 */
function finances_postsave()
{
	global $budget;
	global $obj;
  
	$budget->task_id = $obj->task_id;
	dprint(__FILE__, __LINE__, 5, "saving budget");
	$budget->store();
}
?>
