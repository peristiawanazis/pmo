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
global $dPconfig, $m;

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
require_once $AppUI->getModuleClass('contacts');

// Config
$company_prefix = 'c_';

// Set today
$today = new CDate();

// Get the params
$q = new DBQuery;
$q->addTable('users', 'u');
$q->addQuery('DISTINCT(user_contact)');
$q->addWhere('user_id = '.$AppUI->user_id);

$contact	= new CContact();
$contact->load($q->loadResult());
$company 	= dPgetParam($_POST, 'company_id', 0);//($contact->contact_department != 0) ? $contact->contact_department : $company_prefix.$contact->contact_company );	// Company/Department filter			Default: current user company

$currency 	= dPgetParam($_POST, 'currency', 0);										// 0: *1, 1: *1k, 2: *1M  				Default: *1
$display 	= dPgetParam($_POST, 'display', 0);											// 0: details, 1: subTotal, 2: total	Default: details
$tax 		= dPgetParam($_POST, 'tax', 1);												// 0: without, 1: with	 				Default: without
$hideNull = dPgetParam($_POST, 'hideNull', 2);									// 0:all, 1:Only valued projects and tasks	2:valued projects 3:Only projects with null budget
$project_status = dPgetParam($_POST, 'project_status', 0);      //
$project_type = dPgetParam($_POST, 'project_type', 0);

$default[0] = $today->getYear();														// Default array for $years
$years 		= dPgetParam($_POST, 'years', $default);									// (array)Years to display 				Default: this year
$toExpand	= dPgetParam($_POST, 'expandedList', null);									// List of <tr> to re-expand			Default: none(null)

// Extract company_id and department_id from company param
$company_id = substr(strrchr($company, $company_prefix), strlen($company_prefix));
if ($company_id == '') {
	$company_id 	= 	0;
	$department_id 	= 	$company;
	$department		= 	''.$department_id;
} else
	$department_id 	= 	0;

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

<form id="mainFrm" name="mainFrm" action="?m=finances" method="post">
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
				<td align="right" nowrap><?php echo $AppUI->_("Projects").': '; ?></td>
				<td align="left" nowrap>
					<select class="text" name="hideNull" id="hideNull" onChange="javascript:submitWithExpandList(this);">
						<option value="0" <?php echo ($hideNull==0)?'selected="selected"':''; ?>><?php echo $AppUI->_("All"); ?></option>
						<option value="1" <?php echo ($hideNull==1)?'selected="selected"':''; ?>><?php echo $AppUI->_("Only valued projects and tasks"); ?></option>
						<option value="2" <?php echo ($hideNull==2)?'selected="selected"':''; ?>><?php echo $AppUI->_("Only valued projects"); ?></option>
						<option value="3" <?php echo ($hideNull==3)?'selected="selected"':''; ?>><?php echo $AppUI->_("Only projects with null budget"); ?></option>
					</select>
				</td>
				<td align="right" nowrap><?php echo $AppUI->_("Amounts").': '; ?></td>	
				<td align="left" nowrap><label for="tax0"><input type="radio" name="tax" id="tax0" value="0" onChange="javascript:submitWithExpandList(this);" <?php if($tax == "0") echo 'checked'; ?> /><?php echo $AppUI->_("without tax"); ?></label></td>
				<td align="left" nowrap><label for="tax1"><input type="radio" name="tax" id="tax1" value="1" onChange="javascript:submitWithExpandList(this);" <?php if($tax == "1") echo 'checked'; ?> /><?php echo $AppUI->_("with tax"); ?></label></td>
			</tr>
			<tr>
				<td align="left" nowrap><label for="display0"><input type="radio" name="display" id="display0" value="0" onClick="javascript:$('.subTotal').hide();$('.total').hide();$('.budget').show()" /><?php echo $AppUI->_('Detail'); ?></label></td>
				<td align="left" nowrap><label for="display1"><input type="radio" name="display" id="display1" value="1" onClick="javascript:$('.budget').hide();$('.total').hide();$('.subTotal').show()" /><?php echo $AppUI->_('Sub Total'); ?></label></td>
				<td align="left" nowrap><label for="display2"><input type="radio" name="display" id="display2" value="2" onClick="javascript:$('.budget').hide();$('.subTotal').hide();$('.total').show()" /><?php echo $AppUI->_('Total'); ?></label></td>
				<td align="right" nowrap><?php echo $AppUI->_('Company').'/'.$AppUI->_('Division').':'; ?></td>
				<td align="left" colspan="4" nowrap>
					<?php
					$obj_company 	= new CCompany();
					$companies 		= $obj_company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
					if (count($companies) == 0) { 
						$companies = array(0);
					}
					
					// get the list of permitted companies
					$companies = arrayMerge(array('0' => $AppUI->_('All')), $companies);
					
					//get list of all departments, filtered by the list of permitted companies.
					$q = new DBQuery();
					$q->addTable('companies', 'c');
					$q->addQuery('c.company_id, c.company_name, dep.*');
					$q->addJoin('departments', 'dep', 'c.company_id = dep.dept_company');
					$q->addOrder('c.company_name, dep.dept_parent, dep.dept_name');
					$obj_company->setAllowedSQL($AppUI->user_id, $q);
					$rows = $q->loadList();
					
					//display the select list
					$cBuffer = '<select name="company_id" onChange="javascript:submitWithExpandList(this);" class="text">';
					$cBuffer .= ('<option value="0" style="font-weight:bold;">' . $AppUI->_('All') 
								 . '</option>'."\n");
					$cBuffer .= ('<option value="-1" style="font-weight:bold;" '
								 .(($company == -1) ? 'selected="selected"' : '')
								 . '>' . $AppUI->_('None') 
								 . '</option>'."\n");
					$comp = '';
					foreach ($rows as $row) {
						if ($row['dept_parent'] == 0) {
							if ($comp != $row['company_id']) {
								$cBuffer .= ('<option value="' . $AppUI->___($company_prefix . $row['company_id']) 
											 . '" style="font-weight:bold;"' 
											 . (($company.'' == $AppUI->___($company_prefix . $row['company_id'])) ? 'selected="selected"' : '') 
											 . '>' . $AppUI->___($row['company_name']) . '</option>' . "\n");
								$comp = $row['company_id'];
							}
							
							if ($row['dept_parent'] != null) {
								showchilddept($row);
								findchilddept($rows, $row['dept_id']);
							}
						}
					}
					$cBuffer .= '</select>';
					echo $cBuffer; ?>
				</td>
			</tr>
			<?php 
				$ptypeTemp = dPgetSysVal('ProjectType');
				$pstatusTemp = dPgetSysVal('ProjectStatus');
				$ptype[0] = $AppUI->_('All');
				$pstatus[0] =$AppUI->_('All');
				$ptype = array_merge($ptype, $ptypeTemp);
				$pstatus = array_merge($pstatus,$pstatusTemp);
			?>
			<tr>
				
				<td align="right" colspan="6">
					<?php echo $AppUI->_('Project Type').' : ';?>
					<?php echo arraySelect($ptype, 'project_type', 'id="project_type" size="1" class="text" onChange="javascript:submitWithExpandList(this);"', $project_type, true);?> 
				</td>
				<td align="right" colspan="2">
					<?php echo $AppUI->_('Project Status').' : ';?> 
				</td>
				<td align="left" colspan="3">
					<?php echo arraySelect($pstatus, 'project_status', 'id="project_status" size="1" class="text" onChange="javascript:submitWithExpandList(this);"', $project_status, true); ?>		
				</td>
				<td align="left" colspan="1">
					<?php $yearsGET = '';
					foreach($years as $y){
						$yearsGET .= $y.'y';
					}?>
					<a href="?m=finances&amp;a=export_excel&amp;projectStatus=<?php echo $project_status;?>&amp;projectType=<?php echo $project_type;?>&amp;departementId=<?php echo $departement_id;?>&amp;companyId=<?php echo $company_id;?>&amp;years=<?php echo $yearsGET;?>&amp;tax=<?php echo $tax;?>&amp;hideNull=<?php echo $hideNull;?>&amp;suppressHeaders=1">
					<?php echo $AppUI->_('Excel export');?></a>
				</td>
			</tr>
		</tbody>
	</table>
<div style="text-align: center; padding: 5px; display: none;" id="errorDiv"><?php echo $AppUI->_("Performance of Internet Explorer may cause a very significant slowdown in the case of a large number of projects to load."); ?></div>
<div style="text-align: center; padding: 5px;">
	<input type="button" id="saveButton" value="<?php echo $AppUI->_('Save Changes');?>" onclick="javascript:submitWithExpandList(this);" />
</div>
<!-- Main table -->
<table class="treeTable" cellspacing="0" cellpadding="0" border="1">
	<tbody>
	<?php
		$total_ei = 0;
		$total_ii = 0;
		$total_si = 0;
		$total_eo = 0;
		$total_io = 0;
		$total_so = 0;
		
		// Load all projects
		$projects = loadProjects($company_id, $department_id, $project_type, $project_status);

		foreach($projects as $project){
			if(getPermission('projects', 'view', $project->project_id)) { // Check permission
				$tasks = loadTasks($project->project_id, $years); // Load only corresponding tasks
				if ($tasks != null) { // Check if project have tasks before display it
					echo '<tr id="p_'.$project->project_id.'" rel="p_'.$project->project_id.'">';
					echo '<td class="tdDesc" style="background-color:#'.$project->project_color_identifier.';">'
							.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" />'
							.'<a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=projects&a=view&project_id='.$project->project_id.'" style="color:' . bestColor($project->project_color_identifier) . ';">'
							.$project->project_name.'</a>'
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
					
					foreach($tasks as $task) {
						// Load the budget of each tasks
						$budget = new CBudget();
						$budget->loadFromTask($task->task_id);
						if($hideNull !=1 || !$budget->isNull() || $task->task_dynamic == 1){
							
							$budget->display_tax	= $tax;
							$parent = null;
							if ($task->task_parent == $task->task_id) {
								$parent = 'child-of-p_'.$project->project_id ;
							} else {
								$parent = 'child-of-p_'.$project->project_id.'_t_'.$task->task_parent ;
							}
							
							echo '<tr id="p_'.$project->project_id.'_t_'.$task->task_id.'" class="'.$parent.'">';
							$add = '';
							if($task->task_dynamic == 1)
								$add = 'style="font-weight:bold; font-size:80%;"';
							echo '<td class="tdDesc">';
							if ($task->task_dynamic == 1)
								echo '<img src="./modules/finances/images/dyna.gif" width="10px" height:"10px" /><a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$task->task_id.'">'.$task->task_name.'</a></td>';
							else
								echo '<a target="_blank" title="'.$AppUI->_('Open in new tab').'"  href="index.php?m=tasks&a=view&task_id='.$task->task_id.'">'.$task->task_name.' ('.$budget->Tax.$AppUI->_("%").')</a></td>';

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
	<thead>
		<tr>
			<th class="tdDesc" rowspan="2">
				<?php echo $AppUI->_('Project / Task(Tax)');?></td>
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
		<tr class="tdTotal">
			<td class="tdDesc"><b>TOTAL</b></td>
			<td class="tdContentTask budget" rel="_ei"><b><?php echo number_format($total_ei*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_ii"><b><?php echo number_format($total_ii*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_si"><b><?php echo number_format($total_si*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_eo"><b><?php echo number_format($total_eo*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_io"><b><?php echo number_format($total_io*$mult,2,'.',' ').$symbol; ?></b></td>
			<td class="tdContentTask budget" rel="_so"><b><?php echo number_format($total_so*$mult,2,'.',' ').$symbol; ?></b></td>
			<td colspan=3 style="display:none;" class="tdContentTask subTotal" rel="_ti"><b><?php echo number_format($total_ti*$mult,2,'.',' ').$symbol; ?></b></td>
			<td colspan=3 style="display:none;" class="tdContentTask subTotal" rel="_to"><b><?php echo number_format($total_to*$mult,2,'.',' ').$symbol; ?></b></td>
			<td colspan=6 style="display:none;" class="tdContentTask total" rel="_tt"><b><?php echo number_format($total_tt*$mult,2,'.',' ').$symbol; ?></b></td>
		</tr>
	</thead>
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
	$(this).html('<?php echo $dPconfig['currency_symbol']; ?><input name="'+$(this).parent().attr('id')+$(this).attr('rel')+'" style="width: 85%;" type="text" value="'+ $(this).attr('val')+'"/>');
	$(this).children().focus();
}


<?php 
// Set the precedent display
switch($display) {
	case "0": echo '$("#display0").click(); '; break;
	case "1": echo '$("#display1").click(); '; break;
	case "2": echo '$("#display2").click(); '; break; 
	default:  echo '$("#display0").click(); ';
}
?>
// Block Internet Explorer
if (window.navigator.appName == 'Microsoft Internet Explorer')
{
	$('#errorDiv').show();
}
// Run the js scripts to complete the table values
completeTd();
//$(".treeTable").contents().each(function(i){ alert($(this).text())});
// Allow dynamic edition and display correct icons 
$("tr:not(.parent) > .editable:not(.edit)").live("click", tdClick).live("mouseenter", tdEnter).live('mouseleave', tdLeave);
$("td.tdDesc > a").hover(function() {$(this).append($("<img src='./images/icons/posticon.gif' />"));}, function() {$(this).find("img:last").remove();});

<?php 
// Remove lines according filter
switch($hideNull) {
	case "0": break;
	case "1": echo '$("td.tdContentProject[rel=\'_tt\']").each(function(i) { if (!(parseFloat($(this).text()) > 0)) $(this).parent().remove();}); '; break;
	case "2": echo '$("td.tdContentProject[rel=\'_tt\']").each(function(i) { if (!(parseFloat($(this).text()) > 0)) $(this).parent().remove();}); '; break;
	case "3": echo '$("td.tdContentProject[rel=\'_tt\']").each(function(i) { 
							if (parseFloat($(this).text()) > 0) {
								$("tr[class^=\'child-of-"+$(this).parent().attr(\'id\')+"\']").each(function (j){
									$(this).remove();
								});
								$(this).parent().remove();
							}
						}); $(".tdTotal").remove();'; break;
	default:  break;
}
?>

</script>