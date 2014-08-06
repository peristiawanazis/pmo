<?php /* FINANCES tasks_tab.addedit.budget.php,v 0.1.0 2012/07/20  */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Generates the Budget tab in dotProject task view
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
*
*/
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}
// Get the global var
global $AppUI, $task_id, $task_project, $budget, $tab;

// Include the necessary classes
require_once $AppUI->getModuleClass('finances');

// Load the corresponding Budget
$budget = new CBudget();

if($budget->loadFromTask($task_id) != -1) { // If task is not dynamic
	?>
	<link href="./modules/finances/css/finances.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="./modules/finances/js/finances.js"></script>
	<form action="?m=tasks&amp;a=addedit&amp;task_project=<?php echo $task_project; ?>"
	  method="post" name="budgetFrm">
	<input type="hidden" name="sub_form" value="4" />
	<input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
	<input type="hidden" name="dosql" value="do_task_aed" />

	<table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
	<tr>
		<td valign="top" align="center">
			<input type="hidden" id="hequipment_investment" value="<?php echo $budget->equipment_investment;?>"/>
			<input type="hidden" id="hequipment_operation" value="<?php echo $budget->equipment_operation;?>"/>
			<input type="hidden" id="hintangible_investment" value="<?php echo $budget->intangible_investment;?>"/>
			<input type="hidden" id="hintangible_operation" value="<?php echo $budget->intangible_operation;?>"/>
			<input type="hidden" id="hservice_investment" value="<?php echo $budget->service_investment;?>"/>
			<input type="hidden" id="hservice_operation" value="<?php echo $budget->service_operation;?>"/>
			<table cellspacing="0" cellpadding="3" border="1">
				<tr align="center">
					<td colspan="4"> 
						<?php echo $AppUI->_("Tax"); ?>: 	&#37;<input type="text" class="text" name="TVA" id="TVA" value="<?php echo ($task_id) ? $budget->Tax : mostCommonTax();?>" onchange="updateBudget();"/>
					</td>
				</tr>
				<tr align="center">
					<td colspan="4"> 
						<?php echo $AppUI->_("Amounts"); ?>: 	
						<label for="tax0"><input type="radio" name="display_tax" id="tax0" value=0 onChange="javascript:updateBudget()" <?php if($budget->display_tax == 0) echo "checked"; ?> /><?php echo $AppUI->_("without tax"); ?></label>
						<label for="tax1"><input type="radio" name="display_tax" id="tax1" value=1 onChange="javascript:updateBudget()" <?php if($budget->display_tax == 1) echo "checked"; ?> /><?php echo $AppUI->_("with tax"); ?></label>
					</td>
				</tr>
				<tr align="center">
					<td colspan="4">
						<label for="only_financial"><input type="checkbox" class="checkbox" <?php if($budget->only_financial) echo 'checked="checked"';?> name="only_financial" id="only_financial" /><?php echo $AppUI->_('This task is only financial'); ?></label>
					</td>
				</tr>
				<tr align="center" class="title">
					<td colspan="2"><?php echo $AppUI->_('Investment'); ?></th>
					<td colspan="2"><?php echo $AppUI->_('Operation'); ?></th>
				</tr>
				<tr align="right">
					<td><?php echo $AppUI->_('Equipment'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="equipment_investment" id="equipment_investment" value="<?php echo $budget->get_equipment_investment();?>" onchange="javascript:updateBudget();"/>
					</td>
					<td><?php echo $AppUI->_('Equipment'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="equipment_operation" id="equipment_operation" value="<?php echo $budget->get_equipment_operation();?>" onchange="javascript:updateBudget();"/>
					</td>
				</tr>
				<tr align="right">
					<td><?php echo $AppUI->_('Intangible'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="intangible_investment" id="intangible_investment" value="<?php echo $budget->get_intangible_investment();?>" onchange="javascript:updateBudget();"/>
					</td>
					<td><?php echo $AppUI->_('Intangible'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="intangible_operation" id="intangible_operation" value="<?php echo $budget->get_intangible_operation();?>" onchange="javascript:updateBudget();"/>
					</td>
				</tr>
				<tr align="right">
					<td><?php echo $AppUI->_('Service'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="service_investment" id="service_investment" value="<?php echo $budget->get_service_investment();?>" onchange="javascript:updateBudget();"/>
					</td>
					<td><?php echo $AppUI->_('Service'); ?>: </td>
					<td class="hilite" width="150px"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="service_operation" id="service_operation" value="<?php echo $budget->get_service_operation();?>" onchange="javascript:updateBudget();"/>
					</td>
				</tr>
				<tr align="center">
					<td colspan="2" class="hilite"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="total_investment" id="total_investment" value="<?php echo $budget->get_investment();?>" disabled="disabled"/>
					</td>
					<td colspan="2" class="hilite"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="total_operation" id="total_operation" value="<?php echo $budget->get_operation();?>" disabled="disabled"/>
					</td>
				</tr>
				<tr align="center">
					<td colspan="4" class="hilite"><?php echo $dPconfig['currency_symbol']; ?>
						<input type="text" class="text" name="total" id="total" value="<?php echo $budget->get_total();?>" disabled="disabled"/>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
	</form>
	<script language="javascript" type="text/javascript">
	updateBudget();
	// Definition of local function that need PHP
	function checkAmount(id){
		if(isNaN(parseFloat(document.getElementById(id).value.replace(/ /g,''))))
			document.getElementById(id).value = 0;
		var val = parseFloat(document.getElementById(id).value.replace(/ /g,''));
		document.getElementById(id).value = val.toFixed(2);
	}

	function updateBudget(){
		// Set correct format
		checkAmount("equipment_investment");
		checkAmount("intangible_investment");
		checkAmount("service_investment");
		checkAmount("equipment_operation");
		checkAmount("intangible_operation");
		checkAmount("service_operation");
		
		// Compute sums
		var inv = parseFloat(document.getElementById("equipment_investment").value.replace(/ /g,''));
		inv += parseFloat(document.getElementById("intangible_investment").value.replace(/ /g,''));
		inv += parseFloat(document.getElementById("service_investment").value.replace(/ /g,''));
		document.getElementById("total_investment").value = addThousandsSep(inv.toFixed(2),' ');
		var ope = parseFloat(document.getElementById("equipment_operation").value.replace(/ /g,''));
		ope += parseFloat(document.getElementById("intangible_operation").value.replace(/ /g,''));
		ope += parseFloat(document.getElementById("service_operation").value.replace(/ /g,''));
		document.getElementById("total_operation").value = addThousandsSep(ope.toFixed(2),' ');
		var sum = inv+ope;
		document.getElementById("total").value = addThousandsSep(sum.toFixed(2),' ');
		document.getElementsByName("task_target_budget")[0].value = sum.toFixed(2);
	}
	
	function checkBudget(form, id) { return true; }	
	function saveBudget(form, id) { return true; }
	
	subForm.push(new FormDefinition(<?php echo $tab; ?>, document.budgetFrm, checkBudget, saveBudget));
	</script>
<?php
} else echo '<center>'.$AppUI->_('You can not set the budget of a dynamic task').'</center>';
?>
