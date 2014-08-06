<?php /* FINANCES tasks_tab.view.detailed_budget.php,v 0.1.0 2012/07/20  */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Generates the Detailed Budget tab in dotProject tasks view
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
global $AppUI, $budget, $task_id;

// Include the necessary classes
require_once $AppUI->getModuleClass('finances');

// Load the corresponding Budget
$budget = new CBudget();
$budget->loadFromTask($task_id);
?>
<script type="text/javascript" src="./modules/finances/js/finances.js"></script>
<table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
<tr>
	<td valign="top" align="center">
		<?php echo $AppUI->_("Display"); ?>: 
		<label for="currency0"><input type="radio" name="currency" id="currency0" value=0 onChange="javascript:updateBudget()" <?php if($budget->bestCurrency() == 0) echo "checked"; ?>/><?php echo $dPconfig['currency_symbol']; ?></label>
		<label for="currency1"><input type="radio" name="currency" id="currency1" value=1 onChange="javascript:updateBudget()" <?php if($budget->bestCurrency() == 1) echo "checked"; ?>/><?php echo "k".$dPconfig['currency_symbol']; ?></label>
		<label for="currency2"><input type="radio" name="currency" id="currency2" value=2 onChange="javascript:updateBudget()" <?php if($budget->bestCurrency() == 2) echo "checked"; ?>/><?php echo "M".$dPconfig['currency_symbol']; ?></label>
		<br/><?php echo $AppUI->_("Amounts"); ?>: 	
		<label for="tax0"><input type="radio" name="tax" id="tax0" value=0 onChange="javascript:updateBudget()" <?php if($budget->display_tax == 0) echo "checked"; ?> /><?php echo $AppUI->_("without tax"); ?></label>
		<label for="tax1"><input type="radio" name="tax" id="tax1" value=1 onChange="javascript:updateBudget()" <?php if($budget->display_tax == 1) echo "checked"; ?> /><?php echo $AppUI->_("with tax"); ?></label>
		<input type="hidden" id="hequipment_investment" value="<?php echo $budget->equipment_investment;?>"/>
		<input type="hidden" id="hequipment_operation" value="<?php echo $budget->equipment_operation;?>"/>
		<input type="hidden" id="hintangible_investment" value="<?php echo $budget->intangible_investment;?>"/>
		<input type="hidden" id="hintangible_operation" value="<?php echo $budget->intangible_operation;?>"/>
		<input type="hidden" id="hservice_investment" value="<?php echo $budget->service_investment;?>"/>
		<input type="hidden" id="hservice_operation" value="<?php echo $budget->service_operation;?>"/>
		<table cellspacing="0" cellpadding="3" border="1">
			<tr align="center">
				<td colspan="4"><?php echo $AppUI->_("Tax"); ?>: <?php echo $budget->Tax;?>&#37;</td>
			</tr>
			<tr align="center">
				<td colspan="4">
					<input type="checkbox" class="checkbox" <?php if($budget->only_financial) echo 'checked="checked"';?> name="only_financial" id="only_financial" disabled="disabled"/><?php echo $AppUI->_('This task is only financial'); ?>
				</td>
			</tr>
			<tr align="center" class="title">
				<td colspan="2"><?php echo $AppUI->_('Investment'); ?></th>
				<td colspan="2"><?php echo $AppUI->_('Operation'); ?></th>
			</tr>
			<tr align="right">
				<td><?php echo $AppUI->_('Equipment'); ?>: </td>
				<td class="hilite" width="150px" id="equipment_investment"><?php echo $budget->get_equipment_investment().$dPconfig['currency_symbol'];?></td>
				<td><?php echo $AppUI->_('Equipment'); ?>: </td>
				<td class="hilite" width="150px" id="equipment_operation"><?php echo $budget->get_equipment_operation().$dPconfig['currency_symbol']; ?></td>
			</tr>
			<tr align="right">
				<td><?php echo $AppUI->_('Intangible'); ?>: </td>
				<td class="hilite" width="150px" id="intangible_investment"><?php echo $budget->get_intangible_investment().$dPconfig['currency_symbol']; ?></td>
				<td><?php echo $AppUI->_('Intangible'); ?>: </td>
				<td class="hilite" width="150px" id="intangible_operation"><?php echo $budget->get_intangible_operation().$dPconfig['currency_symbol']; ?></td>
			</tr>
			<tr align="right">
				<td><?php echo $AppUI->_('Service'); ?>: </td>
				<td class="hilite" width="150px" id="service_investment"><?php echo $budget->get_service_investment().$dPconfig['currency_symbol']; ?></td>
				<td><?php echo $AppUI->_('Service'); ?>: </td>
				<td class="hilite" width="150px" id="service_operation"><?php echo $budget->get_service_operation().$dPconfig['currency_symbol']; ?></td>
			</tr>
			<tr align="center">
				<td colspan="2" class="hilite" id="total_investment"><?php echo $budget->get_investment().$dPconfig['currency_symbol']; ?></td>
				<td colspan="2" class="hilite" id="total_operation"><?php echo $budget->get_operation().$dPconfig['currency_symbol']; ?></td>
			</tr>
			<tr align="center">
				<td colspan="4" class="hilite" id="total"><?php echo $budget->get_total().$dPconfig['currency_symbol']; ?></td>
			</tr>
		</table>
	</td>
</tr>
</table>
<script language="javascript" type="text/javascript">
// Definition of function that need PHP
function getCurrency(){
	var radios = document.getElementsByName("currency");
	if(radios[2].checked)
		return "M<?php echo $dPconfig['currency_symbol']; ?>";
	if(radios[1].checked)
		return "k<?php echo $dPconfig['currency_symbol']; ?>";
	return "<?php echo $dPconfig['currency_symbol']; ?>";
}
function checkAmount(id){
	var radios = document.getElementsByName("currency");
	if(isNaN(parseFloat(document.getElementById("h"+id).value.replace(/ /g,''))))
		document.getElementById(id).innerHTML = 0;
	var val = parseFloat(document.getElementById("h"+id).value.replace(/ /g,''))
	var tax = document.getElementsByName("tax");
	if(tax[1].checked)
		val *= <?php echo str_replace(",",".",1+$budget->Tax/100); ?>;
	var radios = document.getElementsByName("currency");
	if(radios[2].checked)
		document.getElementById(id).innerHTML = addThousandsSep((parseFloat(val)/1000000).toFixed(2),' ')+getCurrency();
	else if(radios[1].checked)
		document.getElementById(id).innerHTML = addThousandsSep((parseFloat(val)/1000).toFixed(2),' ')+getCurrency();
	else document.getElementById(id).innerHTML = addThousandsSep(parseFloat(val).toFixed(2),' ')+getCurrency();
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
	var inv = parseFloat(document.getElementById("equipment_investment").innerHTML.replace(/ /g,''));
	inv += parseFloat(document.getElementById("intangible_investment").innerHTML.replace(/ /g,''));
	inv += parseFloat(document.getElementById("service_investment").innerHTML.replace(/ /g,''));
	document.getElementById("total_investment").innerHTML = addThousandsSep(inv.toFixed(2)+getCurrency(),' ');
	var ope = parseFloat(document.getElementById("equipment_operation").innerHTML.replace(/ /g,''));
	ope += parseFloat(document.getElementById("intangible_operation").innerHTML.replace(/ /g,''));
	ope += parseFloat(document.getElementById("service_operation").innerHTML.replace(/ /g,''));
	document.getElementById("total_operation").innerHTML = addThousandsSep(ope.toFixed(2),' ')+getCurrency();
	var sum = inv+ope;
	document.getElementById("total").innerHTML = addThousandsSep(sum.toFixed(2),' ')+getCurrency();
}

// Launch first update
updateBudget();
</script>

