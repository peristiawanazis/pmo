<?php
/* RESOURCE_M index.php, v 0.1.2 2012/07/19 */
/*
* Copyright (c) 2011-2012 Region Poitou-Charentes (France)
*
* Description:	Index page of the Resource Management module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
* version 0.1.1
* 	Fix bug with submit button in Edit Assign.
*   Fix bug with displaying cancel cross in Edit Assign.
* version 0.1.2
*   Keep the expanded lines at page changement.
*	Allow edit dates and adapt assign.
*	Optimize the getAssignement function.
* version 0.1.2.1
*   New algorithm of sorting tasks.
* version 0.1.2.2
*	Fix the 1 hour tasks bug.
* version 0.2.0
*	Fix multi-level dynamic tasks
*	Re-Expand lines at any time
*	Change the way to indicate to JS script what it need to compute
*	Integrate the project color identifier
*	Integrate filter icons
*	Integrate edit icons
*	Change collapse and expand icons in order to feet with the ones of dotProject core
*	Change filter form method to POST
*	Merge various form
*/
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Check permissions for this module
if (!getPermission($m, 'view')) 
	$AppUI->redirect("m=public&a=access_denied");
	
// Get the global var
global $dPconfig, $cBuffer, $department;

$AppUI->savePlace();

// Inclusion the necessary classes
require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('contacts');
require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('departments');
require_once $AppUI->getModuleClass('resource_m');

// Config
$company_prefix = 'c_';


// Set today and inOneMonth dates
$ctoday = new CDate();
$today 	= $ctoday->format(FMT_TIMESTAMP_DATE);
$cinOneMonth = new CDate();
$cinOneMonth->addMonths(1);
$inOneMonth = $cinOneMonth->format(FMT_TIMESTAMP_DATE);

// Get the param
$project_id = dPgetParam($_POST, 'project_id', 0); 								// Project filter 					Default: all(0)
$company 	= dPgetParam($_POST, 'company_id', 0);								// Company/Department filter		Default: all(0)
$contact_id = dPgetParam($_POST, 'contact_id', getContactId($AppUI->user_id));	// Contact filter					Default: user
$aff_style 	= dPgetParam($_POST, 'aff_style', 1);								// 0 : % , 1 : daily assigned hours Default: daily assigned hours
$dyna 		= dPgetParam($_POST, 'dyna', 0);									// 0 : show dynamic tasks, 1 : hide	Default: show dynamic tasks
$start_date = dPgetParam($_POST, 'start_date', $today);							// Start Date filter 				Default: today
$end_date 	= dPgetParam($_POST, 'end_date', $inOneMonth); 						// End Date filter					Default: today + 1 month
$toExpand	= dPgetParam($_POST, 'expandedList', null);							// List of <tr> to re-expand		Default: none(null)


// Extract company_id and department_id from company param
$company_id = substr(strrchr($company, $company_prefix), strlen($company_prefix));
if ($company_id == '') {
	$company_id 	= 	0;
	$department_id 	= 	dPgetParam($_POST, 'company_id', 0);
	$department		= 	''.$department_id;
} else
	$department_id 	= 	0;

// Count how many days, weeks, months, year are in range date
$cstart_date	= 	new CDate($start_date);
$cend_date 		= 	new CDate($end_date);
$dayCount 		= 	dayCount($cstart_date, $cend_date);
$weekCount 		= 	weekCount($cstart_date, $cend_date);
$monthCount 	= 	monthCount($cstart_date, $cend_date);
$yearCount 		= 	yearCount($cstart_date, $cend_date);

// Determine if we have to display days, weeks, months.
//$showHours = ($dayCount<=1) ? 1 : 0;
$showDays = ($dayCount<=32) ? 1 : 0;
$showWeeks = ($dayCount<32*7) ? 1 : 0;
$showMonths = ($dayCount<32*30) ? 1 : 0;
$show = $showDays+$showWeeks+$showMonths;

// Calculate the scale
$scale = ($aff_style) ? $dPconfig['daily_working_hours'] : 100;

// Calculate the number of columns needed
$colCount = ($showHours) ? $dPconfig['daily_working_hours'] : (($showDays) ? $dayCount : (($showWeeks) ? $weekCount : (($showMonths) ? $monthCount : $yearCount)));

// If user have request an assignment modification, process if he have correct permissions else redirect.
if (isset($_POST['aTId']) && $_POST['aTId'] != "..."){
	if (getPermission('tasks', 'edit', $_POST['aTId'])) {
		switch ($_POST['editType']) {
			case 'ad' : editAssign($_POST['aUId'],$_POST['aTId'],$_POST['aPercent'],$_POST['aAffStyle'],$_POST['aAdaptD']); break;
			case 'aa' : editDur($_POST['aUId'],$_POST['aTId'],$_POST['aSDate'],$_POST['aEDate'],$_POST['aAdaptA']);break;
			default : break;
		}
	} else
		$AppUI->redirect("m=public&a=access_denied");
}

// Setup the title block
$titleBlock = new CTitleBlock($AppUI->_('Resource Management'), 'helpdesk.png', $m, "$m.$a");
$titleBlock->show();
?>
<link href="./modules/resource_m/css/jquery.treeTable.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="./modules/resource_m/js/jquery.js"></script>
<script type="text/javascript" src="./modules/resource_m/js/jquery.ui.js"></script>
<script type="text/javascript" src="./modules/resource_m/js/jquery.treeTable.js"></script>
<script type="text/javascript" src="./modules/resource_m/js/resource_m.js"></script>
<script type="text/javascript">
	var timer; 	// Will be used to delay the display of additionnal informations relative to user/project/task.
	var start;	// Will be used to know if user have changed the start date during edition.
	var end;	// Will be used to know if user have changed the end date during edition.
	$(document).ready(function() {
		$(".treeTable").treeTable({
			initialState: "collapsed"  	// can be changed for "expanded"
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

<form id="mainFrm" name="mainFrm" action="?m=resource_m" method="post">
	<table class="tbl" cellspacing="0" cellpadding="4" border="0" width ="100%">
		<tbody>
			<tr>
				<?php 	// Set the date for previous and next date arrow.
				$psdate = new CDate($start_date); $psdate->addMonths(-1);
				$pedate = new CDate($end_date);	$pedate->addMonths(-1);
				$nsdate = new CDate($start_date); $nsdate->addMonths(1);
				$nedate = new CDate($end_date); $nedate->addMonths(1); ?>
				<td align="right" nowrap="nowrap">
					<?php echo generateRangeLink($psdate->format("%Y%m%d"), $pedate->format("%Y%m%d"), 
												'<img src="./images/prev.gif" width="16" height="16" alt="'.$AppUI->_('previous').'" border="0">')
								.'  '.$AppUI->_('From').':';?>
				</td>
				<td align="left" nowrap="nowrap">
					<input type="hidden" id="start_date" name="start_date" value="<?php echo $start_date; ?>" />
					<input type="text" class="text" id="show_start_date" value="<?php echo $cstart_date->format("%d/%m/%Y"); ?>" size="12" disabled="disabled" />
					<a href="javascript:popCalendar(document.mainFrm.start_date)">
						<img src="./images/calendar.gif" width="24" height="12" alt="" border="0">
					</a>
				</td>
				<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To').':';?></td>
				<td align="left" nowrap="nowrap">
					<input type="hidden" id="end_date" name="end_date" value="<?php echo $end_date; ?>" />
					<input type="text" class="text" id="show_end_date" value="<?php echo $cend_date->format("%d/%m/%Y"); ?>" size="12" disabled="disabled" />
					<a href="javascript:popCalendar(document.mainFrm.end_date)">
						<img src="./images/calendar.gif" width="24" height="12" alt="" border="0">
					</a>
					<?php echo '  '.generateRangeLink($nsdate->format("%Y%m%d"), $nedate->format("%Y%m%d"),
													'<img src="./images/next.gif" width="16" height="16" alt="'.$AppUI->_('next').'" border="0">');?>
				</td>
				<td align="left" nowrap><input type="button" onclick="javascript:submitWithExpandList(this);" class="button" id="submitButton" value="<?php echo $AppUI->_("Submit"); ?>" /></td>
				<td align="right" nowrap><?php echo $AppUI->_("Display").':'; ?></td>
				<td align="left" nowrap><?php echo $AppUI->_("%"); ?><input type="radio" name="aff_style" value=0 onChange="javascript:submitWithExpandList(this);" <?php if(!$aff_style) echo 'checked'; ?>/></td>
				<td align="left" nowrap><?php echo $AppUI->_("Daily assigned hours"); ?><input type="radio" name="aff_style" value=1 onChange="javascript:submitWithExpandList(this);" <?php if($aff_style) echo 'checked'; ?>/></td>
				<td align="right" nowrap><?php echo $AppUI->_("Dynamic tasks").':'; ?></td>
				<td align="left" nowrap><?php echo $AppUI->_("Show"); ?><input type="radio" name="dyna" value=0 onChange="javascript:submitWithExpandList(this);" <?php if(!$dyna) echo 'checked'; ?>/></td>
				<td align="left" nowrap><?php echo $AppUI->_("Hide"); ?><input type="radio" name="dyna" value=1 onChange="javascript:submitWithExpandList(this);" <?php if($dyna) echo 'checked'; ?>/></td>
			</tr>
			<tr> 
				<td align="left" colspan=5 nowrap>
					<?php
					$q = new DBQuery();
					$q->addTable('projects', 'p');
					$q->addQuery('project_id');
					$q->addQuery('project_name AS label');
					$q->addOrder('project_name');
					$projectRows = array(0 => $AppUI->_('All Projects', UI_OUTPUT_RAW)) + $q->loadHashList();
					echo arraySelect($projectRows, 'project_id', 'onChange="javascript:submitWithExpandList(this);" class="text"', $project_id); ?>
				</td>
				<td align="right" nowrap><?php echo $AppUI->_('Company').'/'.$AppUI->_('Division').':'; ?></td>
				<td align="left" colspan=2 nowrap>
					<?php
					$obj_company 	= new CCompany();
					$companies 		= $obj_company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
					if (count($companies) == 0) { 
						$companies = array(0);
					}
					
					// get the list of permitted companies
					$companies = arrayMerge(array('0' => $AppUI->_('All')), $companies);
					
					//get list of all departments, filtered by the list of permitted companies.
					$q->clear();
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
				<td align="left" colspan=3 nowrap>
					<?php
					$q->addTable('contacts', 'c');
					$q->addQuery('contact_id');
					$q->addQuery("CONCAT(contact_last_name, ' ', contact_first_name) AS label");
					$q->addOrder('contact_last_name, contact_first_name');
					$userRows = array(0 => $AppUI->_('All Users', UI_OUTPUT_RAW)) + $q->loadHashList();
					echo arraySelect($userRows, 'contact_id','onChange="javascript:submitWithExpandList(this);" class="text"', $contact_id); ?>
				</td>				
			</tr>
		</tbody>
	</table>

<!-- Main table -->
<table class="treeTable" cellspacing="0" cellpadding="0" border="0">
	<thead>
		<tr>
			<th class="thHead">
				<table class="treeHead" cellspacing="0" cellpadding="0" border="0">
					<tbody>
						<tr>
							<td class="treeTitle"><?php echo $AppUI->_('Resource Management');?></td>
						</tr>
					</tbody>
				</table>
			</th>
			<th class="thCal" colspan="<?php echo $colCount; ?>">
				<table class="treeCal" cellspacing="0" cellpadding="0" border="0">
					<tbody>
						<?php //Display the Calendar header:
						echo '<tr>';
						$ccurrent 		= new CDate($start_date);
						$colspan 		= 0;
						$currentYear 	= $ccurrent->getYear();
						for($i = 0; $i < $colCount; $i++) {
							if($currentYear == $ccurrent->getYear())
								$colspan++;
							else {
								echo '<td class="treeYear" colspan='.$colspan.'>'
									.generateRangeLink($currentYear.'0101', $currentYear.'1231', 
														$currentYear)
									.'</td>';
								$colspan 		= 1;
								$currentYear	= $ccurrent->getYear();
							}
							nextDate($ccurrent, $show);
						}
						echo '<td class="treeYear" colspan='.$colspan.'>'
							.generateRangeLink($currentYear.'0101', $currentYear.'1231', 
												$currentYear)
							.'</td>';
							
						echo '</tr>';
						if($showMonths) {
							echo '<tr>';
							$ccurrent 		= new CDate($start_date);
							$colspan 		= 0;
							$currentMonth	= $ccurrent->getMonthName(true);
							for($i = 0; $i < $colCount; $i++) {
								if($currentMonth == $ccurrent->getMonthName(true))
									$colspan++;
								else {
									prevDate($ccurrent, $show);
									echo '<td class="treeMonth" colspan='.$colspan.'>'
										.generateRangeLink($ccurrent->format('%Y%m').'01', Date_Calc::endOfMonth($ccurrent->format('%m'),
																												$ccurrent->format('%Y'),
																												'%Y%m%d'), 
															$AppUI->_($currentMonth))
										.'</td>';
									nextDate($ccurrent, $show);
									$colspan 		= 1;
									$currentMonth	= $ccurrent->getMonthName(true);
								}
								nextDate($ccurrent, $show);
							}
							prevDate($ccurrent, $show);
							echo '<td class="treeMonth" colspan='.$colspan.'>'
								.generateRangeLink($ccurrent->format('%Y%m').'01', Date_Calc::endOfMonth($ccurrent->format('%m'), 
																										$ccurrent->format('%Y'),
																										'%Y%m%d'), 
													$AppUI->_($currentMonth))
								.'</td>';
							echo '</tr>';
						}
						
						if($showWeeks) {
							echo '<tr>';
							$ccurrent 		= new CDate($start_date);
							$colspan 		= 0;
							$currentWeek	= $ccurrent->format('s%U');
							for($i = 0; $i < $colCount; $i++) {
								if($currentWeek == $ccurrent->format('s%U'))
									$colspan++;
								else {	
									prevDate($ccurrent, $show);
									echo '<td class="treeWeek" colspan='.$colspan.'>'
										.generateRangeLink(getStartOfWeek($ccurrent->format('%Y%m%d')), getEndOfWeek($ccurrent->format('%Y%m%d')), 
																		$currentWeek)
										.'</td>';
									nextDate($ccurrent, $show);
									$colspan 		= 1;
									$currentWeek	= $ccurrent->format('s%U');
								}
								nextDate($ccurrent, $show);
							}
							prevDate($ccurrent, $show);
							echo '<td class="treeWeek" colspan='.$colspan.'>'
								.generateRangeLink(getStartOfWeek($ccurrent->format('%Y%m%d')), getEndOfWeek($ccurrent->format('%Y%m%d')), 
													$currentWeek)
								.'</td>';
							echo '</tr>';
						}
						
						if($showDays) {
							echo '<tr>';
							$ccurrent 	= new CDate($start_date);
							$colspan 	= 0;
							$currentDay	= $ccurrent->getDay();
							for($i = 0; $i < $colCount; $i++) {
								if($currentDay == $ccurrent->getDay())
									$colspan++;
								else {
									prevDate($ccurrent, $show);
									echo '<td class="treeDay" colspan='.$colspan.'>'
										.generateRangeLink($ccurrent->format('%Y%m%d'), $ccurrent->format('%Y%m%d'), 
															$currentDay)
										.'</td>';
									nextDate($ccurrent, $show);
									$colspan 	= 1;
									$currentDay	= $ccurrent->getDay();
								}
								nextDate($ccurrent, $show);
							}
							prevDate($ccurrent, $show);
							echo '<td class="treeDay" colspan='.$colspan.'>'
								.generateRangeLink($ccurrent->format('%Y%m%d'), $ccurrent->format('%Y%m%d'), 
													$currentDay)
								.'</td>';
						}
						
						if($showHours) {
							echo '<tr>';
							$ccurrent 		= new CDate($start_date);
							$currentHour	= $ccurrent->getHour();
							for($i = 0; $i < $colCount; $i++) {
								echo '<td class="treeHour">'.$currentHour.'</td>';
								nextDate($ccurrent, $show);
								$currentHour	= $ccurrent->getHour();
							}
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</th>
		</tr>
	</thead>
	<tbody>
	<?php
	// Load the users to display
	$users = loadUsersIncludindFilters($contact_id, $company_id, $department_id, $project_id);
	foreach ($users as $user) { // Display a line by user
		if(getPermission('contacts', 'view', $user->contact_id)) {
			$dep	= $user->getDepartmentDetails();
			$fun 	= 'displayUserDetails(event,\''.addslashes($user->getCompanyName()).'\',\''.addslashes($dep['dept_name']).'\');';
			echo '<tr id="u_'.$user->contact_id.'" rel="u_'.$user->contact_id.'">'
					.'<td class="tdRessource" style="font-weight:bold;" onMouseOver="timer='.$fun.'" onMouseOut="hideUserDetails(timer)">'
					.generateFilterLink('contact', $user->contact_id, $user->contact_last_name.' '.$user->contact_first_name)
					.'</td>'
					.generateTdUsers($colCount)
				.'</tr>';
			
			$tasks 	= loadTasksOf($user->contact_id, $start_date, $end_date); // Load all tasks concerned by this user.
			
			// Load the projects coresponding to those tasks.
			$projects 	= loadProjectsOf($tasks);
			foreach ($projects as $project) {
				if(getPermission('projects', 'view', $project->project_id)) {
					$sdate 		= new CDate($project->project_start_date);
					$edate 		= new CDate($project->project_end_date);
					$owner 		= new CContact();
					$company 	= new CCompany();
					$department = new CDepartment();
					$owner->load(getContactId($project->project_owner));
					$company->load($project->project_company);
					$department->load($project->project_department);
					$fun 		= 'displayProjectDetails(event,\''
														.$owner->contact_first_name.' '.$owner->contact_last_name.'\',\''
														.$sdate->format('%d/%m/%Y').'\',\''
														.$edate->format('%d/%m/%Y').'\',\''
														.$company->company_name.'\',\''
														.$department->department_name.'\');';
					echo '<tr id="u_'.$user->contact_id.'_p_'.$project->project_id.'" class="child-of-u_'.$user->contact_id.'">'
							.'<td class="tdRessource" onMouseOver="timer='.$fun.'" onMouseOut="hideProjectDetails(timer)" style="background-color: #'.$project->project_color_identifier.'; " >'
								.'<img src="./modules/projects/images/applet3-48.png" width="12px" height:"12px" /> '
								.generateFilterLink('project', $project->project_id, $project->project_name,
													bestColor($project->project_color_identifier))
							.'</td>'
							.generateTdProjects($colCount, "child-of-u_".$user->contact_id, $project->project_color_identifier)
						.'</tr>';
					
					//$tasks =  loadTasksFrom($project->project_id,$user->contact_id,$start_date,$end_date);
					foreach ($tasks as $task) {
						if ($task->task_project == $project->project_id  && getPermission('tasks', 'view', $task->task_id)) {
							if ($task->task_project == $project->project_id) {
								$parent = null;
								if ($task->task_parent == $task->task_id) {
									if (!$dyna || ($task->task_dynamic != 1))
										$parent = 'child-of-u_'.$user->contact_id.'_p_'.$project->project_id ;
								} else {
									if (!$dyna)
										$parent = 'child-of-u_'.$user->contact_id.'_p_'.$project->project_id.'_t_'.$task->task_parent ;
									else if($task->task_dynamic != 1)
											$parent = 'child-of-u_'.$user->contact_id.'_p_'.$project->project_id;
								}
								if($parent != null) {
									$canEdit 	= getPermission('tasks', 'edit', $task->task_id);
									$sdate 		= new CDate($task->task_start_date);
									$edate 		= new CDate($task->task_end_date);
									$owner 		= new CContact();
									$owner->load(getContactId($task->task_owner));
									$fun 		= 'displayTaskDetails(event,\''
																	.$owner->contact_first_name.' '.$owner->contact_last_name.'\',\''
																	.$task->task_percent_complete.'%'.'\',\''
																	.$sdate->format('%d/%m/%Y').'\',\''
																	.$edate->format('%d/%m/%Y').'\',\''
																	.($task->task_dynamic!=1 && $canEdit).'\');';
									echo '<tr id="u_'.$user->contact_id.'_p_'.$project->project_id.'_t_'.$task->task_id.'" class="'.$parent.'">';
									$add = '';
									if($task->task_dynamic == 1) {
										$add = 'style="font-weight:bold; font-size:80%;"';
										$over= '';
										$out = '';
									} else if($canEdit) {
										$aPercent = getUserTaskAssign($task->task_id, $user->contact_id)*$scale/100;
										$aPercent = (round($aPercent*4)/4);
										$aPercent = str_replace('.', '', $aPercent);
										$aPercent = str_replace(',', '.', $aPercent);
										$over 	  ='$(this).append($(\'<img style=\\\'float:right;\\\' src=\\\'./images/icons/pencil.gif\\\' />\'));'
													.'$(this).css(\'cursor\', \'pointer\'); ';
										$out      = '$(this).find(\'img:last\').remove();'
													.'$(this).css(\'cursor\', \'default\'); ';
										$add      = 'onClick="displayAssignEdit(event,\''
																		.$user->contact_last_name.' '.$user->contact_first_name.'\',\''
																		.addslashes($task->task_name).'\',\''
																		.addslashes($project->project_name).'\',\''
																		.$task->task_id.'\',\''
																		.getUserId($user->contact_id).'\',\''
																		.$task->task_percent_complete.'%'.'\',\''
																		.$sdate->format('%d/%m/%Y').'\',\''
																		.$edate->format('%d/%m/%Y').'\',\''
																		.$aPercent.'\',timer);"';
									}
									echo '<td class="tdRessource" '.$add.' onMouseOver="javascript:'.$over.'timer='.$fun.'" '
											.'onMouseOut="javascript:'.$out.'hideTaskDetails(timer)">';
									if ($task->task_dynamic == 1 || !$canEdit)
										echo '<img src="./modules/resource_m/images/dyna.gif" width="10px" height:"10px" /> '.$task->task_name.'</td>';
									else
										echo '<a>'.$task->task_name.'</a></td>';
									echo generateTdTasks($colCount, $start_date, 
														$show, $task, $user->contact_id, 
														$aff_style, $parent, $scale, $AppUI->_('Daily assigned hours'), $AppUI->_('%'))
										.'</tr>';
								}
							}
						}
					}
				}
			}
		}
	}
	?>
	</tbody>
</table>
<div id="convert" style="display: none;"></div>
<script type="text/javascript">
// Run the js script to compute the table values
completeTd(<?php echo $scale; ?>, "<?php echo ($aff_style) ? $AppUI->_('Daily assigned hours'): $AppUI->_('%'); ?>");
mergeTd();
</script>

<!-- Additionnal information for users -->
<div id="userDetails" style="position:absolute;top:0px;z-index:1000;display:none;">
	<table border="0" cellpadding="2" cellspacing="0" class="std" width="100%" >
	<tr>
		<td colspan="2" align="center"><?php echo $AppUI->_("Filter by this user"); ?></td>
	</tr>
	<tr>
		<td colspan="2" class="hilite" style="text-decoration: underline;" align="center"><?php echo $AppUI->_("User details").':'; ?></td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Company").':'; ?></td>
		<td class="hilite" id="uCompany" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Department").':'; ?></td>
		<td class="hilite" id="uDepartment" align="left"></td>
	</tr>
	</table>
</div>

<!-- Additionnal information for projects -->
<div id="projectDetails" style="position:absolute;top:0px;z-index:1000;display:none;">
	<table border="0" cellpadding="2" cellspacing="0" class="std" width="100%" >
	<tr>
		<td colspan="4" align="center"><?php echo $AppUI->_("Filter by this project"); ?></td>
	</tr>
	<tr>
		<td colspan="4" class="hilite" style="text-decoration: underline;" align="center"><?php echo $AppUI->_("Project details").':'; ?></td>
	</tr>
	<tr>
		<td align="right" colspan="2" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Owner").':'; ?></td>
		<td colspan="2" class="hilite" id="pOwner" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Start date").':'; ?></td>
		<td class="hilite" id="pSDate" align="left"></td>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("End date").':'; ?></td>
		<td class="hilite" id="pEDate" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Company").':'; ?></td>
		<td class="hilite" id="pCompany" align="left"></td>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Department").':'; ?></td>
		<td class="hilite" id="pDepartment" align="left"></td>
	</tr>
	</table>
</div>

<!-- Additionnal information for tasks -->
<div id="taskDetails" style="position:absolute;top:0px;z-index:1000;display:none;">
	<table border="0" cellpadding="2" cellspacing="0" class="std" width="100%" >
	<tr id="trDyna" style="visibility:hidden;">
		<td colspan="4" align="center"><?php echo $AppUI->_("Edit this task"); ?></td>
	</tr>
	<tr>
		<td colspan="4" class="hilite" style="text-decoration: underline;" align="center"><?php echo $AppUI->_("Task details").':'; ?></td>
	</tr>
	<tr>
		<td align="right" colspan="2" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Owner").':'; ?></td>
		<td colspan="2" class="hilite" id="tOwner" align="left"></td>
	</tr>
	<tr>
		<td align="right" colspan="2" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Percent complete").':'; ?></td>
		<td colspan="2" class="hilite" id="tPercent" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Start date").':'; ?></td>
		<td class="hilite" id="tSDate" align="left"></td>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("End date").':'; ?></td>
		<td class="hilite" id="tEDate" align="left"></td>
	</tr>
	</table>
</div>

<!-- Assignment edition form -->
<div id="assignEdit" style="position:absolute;top:0px;z-index:1000;display:none;">
	<input type="hidden" name="aTId" id="aTId" value="..." />
	<input type="hidden" name="aUId" id="aUId" value="..." />
	<input type="hidden" name="aAffStyle" id="aAffStyle" value="<?php echo $aff_style; ?>" />
	<table border="0" cellpadding="2" cellspacing="0" class="std" width="100%" >
	<tr>
		<td colspan="3">
		<table border="0" cellpadding="2" cellspacing="0" class="tbl" width="100%" >
			<tr>
			<td width="100%" align="center"><?php echo $AppUI->_("Task Edition"); ?></td>
			<td onClick="document.getElementById('assignEdit').style.display='none';"><img src="./modules/resource_m/images/cancel.gif" width="14" height="14" border="0"></td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("User").':'; ?></td>
		<td colspan="2" class="hilite" id="aUser" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Project").':'; ?></td>
		<td colspan="2" class="hilite" id="aProject" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Task").':'; ?></td>
		<td colspan="2" class="hilite" id="aTask" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Percent complete").':'; ?></td>
		<td colspan="2" class="hilite" id="aComplete" align="left"></td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Start date").':'; ?></td>
		<td class="hilite" align="right" valign="top" nowrap>
			<input type="hidden" id="aSDate" name="aSDate" value="" onChange="document.getElementById('aPercent').disabled='disabled';
			document.getElementById('aa').style.display='table-row';"/>
			<input type="text" class="text" id="show_aSDate" name="show_aSDate" value="" size="12" disabled="disabled" />
		</td>
		<td class="hilite" align="left" valign="top" id="aSCal" style="display:table-cell;" nowrap>
			<a href="javascript:popCalendar(document.mainFrm.aSDate)" onClick="switchTypeAA();" onFocus="if (document.getElementById('aSDate').value == start) switchTypeNull();" >
				<img src="./images/calendar.gif" width="24" height="12" alt="" border="0">
			</a>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("End date").':'; ?></td>
		<td class="hilite" align="right" valign="top" nowrap>
			<input type="hidden" id="aEDate" name="aEDate" value="" />
			<input type="text" class="text" id="show_aEDate" name="show_aEDate" value="" size="12" disabled="disabled" />
		</td>
		<td class="hilite" align="left" valign="top" id="aECal" style="display:table-cell;" nowrap>
			<a href="javascript:popCalendar(document.mainFrm.aEDate)" onClick="switchTypeAA();" onFocus="if (document.getElementById('aEDate').value == end) switchTypeNull();" >
				<img src="./images/calendar.gif" width="24" height="12" alt="" border="0">
			</a>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Assignment").':'; ?></td>
		<td class="hilite" align="right" valign="top" nowrap>
			<input type="text" class="text" id="aPercent" name="aPercent" value="" size="7" maxlength="6" onFocus="switchTypeAD();this.select();" onBlur="switchTypeNull();" onChange="this.onblur='';switchTypeAD();" />
		</td>
		<td class="hilite" align="left" valign="top" nowrap><?php echo ($aff_style) ? $AppUI->_("Daily assigned hours") : $AppUI->_("%"); ?></td>
	</tr>
	<tr id="ad" style="display:none;">
		<td class="hilite" align="right" valign="top" nowrap>
			<input type="checkbox" id="aAdaptD" name="aAdaptD"/>
		</td>
		<td align="left" colspan="2" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Adapt the duration of the task as a result of the change"); ?></td>
	</tr>
	<tr id="aa" style="display:none;">
		<td class="hilite" align="right" valign="top" nowrap>
			<input type="checkbox" id="aAdaptA" name="aAdaptA"/>
		</td>
		<td align="left" colspan="2" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Adapt the assignment of the user as a result of the change"); ?></td>
	</tr>
	<tr id="editAssignBtn" >
		<td colspan="3" align="right">
			<input type="hidden" id="editType" name="editType" value ="" />
			<input type="button" onclick="javascript:submitWithExpandList(this);" class="button" value="<?php echo $AppUI->_('Submit'); ?>" />
		</td>
	</tr>
	</table>
</div>
</form>
<?php
if($aff_style)
	echo $AppUI->_("Assignments are rounded to 1/4 hour.");
?>