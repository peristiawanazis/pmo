<?php  /* FINANCES index.php, v 0.1.0 2012/07/20 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
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
global $AppUI, $macroprojects, $currentTabId, $dPconfig, $m;

// Check permissions for this module
if (!getPermission($m, 'view')) 
	$AppUI->redirect("m=public&a=access_denied");
	


$AppUI->savePlace();

// Include the necessary classes
require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('departments');
require_once $AppUI->getModuleClass('finances');
require_once $AppUI->getModuleClass('macroprojects');

// Config
$company_prefix = 'c_';

// Set today
$today = new CDate();

// Get the params
$currency 	= dPgetParam($_POST, 'currency', 0);										// 0: *1, 1: *1k, 2: *1M  				Default: *1
$display 	= dPgetParam($_POST, 'display', 0);											// 0: details, 1: subTotal, 2: total	Default: details
$tax 		= dPgetParam($_POST, 'tax', 0);												// 0: without, 1: with	 				Default: without
$toExpand	= dPgetParam($_POST, 'expandedList', null);									// List of <tr> to re-expand			Default: none(null)
$default[0] = $today->getYear();														// Default array for $years
$years 		= dPgetParam($_POST, 'years', $default);									// (array)Years to display 				Default: this year
$macroproject_status = dPgetParam($_POST, 'macroproject_status', 0);
$macroproject_type = dPgetParam($_POST, 'macroproject_type', 0);
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
			initialState: "collapsed"	 	// can be changed for "expanded"
		});
		<?php 
		// Re-expand <tr> wich were expanded on last page
		if(isset($_POST['expandedList'])) {
			$expand = explode(',',$toExpand);
			foreach ($expand as $id) {
				if ($id != '')
					echo '$("tr#'.$id.'").expand();';
			}
		} ?>
	});
</script>

<form id="mainFrm" name="mainFrm" action="?m=macroprojects&tab=<?php echo $currentTabId; ?>" method="post">
	<input type="hidden" name="edit" value="0" />
	<table class="tbl" cellspacing="0" cellpadding="4" border="0" width ="100%">
		<tbody>
			<tr>
				<td align="right" rowspan="2" nowrap="nowrap"><?php echo $AppUI->_('Years').': ';?></td>
				<td align="left" rowspan="2" nowrap="nowrap">
					<div class="multiselect">
						<label><input type="checkbox" name="years[]" value="1" <?php if(in_array("1",$years)) echo "checked"; ?>/><?php echo $AppUI->_('All');?></label>
						<?php for($i = -2; $i < 5; $i++) {
							echo '<label><input type="checkbox" name="years[]" value="'.($i+$today->getYear()).'" ';
							if(in_array($i+$today->getYear(),$years)) echo 'checked ';
							echo '/>'.($i+$today->getYear()).'</label>';
						} ?>
					</div>
				</td>
				<td align="left" rowspan="2" nowrap><input type="button" class="button" value="<?php echo $AppUI->_("Submit"); ?>" onclick="javascript:submitWithExpandList(this);" /></td>
				<td align="right" rowspan="2"  nowrap><?php echo $AppUI->_("Display").': '; ?></td>
				<td align="left" nowrap><label for="currency0"><input type="radio" name="currency" id="currency0" value="0" onChange="javascript:submitWithExpandList(this);" <?php if($currency == "0") echo 'checked'; ?> /><?php echo $dPconfig['currency_symbol']; ?></label></td>
				<td align="left" nowrap><label for="currency1"><input type="radio" name="currency" id="currency1" value="1" onChange="javascript:submitWithExpandList(this);" <?php if($currency == "1") echo 'checked'; ?> /><?php echo "k".$dPconfig['currency_symbol']; ?></label></td>
				<td align="left" nowrap><label for="currency2"><input type="radio" name="currency" id="currency2" value="2" onChange="javascript:submitWithExpandList(this);" <?php if($currency == "2") echo 'checked'; ?> /><?php echo "M".$dPconfig['currency_symbol']; ?></label></td>
				<td align="right"rowspan="2" nowrap><?php echo $AppUI->_("Amounts").': '; ?></td>	
				<td align="left" rowspan="2" nowrap><label for="tax0"><input type="radio" name="tax" id="tax0" value="0" onChange="javascript:submitWithExpandList(this);" <?php if($tax == "0") echo 'checked'; ?> /><?php echo $AppUI->_("without tax"); ?></label></td>
				<td align="left"rowspan="2" nowrap><label for="tax1"><input type="radio" name="tax" id="tax1" value="1" onChange="javascript:submitWithExpandList(this);" <?php if($tax == "1") echo 'checked'; ?> /><?php echo $AppUI->_("with tax"); ?></label></td>
				<td align="right" colspan="5"></td>
			</tr>
			<tr>
				<td align="left" nowrap><label for="display0"><input type="radio" name="display" id="display0" value="0" onClick="javascript:$('.subTotal').hide();$('.total').hide();$('.budget').show()" /><?php echo $AppUI->_('Detail'); ?></label></td>
				<td align="left" nowrap><label for="display1"><input type="radio" name="display" id="display1" value="1" onClick="javascript:$('.budget').hide();$('.total').hide();$('.subTotal').show()" /><?php echo $AppUI->_('Sub Total'); ?></label></td>
				<td align="left" nowrap><label for="display2"><input type="radio" name="display" id="display2" value="2" onClick="javascript:$('.budget').hide();$('.subTotal').hide();$('.total').show()" /><?php echo $AppUI->_('Total'); ?></label></td>
				<td align="right" colspan="5"></td>
			</tr>
			<?php 
				$mptypeTemp = dPgetSysVal('MacroProjectType');
				$mpstatusTemp = dPgetSysVal('MacroProjectStatus');
				$mptype[0] = $AppUI->_('All');
				$mpstatus[0] =$AppUI->_('All');
				$mptype = array_merge($mptype, $mptypeTemp);
				$mpstatus = array_merge($mpstatus,$mpstatusTemp);
			?>
			<tr>
				<td align="right" colspan="6">
					<?php echo $AppUI->_('MacroProject Type').' : ';?>
					<?php echo arraySelect($mptype, 'macroproject_type', 'id="macroproject_type" size="1" class="text" onChange="javascript:submitWithExpandList(this);"', $macroproject_type, true);?> 
				</td>
				<td align="right" colspan="2">
					<?php echo $AppUI->_('MacroProject Status').' : ';?> 
				</td>
				<td align="left" colspan="3">
					<?php echo arraySelect($mpstatus, 'macroproject_status', 'id="macroproject_status" size="1" class="text" onChange="javascript:submitWithExpandList(this);"', $macroproject_status, true); ?>		
				</td>
				<td align="left" colspan="1">
					<?php $yearsGET = '';
					foreach($years as $y){
						$yearsGET .= $y.'y';
					}?>
					<a href="?m=finances&amp;a=export_excel&amp;macroproject=1&amp;macroprojectStatus=<?php echo $macroproject_status;?>&amp;macroprojectType=<?php echo $macroproject_type;?>&amp;years=<?php echo $yearsGET;?>&amp;tax=<?php echo $tax;?>&amp;suppressHeaders=1">
					<?php echo $AppUI->_('Excel export');?></a>
				</td>
			</tr>
		</tbody>
	</table>
<div style="text-align: center; padding: 5px;">
	<input type="button" id="saveButton" value="<?php echo $AppUI->_('Save Changes');?>" onclick="javascript:submitWithExpandList(this);" />
</div>
<!-- Main table -->
<table class="treeTable" cellspacing="0" cellpadding="0" border="1">
	<tbody>
	<?php

		$macroprojects = loadMacroProjects($macroproject_type, $macroproject_status);
		
		foreach($macroprojects as $mp){
			$tasks = loadMPTasks($mp->macroproject_id, $years);
			if ($tasks != null) { // Check if project have tasks before display it
				echo '<tr id="mp_'.$mp->macroproject_id.'" rel="mp_'.$mp->macroproject_id.'">';
				echo '<td class="tdDesc" style="background-color:#'.$mp->macroproject_color_identifier.';">'
						.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" />'
						.'<b><a href="index.php?m=macroprojects&a=view&macroproject_id='.$mp->macroproject_id.'" style="color:' . bestColor($mp->macroproject_color_identifier) . ';">'
						.$mp->macroproject_name.'</a></b>'
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
							echo '<tr id="mp_'.$mp->macroproject_id.'_p_'.$project->project_id.'" class="child-of-mp_'.$mp->macroproject_id.'" rel="mp_'.$mp->macroproject_id.'_p_'.$project->project_id.'">';
							echo '<td class="tdDesc" style="background-color:#'.$project->project_color_identifier.';">'
									.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" />'
									.'<a href="index.php?m=projects&a=view&project_id='.$project->project_id.'" style="color:' . bestColor($project->project_color_identifier) . ';">'
									.$project->project_name.'</a>'
								.'</td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$mp->macroproject_id.'_ei" rel="_ei"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$mp->macroproject_id.'_ii" rel="_ii"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$mp->macroproject_id.'_si" rel="_si"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$mp->macroproject_id.'_eo" rel="_eo"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$mp->macroproject_id.'_io" rel="_io"></td>'
								.'<td class="tdContentProject budget todo child-of-mp_'.$mp->macroproject_id.'_so" rel="_so"></td>'
								.'<td colspan=3 style="display:none;" class="tdContentProject subTotal todo child-of-mp_'.$mp->macroproject_id.'_ti" rel="_ti"></td>'
								.'<td colspan=3 style="display:none;" class="tdContentProject subTotal todo child-of-mp_'.$mp->macroproject_id.'_to" rel="_to"></td>'
								.'<td colspan=6 style="display:none;" class="tdContentProject total todo child-of-mp_'.$mp->macroproject_id.'_tt" rel="_tt"></td>'
								."<tr/>\n";
							
							$actualProject = $task->task_project;
						}
					
						// Load the budget of each tasks
						$budget = new CBudget();
						$budget->loadFromTask($task->task_id);
						$budget->display_tax	= $tax;
						$parent = null;
						if ($task->task_parent == $task->task_id) {
							$parent = 'child-of-mp_'.$mp->macroproject_id.'_p_'.$project->project_id ;
						} else {
							$parent = 'child-of-mp_'.$mp->macroproject_id.'_p_'.$project->project_id.'_t_'.$task->task_parent ;
						}
						
						echo '<tr id="mp_'.$mp->macroproject_id.'_p_'.$project->project_id.'_t_'.$task->task_id.'" class="'.$parent.'">';
						$add = '';
						if($task->task_dynamic == 1)
							$add = 'style="font-weight:bold; font-size:80%;"';
						echo '<td class="tdDesc">';
						
						$start_date = new CDate($task->task_start_date);
						$end_date = new CDate($task->task_end_date);
						if ($task->task_dynamic == 1)
							echo '<img src="./modules/finances/images/dyna.gif" width="10px" height:"10px" /><a title="'.$AppUI->_("Start Date").': '.$start_date->format("%d/%m/%Y").' - '.$AppUI->_("End Date").': '.$end_date->format("%d/%m/%Y").'" href="index.php?m=tasks&a=view&task_id='.$task->task_id.'">'.$task->task_name.'</a></td>';
						else
							echo '<a title="'.$AppUI->_("Start Date").': '.$start_date->format("%d/%m/%Y").' - '.$AppUI->_("End Date").': '.$end_date->format("%d/%m/%Y").'" href="index.php?m=tasks&a=view&task_id='.$task->task_id.'">'.$task->task_name.' ('.$budget->Tax.$AppUI->_("%").')</a></td>';

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
	
	?>
	</tbody>
	<thead>
		<tr>
			<th class="tdDesc" rowspan="2">
				<?php echo $AppUI->_('MacroProject / Project / Task(Tax)');?></td>
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
//$(".treeTable").contents().each(function(i){ alert($(this).text())});
// Allow dynamic edition and display correct icons 
$("tr:not(.parent) > .editable:not(.edit)").live("click", tdClick).live("mouseenter", tdEnter).live('mouseleave', tdLeave);
</script>