<?php /* MACRO_PROJECTS view.php, v 0.1.0 2012/05/30 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Author:		Henri SAULME, <henri.saulme@gmail.com>
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

GLOBAL $macroproject_id;

$macroproject_id = intval(dPgetParam($_GET, 'macroproject_id', 0));
$q = new DBQuery;

//check permissions for this record
$canAccess = getPermission($m, 'access', $macroproject_id);
$canRead = getPermission($m, 'view', $macroproject_id);
$canEdit = getPermission($m, 'edit', $macroproject_id);

$canAuthorTask = getPermission('tasks', 'add');

//Check if the proect is viewable.
if (!($canRead)) {
	$AppUI->redirect('m=public&a=access_denied');
}

//retrieve any state parameters
if (isset($_GET['tab'])) {
	$AppUI->setState('ProjVwTab', $_GET['tab']);
}
$tab = $AppUI->getState('ProjVwTab') !== NULL ? $AppUI->getState('ProjVwTab') : 0;

//check if this record has dependencies to prevent deletion
$msg = '';
$obj = new CMacroProject();
$canDelete = $obj->canDelete($msg, $macroproject_id);

//get critical tasks (criteria: task_end_date)
$criticalTasks = ($macroproject_id > 0) ? $obj->getCriticalTasks($macroproject_id) : NULL;

//get macroprojectPriority from sysvals
$macroprojectPriority = dPgetSysVal('macroprojectPriority');
$macroprojectPriorityColor = dPgetSysVal('macroprojectPriorityColor');

$working_hours = ($dPconfig['daily_working_hours']?$dPconfig['daily_working_hours']:8);

//check that macroproject has tasks; otherwise run seperate query
$q->addTable('tasks', 'tsk');
$q->addQuery("COUNT(distinct tsk.task_id) AS total_tasks");
$q->addWhere(makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
$hasTasks = $q->loadResult();
$q->clear();

//load the record data
//GJB: Note that we have to special case duration type 24 
//and this refers to the hours in a day, NOT 24 hours
$q->addTable('macroprojects', 'mp');
$q->addJoin('companies', 'com', 'com.company_id = macroproject_company');
$q->addJoin('companies', 'com_internal', 'com_internal.company_id = macroproject_company_internal');
$q->addJoin('users', 'u', 'user_id = macroproject_owner');
$q->addJoin('contacts', 'con', 'contact_id = user_contact');
if ($hasTasks) {
    $q->addJoin('tasks', 't1', makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
	$q->addQuery('com.company_name AS company_name, com_internal.company_name' 
				 . ' AS company_name_internal' 
				 . ", CONCAT_WS(', ',contact_last_name,contact_first_name) user_name" 
				 . ',mp.macroproject_color_identifier, mp.macroproject_name'
				 . ',mp.macroproject_short_name, mp.macroproject_percent_complete'
				 . ',mp.macroproject_start_date, mp.macroproject_end_date'
				 . ',mp.macroproject_target_budget, mp.macroproject_url'
				 . ',mp.macroproject_demo_url, mp.macroproject_description'
				 . ',mp.macroproject_status, mp.macroproject_priority'
				 . ',mp.macroproject_type');
    //$q->addWhere('t1.task_id = t1.task_parent'); ==>on veut le calculer en prenant en compte toutes les tâches
} else {
	$q->addQuery('com.company_name AS company_name, com_internal.company_name' 
				 . ' AS company_name_internal' 
				 . ", CONCAT_WS(' ',contact_first_name,contact_last_name) user_name, mp.*, "
                 .'(0.0) AS macroproject_percent_complete');
}
$q->addWhere('macroproject_id = ' . $macroproject_id);
$q->addGroup('macroproject_id');
$sql = $q->prepare();
$q->clear();

$obj = null;
if (!db_loadObject($sql, $obj)) {
	$AppUI->setMsg('macroproject');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect();
} else {
	$AppUI->savePlace();
}


//worked hours
//now milestones are summed up, too, for consistence with the tasks duration sum
//the sums have to be rounded to prevent the sum form having many (unwanted) decimals because of the mysql floating point issue
//more info on http://www.mysql.com/doc/en/Problems_with_float.html

if ($hasTasks) {
    $q->addTable('task_log');
    $q->addTable('tasks');
    $q->addQuery('ROUND(SUM(task_log_hours),2)');
    $q->addWhere('task_log_task = task_id AND '.makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
    $sql = $q->prepare();
    $q->clear();
    $worked_hours = db_loadResult($sql);
    $worked_hours = rtrim($worked_hours, '.');
    
    //total hours
    //same milestone comment as above, also applies to dynamic tasks
    $q->addTable('tasks');
    $q->addQuery('ROUND(SUM(task_duration),2)');
    $q->addWhere('task_duration_type = 24 AND task_dynamic != 1 AND '.makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
    $sql = $q->prepare();
    $q->clear();
    $days = db_loadResult($sql);
    
    $q->addTable('tasks');
    $q->addQuery('ROUND(SUM(task_duration),2)');
    $q->addWhere('task_duration_type = 1 AND task_dynamic != 1 AND '.makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
    $sql = $q->prepare();
    $q->clear();
    $hours = db_loadResult($sql);
    $total_hours = $days * $dPconfig['daily_working_hours'] + $hours;
    
    $total_macroproject_hours = 0;
    
    $q->addTable('tasks', 't');
    $q->addQuery('ROUND(SUM(t.task_duration*u.perc_assignment/100),2)');
    $q->addJoin('user_tasks', 'u', 't.task_id = u.task_id');
    $q->addWhere('t.task_duration_type = 24 AND t.task_dynamic != 1 AND '.makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
    $total_macroproject_days_sql = $q->prepare();
    $q->clear();
    
    $q->addTable('tasks', 't');
    $q->addQuery('ROUND(SUM(t.task_duration*u.perc_assignment/100),2)');
    $q->addJoin('user_tasks', 'u', 't.task_id = u.task_id');
    $q->addWhere('t.task_duration_type = 1 AND t.task_dynamic != 1 AND '.makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
    $total_macroproject_hours_sql = $q->prepare();
    $q->clear();
    
    $total_macroproject_hours = (db_loadResult($total_macroproject_days_sql) 
							* $dPconfig['daily_working_hours'] 
							+ db_loadResult($total_macroproject_hours_sql));
    //due to the round above, we don't want to print decimals unless they really exist
    //$total_macroproject_hours = rtrim($total_macroproject_hours, '0');
}
else { //no tasks in macroproject so "fake" macroproject data
    $worked_hours = $total_hours = $total_macroproject_hours = 0.00;
}
//get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

//create Date objects from the datetime fields
$start_date = (intval($obj->macroproject_start_date) ? new CDate($obj->macroproject_start_date) : null);
$end_date = (intval($obj->macroproject_end_date) ? new CDate($obj->macroproject_end_date) : null);
$actual_end_date = (intval($criticalTasks[0]['task_end_date']) 
                    ? new CDate($criticalTasks[0]['task_end_date']) : null);
$style = ((($actual_end_date > $end_date) && !empty($end_date)) 
          ? 'style="color:red; font-weight:bold"' : '');

//setup the title block
$titleBlock = new CTitleBlock('View Dashboard', 'applet3-48.png', $m, "$m.$a");

//patch 2.12.04 text to search entry box
if (isset($_POST['searchtext'])) {
	$AppUI->setState('searchtext', $_POST['searchtext']);
}

$search_text = (($AppUI->getState('searchtext')) ? $AppUI->getState('searchtext'):'');
$titleBlock->addCell($AppUI->_('Search') . ':');
$titleBlock->addCell(('<input type="text" class="text" SIZE="10" name="searchtext"' 
                      . ' onchange="javascript:document.searchfilter.submit();" value="' . $search_text . '"' 
                      . 'title="' . $AppUI->_('Search in name and description fields') 
                      . '"/><!--<input type="submit" class="button" value=">" title="' 
                      . $AppUI->_('Search in name and description fields') . '"/>-->'), '',
					 ('<form action="?m=macroprojects&amp;a=view&amp;macroproject_id=' . $macroproject_id 
                      . '" method="post" id="searchfilter">'), '</form>');

if ($canEdit) {
	if ($canAuthorTask) {
	$titleBlock->addCell();
	$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('add/del project') 
	                      . '" />'), '', ('<form action="?m=macroprojects&amp;a=addproject&amp;macroproject_id=' 
	                                    . $macroproject_id . '" method="post">'), '</form>');
	}
	$titleBlock->addCell();
	$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new event') 
	                      . '" />'), '', ('<form action="?m=calendar&amp;a=addedit&amp;event_macroproject=' 
	                                    . $macroproject_id . '" method="post">'), '</form>');

	$titleBlock->addCell();
	$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new file') 
	                      . '" />'), '', ('<form action="?m=files&amp;a=addedit&amp;macroproject_id=' 
	                                    . $macroproject_id . '" method="post">'), '</form>');
}

$titleBlock->addBreadcrumb('?m=macroprojects', 'Dashboard');
$titleBlock->addBreadcrumb('', $obj->macroproject_name, 'applet3-48.png');
if ($canEdit) {
	//$titleBlock->addCrumb(('?m=macroprojects&amp;a=addedit&amp;macroproject_id=' . $macroproject_id), 'edit this macroproject');
	if ($canDelete) {
		$titleBlock->addCrumbDelete('delete macroproject', $canDelete, $msg);
	}
	$titleBlock->addCrumb('?m=tasks&amp;a=organize&amp;macroproject_id=' . $macroproject_id, 'organize tasks');
}
$titleBlock->addCrumb('?m=macroprojects&amp;a=reports&amp;macroproject_id=' . $macroproject_id, 'reports');
$titleBlock->show();
updateMacroProjectPercentComplete($macroproject_id);
?>
<script type="text/javascript" language="javascript">
<?php
//security improvement:
//some javascript functions may not appear on client side in case of user not having write permissions
//else users would be able to arbitrarily run 'bad' functions
//if ($canEdit) {  //All users can see Details of a macroproject
?>
function delIt() {
	if (confirm("<?php 
echo ($AppUI->_('doDelete', UI_OUTPUT_JS) . ' ' . $AppUI->_('macroproject', UI_OUTPUT_JS) . '?'); ?>")) {
		document.frmDelete.submit();
	}
}

function doDetails() { 
	obj=document.getElementById('contentTr'); 
	col=document.getElementById('displayImg'); 
	if (obj.style.display=="none") { 
		obj.style.display="block"; 
		col.src="images/icons/collapse.gif"; 
	} else { 
		obj.style.display="none"; 
		col.src="images/icons/expand.gif"; 
	}
} 
<?php //} ?>
</script>

<form name="frmDelete" action="./index.php?m=macroprojects" method="post">
	<input type="hidden" name="dosql" value="do_macroproject_aed" />
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="macroproject_id" value="<?php echo $macroproject_id; ?>" />
</form>
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std" >
<tr>
	<td style="border: outset #d1d1cd 1px;background-color:#<?php 
echo $obj->macroproject_color_identifier; ?>" colspan="2">
	<a href="?m=macroprojects&amp;a=addedit&amp;macroproject_id=<?php echo $macroproject_id; ?>"><img src="images/icons/pencil.gif"/></a><a href="javascript:doDetails()">
		<img border="0" align="middle" id="displayImg" alt="" src="images/icons/expand.gif" />
	</a>
	<?php
echo ('<span style="color:' . bestColor($obj->macroproject_color_identifier) . '; font-weight:bold">' 
      . $obj->macroproject_name . '</span>');
	?>
	</td>
</tr>

<tr id="contentTr" style="display:none;">
	<td width="50%" valign="top">
		<strong><?php echo $AppUI->_('Details'); ?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Company'); ?>:</td>
            <td class="hilite" width="100%"><?php 
echo (((getPermission('companies', 'view', $obj->macroproject_company)) 
       ? ('<a href="?m=companies&amp;a=view&amp;company_id=' . $obj->macroproject_company . '">') : '') 
      . htmlspecialchars($obj->company_name, ENT_QUOTES) 
	  . ((getPermission('companies', 'view', $obj->macroproject_company)) ? '</a>' : '')); 
?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Internal Company'); ?>:</td>
            <td class="hilite" width="100%"><?php 
echo (((getPermission('companies', 'view', $obj->macroproject_company_internal)) 
       ? ('<a href="?m=companies&amp;a=view&amp;company_id=' . $obj->macroproject_company_internal . '">') : '') 
      . htmlspecialchars($obj->company_name_internal, ENT_QUOTES) 
	  . ((getPermission('companies', 'view', $obj->macroproject_company_internal)) 
         ? '</a>' : '')); 
?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Short Name'); ?>:</td>
			<td class="hilite"><?php 
echo htmlspecialchars(@$obj->macroproject_short_name, ENT_QUOTES); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Start Date'); ?>:</td>
			<td class="hilite"><?php echo $start_date ? $start_date->format($df) : '-'; ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Target End Date'); ?>:</td>
			<td class="hilite"><?php echo $end_date ? $end_date->format($df) : '-'; ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Actual End Date'); ?>:</td>
			<td class="hilite"><?php 
if ($macroproject_id > 0) {
	echo (($actual_end_date) 
	      ? ('<a href="?m=tasks&amp;a=view&amp;task_id=' . $criticalTasks[0]['task_id'] . '">' 
	         . '<span '. $style.'>'.$actual_end_date->format($df).'</span></a>') 
	      : '-');
} else {
	echo $AppUI->_('Dynamically calculated');
} 

?>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Target Budget'); ?>:</td>
			<td class="hilite"><?php echo $dPconfig['currency_symbol'] ?><?php 
echo @$obj->macroproject_target_budget; ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('MacroProject Owner'); ?>:</td>
			<td class="hilite"><?php echo $obj->user_name; ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('URL'); ?>:</td>
			<td class="hilite"><a href="<?php echo @$obj->macroproject_url; ?>" target="_new"><?php 
echo @$obj->macroproject_url; ?></A></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Staging URL'); ?>:</td>
			<td class="hilite"><a href="<?php echo @$obj->macroproject_demo_url; ?>" target="_new"><?php 
echo @$obj->macroproject_demo_url; ?></a></td>
		</tr>
		<tr>
			<td colspan="2">
			<?php
				/*require_once($AppUI->getSystemClass('CustomFields'));
				$custom_fields = New CustomFields($m, $a, $obj->macroproject_id, "view");
				$custom_fields->printHTML();*/
			?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
			<strong><?php echo $AppUI->_('Description'); ?></strong><br />
			<table cellspacing="0" cellpadding="2" border="0" width="100%">
			<tr>
				<td class="hilite">
					<?php echo str_replace(chr(10), "<br>", $obj->macroproject_description); ?>
				</td>
			</tr>
			</table>
			</td>
		</tr>
		</table>
	</td>
	<td width="50%" rowspan="9" valign="top">
		<strong><?php echo $AppUI->_('Summary'); ?></strong><br />
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Status'); ?>:</td>
			<td class="hilite" width="100%"><?php 
echo $AppUI->_($mpstatus[$obj->macroproject_status]); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Priority'); ?>:</td>
			<td class="hilite" width="100%" style="background-color:<?php 
echo $macroprojectPriorityColor[$obj->macroproject_priority]?>"><?php 
echo $AppUI->_($macroprojectPriority[$obj->macroproject_priority]); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Type'); ?>:</td>
			<td class="hilite" width="100%"><?php 
echo $AppUI->_($mptype[$obj->macroproject_type]); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Progress'); ?>:</td>
			<td class="hilite" width="100%"><?php 
printf('%.1f%%', $obj->macroproject_percent_complete); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Worked Hours'); ?>:</td>
			<td class="hilite" width="100%"><?php echo $worked_hours ?></td>
		</tr>	
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Scheduled Hours'); ?>:</td>
			<td class="hilite" width="100%"><?php echo $total_hours ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('MacroProject Hours'); ?>:</td>
			<td class="hilite" width="100%"><?php echo $total_macroproject_hours ?></td>
		</tr>				
		<?php
$q->addTable('departments', 'a');
$q->addTable('macroproject_departments', 'b');
$q->addQuery('a.dept_id, a.dept_name, a.dept_phone');
$q->addWhere('a.dept_id = b.department_id AND b.macroproject_id = ' . $macroproject_id);
$depts = $q->loadHashList('dept_id');
$q->clear();
if (count($depts) > 0) {
		?>
		<tr>
			<td><strong><?php echo $AppUI->_('Departments'); ?></strong></td>
		</tr>
		<tr>
			<td colspan='3' class="hilite">
				<?php
	foreach ($depts as $dept_id => $dept_info) {
		echo ('<div>' . $dept_info['dept_name']);
		if ($dept_info['dept_phone'] != '') {
			echo ('(' . $dept_info['dept_phone'] . ')');
		}
		echo '</div>';
	}
?>
			</td>
		</tr>
		<?php
}

$q->addTable('contacts', 'a');
$q->addTable('macroproject_contacts', 'b');
$q->addJoin('departments', 'c', 'a.contact_department = c.dept_id', 'left outer');
$q->addQuery('a.contact_id, a.contact_first_name, a.contact_last_name, ' 
             . 'a.contact_email, a.contact_phone, c.dept_name');
$q->addWhere('a.contact_id = b.contact_id AND b.macroproject_id = ' . $macroproject_id 
             . ' AND (contact_owner = ' . $AppUI->user_id . ' OR contact_private=0)');
$contacts = $q->loadHashList('contact_id');
$q->clear();
if (count($contacts) > 0) {
?>
		<tr>
			<td><strong><?php echo $AppUI->_('Contacts'); ?></strong></td>
		</tr>
		<tr>
			<td colspan="3" class="hilite">
				<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
				<tr>
					<th><?php echo ($AppUI->_('Name')); ?></th>
					<th><?php echo ($AppUI->_('Email')); ?></th>
					<th><?php echo ($AppUI->_('Phone')); ?></th>
					<th><?php echo ($AppUI->_('Department')); ?></th>
				</tr>
<?php
	foreach ($contacts as $contact_id => $contact_data) {
		$canEdit = getPermission('contacts', 'edit', $contact_id);
?>
				<tr>
					<td class='hilite'><?php 
		echo ((($canEdit) ? ('<a href="index.php?m=contacts&amp;a=view&amp;contact_id=' 
							 . $contact_id . '">') :'') 
		      . ($contact_data['contact_first_name'] . ' ' . $contact_data['contact_last_name']) 
		      . (($canEdit) ? '</a>' : ''));
?>
					</td>
					<td class="hilite">
						<a href="mailto:<?php echo $contact_data['contact_email']; ?>">
						<?php echo $contact_data['contact_email']; ?>
						</a>
					</td>
					<td class="hilite"><?php echo $contact_data['contact_phone']; ?></td>
					<td class="hilite"><?php echo $contact_data['dept_name']; ?></td>
				</tr>
<?php 
	}
?>
				</table>
			</td>
		</tr>
<?php
}
?>
		</table>
	</td>
</tr>
</table>

<?php
$tabBox = new CTabBox(('?m=macroprojects&amp;a=view&amp;macroproject_id=' . $macroproject_id), '', $tab);
$query_string = ('?m=macroprojects&amp;a=view&amp;macroproject_id=' . $macroproject_id);
//tabbed information boxes
//Note that we now control these based upon module requirements.
$canAccessTask = getPermission('tasks', 'access');
$canAccessTaskLog = getPermission('task_log', 'access');
$showEditCheckbox = false;

if ($canAccessTask) {
	$tabBox->add(DP_BASE_DIR.'/modules/tasks/tasks', 'Tasks');
	$tabBox->add(DP_BASE_DIR.'/modules/tasks/tasks', 'Tasks (Inactive)');
}
/*if (getPermission('forums', 'access')) { // Carefull : forums are useless and bug !
	$tabBox->add(DP_BASE_DIR.'/modules/macroprojects/vw_forums', 'Forums');
}*/
/*
if (getPermission('files', 'access')) {
	$tabBox->add(DP_BASE_DIR.'/modules/macroprojects/vw_files', 'Files');
}
*/
if ($canAccessTask) {
	$tabBox->add(DP_BASE_DIR.'/modules/tasks/viewgantt', 'Gantt Chart');
}

$tabBox->loadExtras($m);
$f = 'all';
$min_view = true;

$tabBox->show();
?>
