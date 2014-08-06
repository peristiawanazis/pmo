<?php  /* FINANCES projects_tab.budget_summary.php, v 0.1.0 2012/07/20 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Generates the Budget Summary tab in dotProject project view
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// Get the global var
global $dPconfig, $m, $macroproject_id;

// Check permissions for this module
if (!getPermission($m, 'view')) 
	$AppUI->redirect("m=public&a=access_denied");

// Set today
$today = new CDate();
$default[0] = $today->getYear();														// Default array for $years
	
// 
$macroproject = new CMacroproject();
$macroproject->load($macroproject_id);

$AppUI->savePlace();

// Inclusion the necessary classes
require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('finances');

// Get the params
$currency 	= dPgetParam($_POST, 'currency', 0);										// 0: *1, 1: *1k, 2: *1M  				Default: *1
$display 	= dPgetParam($_POST, 'display', 0);											// 0: details, 1: subTotal, 2: total	Default: details
$tax 		= dPgetParam($_POST, 'tax', 0);												// 0: without, 1: with	 				Default: without


// Edit the values if necessary
if(dPgetParam($_POST, 'edit', 0))
	foreach($_POST as $vblname => $value) updateValue($vblname, $value, $tax); // Check on $_POST value is made after

?>
<link href="./modules/finances/css/jquery.treeTable.css" rel="stylesheet" type="text/css" />
<link href="./modules/finances/css/finances.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="./modules/finances/js/jquery.js"></script>
<script type="text/javascript" src="./modules/finances/js/jquery.ui.js"></script>
<script type="text/javascript" src="./modules/finances/js/jquery.treeTable.js"></script>
<script type="text/javascript" src="./modules/finances/js/finances.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$(".treeTable").treeTable({
			initialState: "expanded"	 	// can be changed for "collapsed"
		});
	});
</script>

<form id="mainFrm" name="mainFrm" action="#" method="post">
	<input type="hidden" name="edit" value="0" />
	<table class="tbl" cellspacing="0" cellpadding="4" border="0" width ="100%">
		<tbody>
			<tr>
				<td align="right" rowspan="2"  nowrap><?php echo $AppUI->_("Display").': '; ?></td>
				<td align="left" nowrap><label for="currency0"><input type="radio" name="currency" id="currency0" value="0" onChange="javascript:this.form.submit();" <?php if($currency == "0") echo 'checked'; ?> /><?php echo $dPconfig['currency_symbol']; ?></label>
				<label for="currency1"><input type="radio" name="currency" id="currency1" value="1" onChange="javascript:this.form.submit();" <?php if($currency == "1") echo 'checked'; ?> /><?php echo "k".$dPconfig['currency_symbol']; ?></label>
				<label for="currency2"><input type="radio" name="currency" id="currency2" value="2" onChange="javascript:this.form.submit();" <?php if($currency == "2") echo 'checked'; ?> /><?php echo "M".$dPconfig['currency_symbol']; ?></label></td>
			</tr>
			<tr>
				<td align="left" nowrap><label for="display0"><input type="radio" name="display" id="display0" value="0" onClick="javascript:$('.subTotal').hide();$('.total').hide();$('.budget').show()" /><?php echo $AppUI->_('Detail'); ?></label>
				<label for="display1"><input type="radio" name="display" id="display1" value="1" onClick="javascript:$('.budget').hide();$('.total').hide();$('.subTotal').show()" /><?php echo $AppUI->_('Sub Total'); ?></label>
				<label for="display2"><input type="radio" name="display" id="display2" value="2" onClick="javascript:$('.budget').hide();$('.subTotal').hide();$('.total').show()" /><?php echo $AppUI->_('Total'); ?></label></td>
			</tr>
			<tr>
				<td align="right" nowrap><?php echo $AppUI->_("Amounts").': '; ?></td>	
				<td align="left" nowrap><label for="tax0"><input type="radio" name="tax" id="tax0" value="0" onChange="javascript:this.form.submit();" <?php if($tax == "0") echo 'checked'; ?> /><?php echo $AppUI->_("without tax"); ?></label>
				<label for="tax1"><input type="radio" name="tax" id="tax1" value="1" onChange="javascript:this.form.submit();" <?php if($tax == "1") echo 'checked'; ?> /><?php echo $AppUI->_("with tax"); ?></label></td>
			</tr>
		</tbody>
	</table>
<div style="text-align: center; padding: 5px;">
	<input type="button" id="saveButton" value="<?php echo $AppUI->_('Save Changes');?>" onclick="javascript:submitWithExpandList(this);" />
</div>
<!-- Main table -->
<table class="treeTable" cellspacing="0" cellpadding="0" border="1">
	<thead>
		<tr>
			<th class="tdDesc" rowspan="2">
				<?php echo $AppUI->_('Macroproject / Project / Task(Tax)');?></td>
			</th>
			<th colspan="3">
				<?php echo $AppUI->_('INVESTMENT');?></td>
			</th>
			<th colspan="3">
				<?php echo $AppUI->_('OPERATION');?></td>
			</th>
		</tr>
		<tr>
			<th><?php echo $AppUI->_('Equipment');?></th>
			<th><?php echo $AppUI->_('Intangible');?></th>
			<th><?php echo $AppUI->_('Service');?></th>
			<th><?php echo $AppUI->_('Equipment');?></th>
			<th><?php echo $AppUI->_('Intangible');?></th>
			<th><?php echo $AppUI->_('Service');?></th>
		</tr>
	</thead>
	<tbody>
	<?php
		$total_ei = 0;
		$total_ii = 0;
		$total_si = 0;
		$total_eo = 0;
		$total_io = 0;
		$total_so = 0;
		
		if(getPermission('macroprojects', 'view', $macroproject->macroproject_id)) { // Check permission
			$allYears[0] = 1;
			$tasks = loadMPTasks($macroproject->macroproject_id, $allYears);
			if ($tasks != null) { // Check if project have tasks before display it
				echo '<tr id="mp_'.$macroproject->macroproject_id.'" rel="mp_'.$macroproject->macroproject_id.'">';
				echo '<td class="tdDesc" style="background-color:#'.$macroproject->macroproject_color_identifier.';">'
						.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" />'
						.'<b><a href="index.php?m=macroprojects&a=view&macroproject_id='.$macroproject->macroproject_id.'" style="color:' . bestColor($macroproject->macroproject_color_identifier) . ';">'
						.$macroproject->macroproject_name.'</a></b>'
					.'</td>'
					.'<td class="tdContentProject budget todo" rel="_ei"></td>'
					.'<td class="tdContentProject budget todo" rel="_ii"></td>'
					.'<td class="tdContentProject budget todo" rel="_si"></td>'
					.'<td class="tdContentProject budget todo" rel="_eo"></td>'
					.'<td class="tdContentProject budget todo" rel="_io"></td>'
					.'<td class="tdContentProject budget todo" rel="_so"></td>'
					.'<td colspan=3 style="display:none;" class="tdContentProject subTotal todo" rel="_ti"></td>'
					.'<td colspan=3 style="display:none;" class="tdContentProject subTotal todo" rel="_to"></td>'
					.'<td colspan=6 style="display:none;" class="tdContentProject total todo" rel="_tt"></td>'
					."<tr/>\n";
				
				$actualProject = -1;
				$allTask = array();
				foreach($tasks as $task) {
					if($allTask[$task->task_id] == null) {
						$allTask[$task->task_id] = 1;
						if ($actualProject != $task->task_project){
							$project = new CProject();
							$project->load($task->task_project);
							echo '<tr id="mp_'.$macroproject->macroproject_id.'_p_'.$project->project_id.'" class="child-of-mp_'.$macroproject->macroproject_id.'" rel="mp_'.$macroproject->macroproject_id.'_p_'.$project->project_id.'">';
							echo '<td class="tdDesc" style="background-color:#'.$project->project_color_identifier.';">'
									.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" />'
									.'<a href="index.php?m=projects&a=view&project_id='.$project->project_id.'" style="color:' . bestColor($project->project_color_identifier) . ';">'
									.$project->project_name.'</a>'
								.'</td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$macroproject->macroproject_id.'_ei" rel="_ei"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$macroproject->macroproject_id.'_ii" rel="_ii"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$macroproject->macroproject_id.'_si" rel="_si"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$macroproject->macroproject_id.'_eo" rel="_eo"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$macroproject->macroproject_id.'_io" rel="_io"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$macroproject->macroproject_id.'_so" rel="_so"></td>'
								.'<td colspan=3 style="display:none;" class="tdContentProject subTotal todo child-of-mp_'.$macroproject->macroproject_id.'_ti" rel="_ti"></td>'
								.'<td colspan=3 style="display:none;" class="tdContentProject subTotal todo child-of-mp_'.$macroproject->macroproject_id.'_to" rel="_to"></td>'
								.'<td colspan=6 style="display:none;" class="tdContentProject total todo child-of-mp_'.$macroproject->macroproject_id.'_tt" rel="_tt"></td>'
								."<tr/>\n";
							
							$actualProject = $task->task_project;
						}
					
						// Load the budget of each tasks
						$budget = new CBudget();
						$budget->loadFromTask($task->task_id);
						$budget->display_tax	= $tax;
						$parent = null;
						if ($task->task_parent == $task->task_id) {
							$parent = 'child-of-mp_'.$macroproject->macroproject_id.'_p_'.$project->project_id ;
						} else {
							$parent = 'child-of-mp_'.$macroproject->macroproject_id.'_p_'.$project->project_id.'_t_'.$task->task_parent ;
						}
						
						echo '<tr id="mp_'.$macroproject->macroproject_id.'_p_'.$project->project_id.'_t_'.$task->task_id.'" class="'.$parent.'">';
						$add = '';
						if($task->task_dynamic == 1)
							$add = 'style="font-weight:bold; font-size:80%;"';
						echo '<td class="tdDesc">';
						if ($task->task_dynamic == 1)
							echo '<img src="./modules/finances/images/dyna.gif" width="10px" height:"10px" /><a href="index.php?m=tasks&a=view&task_id='.$task->task_id.'">'.$task->task_name.'</a></td>';
						else
							echo '<a href="index.php?m=tasks&a=view&task_id='.$task->task_id.'">'.$task->task_name.' ('.$budget->Tax.$AppUI->_("%").')</a></td>';

						if(countChildren($task->task_id) == 0) {
							// If the task don't have children, we can edit the budget ...
							switch($currency) {
								case 0 : $mult = 1; $symbol = $dPconfig['currency_symbol']; break;
								case 1 : $mult = 0.001; $symbol = 'k'.$dPconfig['currency_symbol']; break;
								case 2 : $mult = 0.000001; $symbol = 'M'.$dPconfig['currency_symbol']; break;
								default :  $mult = 1; $symbol = $dPconfig['currency_symbol'];
							}
							$editable = '';
							if(getPermission('tasks', 'edit', $task->task_id)) $editable = 'editable';
							echo '<td class="tdContentTask '.$editable.' budget '.$parent.'_ei" rel="_ei" val="'.$budget->get_equipment_investment(1,"","").'">'.$budget->get_equipment_investment($mult, $symbol).'</td>'
								.'<td class="tdContentTask '.$editable.' budget '.$parent.'_ii" rel="_ii" val="'.$budget->get_intangible_investment(1,"","").'">'.$budget->get_intangible_investment($mult, $symbol).'</td>'
								.'<td class="tdContentTask '.$editable.' budget '.$parent.'_si" rel="_si" val="'.$budget->get_service_investment(1,"","").'">'.$budget->get_service_investment($mult, $symbol).'</td>'
								.'<td class="tdContentTask '.$editable.' budget '.$parent.'_eo" rel="_eo" val="'.$budget->get_equipment_operation(1,"","").'">'.$budget->get_equipment_operation($mult, $symbol).'</td>'
								.'<td class="tdContentTask '.$editable.' budget '.$parent.'_io" rel="_io" val="'.$budget->get_intangible_operation(1,"","").'">'.$budget->get_intangible_operation($mult, $symbol).'</td>'
								.'<td class="tdContentTask '.$editable.' budget '.$parent.'_so" rel="_so" val="'.$budget->get_service_operation(1,"","").'">'.$budget->get_service_operation($mult, $symbol).'</td>'
								.'<td colspan=3 style="display:none;" class="tdContentTask subTotal '.$parent.'_ti" rel="_ti">'.$budget->get_investment($mult, $symbol).'</td>'
								.'<td colspan=3 style="display:none;" class="tdContentTask subTotal '.$parent.'_to" rel="_to">'.$budget->get_operation($mult, $symbol).'</td>'
								.'<td colspan=6 style="display:none;" class="tdContentTask total '.$parent.'_tt" rel="_tt">'.$budget->get_total($mult, $symbol).'</td>'
								."\n";
							$total_ei += $budget->get_equipment_investment(1,"","");
							$total_ii += $budget->get_intangible_investment(1,"","");
							$total_si += $budget->get_service_investment(1,"","");
							$total_eo += $budget->get_equipment_operation(1,"","");
							$total_io += $budget->get_intangible_operation(1,"","");
							$total_so += $budget->get_service_operation(1,"","");
						} else {
							// ... otherwise it need to be computed (by JS)
							echo '<td class="tdContentTask budget todo '.$parent.'_ei" rel="_ei"></td>'
								.'<td class="tdContentTask budget todo '.$parent.'_ii" rel="_ii"></td>'
								.'<td class="tdContentTask budget todo '.$parent.'_si" rel="_si"></td>'
								.'<td class="tdContentTask budget todo '.$parent.'_eo" rel="_eo"></td>'
								.'<td class="tdContentTask budget todo '.$parent.'_io" rel="_io"></td>'
								.'<td class="tdContentTask budget todo '.$parent.'_so" rel="_so"></td>'
								.'<td colspan=3 style="display:none;" class="tdContentTask subTotal todo '.$parent.'_ti" rel="_ti"></td>'
								.'<td colspan=3 style="display:none;" class="tdContentTask subTotal todo '.$parent.'_to" rel="_to"></td>'
								.'<td colspan=6 style="display:none;" class="tdContentTask total todo '.$parent.'_tt" rel="_tt"></td>'
								."\n";
						}					
						echo '<tr/>';
					}
				}
			}
		}
	$total_ti = ($total_ei+$total_ii+$total_si);
	$total_to = ($total_eo+$total_io+$total_so);
	$total_tt = ($total_ti+$total_to);
	switch($currency) {
		case 0 : $mult = 1; $symbol = $dPconfig['currency_symbol']; break;
		case 1 : $mult = 0.001; $symbol = 'k'.$dPconfig['currency_symbol']; break;
		case 2 : $mult = 0.000001; $symbol = 'M'.$dPconfig['currency_symbol']; break;
		default :  $mult = 1; $symbol = $dPconfig['currency_symbol'];
	}
	?>
	</tbody>
	<tfoot>
		<tr class="tdTotal">
			<td class="tdDesc" rowspan=3><b>TOTAL</b></td>
			<td class="tdContentTask" rel="_ei"><b><?php echo number_format($total_ei*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask" rel="_ii"><b><?php echo number_format($total_ii*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask" rel="_si"><b><?php echo number_format($total_si*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask" rel="_eo"><b><?php echo number_format($total_eo*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask" rel="_io"><b><?php echo number_format($total_io*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask" rel="_so"><b><?php echo number_format($total_so*$mult,2,'.',' ').$symbol; ?></b></td>
		</tr>
		<tr class="tdTotal">
			<td colspan=3 class="tdContentTask" rel="_ti"><b><?php echo number_format($total_ti*$mult,2,'.',' ').$symbol; ?></b></td>
			<td colspan=3 class="tdContentTask" rel="_to"><b><?php echo number_format($total_to*$mult,2,'.',' ').$symbol; ?></b></td>
		</tr>
		<tr class="tdTotal">
			<td colspan=6 class="tdContentTask" rel="_tt"><b><?php echo number_format($total_tt*$mult,2,'.',' ').$symbol; ?></b></td>
		</tr>
	</tfoot>
</table>
</form>
<script type="text/javascript">
// Definition of function that need PHP
function getCurrency(){
	var radios = document.getElementsByName("currency");
	if(radios[2].checked)
		return "M<?php echo $dPconfig['currency_symbol']; ?>";
	if(radios[1].checked)
		return "k<?php echo $dPconfig['currency_symbol']; ?>";
	return "<?php echo $dPconfig['currency_symbol']; ?>";
}

function tdClick(){
	$("#saveButton").show();
	$(this).addClass("edit");
	var name = $(this).parent().attr('id')+$(this).attr('rel');
	name = name.substr(name.indexOf('_p_')+1);
	$(this).html('<?php echo $dPconfig['currency_symbol']; ?><input name="'+name+'" style="width: 85%;" type="text" value="'+ $(this).attr('val')+'"/>');
	$(this).children().focus();
}

// Set the precedent display
<?php 
switch($display) {
	case "0": echo '$("#display0").click(); '; break;
	case "1": echo '$("#display1").click(); '; break;
	case "2": echo '$("#display2").click(); '; break; 
	default:  echo '$("#display0").click(); ';
}
?>

// Run the js scripts to complete the table values
completeTd();

// Display correct icons 
$("tr:not(.parent) > .tdContentTask:not(.edit, .subTotal, .total)").live("click", tdClick).live("mouseenter", tdEnter).live('mouseleave', tdLeave);
</script>