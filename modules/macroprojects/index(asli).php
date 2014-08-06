<?php  /* MACRO_PROJECTS index.php, v 0.1.0 2012/05/30 */
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

global $cBuffer;

$AppUI->savePlace();
$q = new DBQuery();

// load the companies class to retrieved denied companies
require_once ($AppUI->getModuleClass('companies'));

// Let's update macroproject status!
if (isset($_GET['update_macroproject_status']) && isset($_GET['macroproject_status']) 
   && isset($_GET['macroproject_id'])) {
	$macroprojects_id = $_GET['macroproject_id']; // This must be an array
	
	foreach ($macroprojects_id as $macroproject_id) {
		$q->addTable('macroprojects');
		$q->addUpdate('macroproject_status', $_GET['macroproject_status']);
		$q->addWhere('macroproject_id = ' . $macroproject_id);
		$q->exec();
		$q->clear();
	}
	// Insert our closing for the select
	$bufferUser .= '</select>'."\n";
}

// End of macroproject status update
// retrieve any state parameters
if (isset($_GET['tab'])) {
	$AppUI->setState('MacroProjIdxTab', intval(dPgetCleanParam($_GET, 'tab')));
}

$tab = $AppUI->getState('MacroProjIdxTab') !== NULL ? $AppUI->getState('MacroProjIdxTab') : 500;
$currentTabId = $tab;
$active = intval(!$AppUI->getState('MacroProjIdxTab'));

if (isset($_POST['company_id'])) {
	$AppUI->setState('MacroProjIdxCompany', intval($_POST['company_id']));
}
$company_id = (($AppUI->getState('MacroProjIdxCompany') !== NULL) 
               ? $AppUI->getState('MacroProjIdxCompany') 
               : $AppUI->user_company);

$company_prefix = 'company_';

if (isset($_POST['department'])) {
	$AppUI->setState('MacroProjIdxDepartment', $_POST['department']);
	
	//if department is set, ignore the company_id field
	unset($company_id);
}
$department = (($AppUI->getState('MacroProjIdxDepartment') !== NULL) 
               ? $AppUI->getState('MacroProjIdxDepartment') 
               : ($company_prefix . $AppUI->user_company));

//if $department contains the $company_prefix string that it's requesting a company
// and not a department.  So, clear the $department variable, and populate the $company_id variable.
if (!(mb_strpos($department, $company_prefix)===false)) {
	$company_id = mb_substr($department,mb_strlen($company_prefix));
	$AppUI->setState('MacroProjIdxCompany', $company_id);
	unset($department);
}

$valid_ordering = array('macroproject_name', 'user_username', 'my_tasks desc', 'total_tasks desc',
                        'total_tasks', 'my_tasks', 'macroproject_color_identifier', 'company_name', 
                        'macroproject_end_date', 'macroproject_start_date', 'macroproject_actual_end_date', 
                        'task_log_problem DESC,macroproject_priority', 'macroproject_status', 
                        'macroproject_percent_complete');

$orderdir = $AppUI->getState('MacroProjIdxOrderDir') ? $AppUI->getState('MacroProjIdxOrderDir') : 'asc';
if (isset($_GET['macroorderby']) && in_array($_GET['macroorderby'], $valid_ordering)) {
	$orderdir = (($AppUI->getState('MacroProjIdxOrderDir') == 'asc') ? 'desc' : 'asc');
	$AppUI->setState('MacroProjIdxOrderBy', $_GET['macroorderby']);
}
$macroorderby = (($AppUI->getState('MacroProjIdxOrderBy'))
            ? $AppUI->getState('MacroProjIdxOrderBy') : 'macroproject_end_date');
$AppUI->setState('MacroProjIdxOrderDir', $orderdir);

// prepare the users filter
if (isset($_GET['show_owner'])) {
	$AppUI->setState('MacroProjIdxowner', intval($_GET['show_owner']));
}
else if (isset($_POST['show_owner'])) {
	$AppUI->setState('MacroProjIdxowner', intval($_POST['show_owner']));
}
$owner = $AppUI->getState('MacroProjIdxowner') !== NULL ? $AppUI->getState('MacroProjIdxowner') : $AppUI->user_id;

$q->addTable('users', 'u');
$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact');
$q->addQuery('user_id');
$q->addQuery("CONCAT(contact_last_name, ', ', contact_first_name, ' (', user_username, ')')" 
             . ' AS label');
$q->addOrder('contact_last_name, contact_first_name, user_username');
$userRows = array(0 => $AppUI->_('All Users', UI_OUTPUT_RAW)) + $q->loadHashList();
$bufferUser = arraySelect($userRows, 'show_owner', 
                          'class="text" onchange="javascript:document.pickUser.submit()""', $owner);

/* setting this to filter macroproject_list_data function below
 0 = undefined
 3 = active
 5 = completed
 7 = archived

Because these are "magic" numbers, if the values for macroprojectStatus change under 'System Admin', 
they'll need to change here as well (sadly).
*/
if ($tab != 7 && $tab != 8) {
	$macroproject_status = $tab;
} else if ($tab == 0) {
	$macroproject_status = 0;
}
if ($tab == 5 || $tab == 7) {
	$macroproject_active = 0;
}

//for getting permissions for records related to macroprojects
$obj_macroproject = new CMacroProject();
// collect the full (or filtered) macroprojects list data via function in macroprojects.class.php
macroprojects_list_data();

// Get Type 
$mptype =  dPgetSysVal('MacroProjectType');
// Get Status
$mpstatus =  dPgetSysVal('MacroProjectStatus');

// setup the title block
$titleBlock = new CTitleBlock('macroprojects', 'applet3-48.png', $m, ($m . '.' . $a));
$titleBlock->addCell('<a href="?m=macroprojects&amp;show_owner=' . $AppUI->user_id . '">' . $AppUI->_('Owner') . ':' . '</a>');
$titleBlock->addCell(('<form action="?m=macroprojects" method="post" name="pickUser">' . "\n" 
                      . $bufferUser . "\n" . '</form>' . "\n"));
$titleBlock->addCell($AppUI->_('Company') . '/' . $AppUI->_('Division') . ':');
$titleBlock->addCell(('<form action="?m=macroprojects" method="post" name="pickCompany">' . "\n" 
                      . $cBuffer . "\n" .  '</form>' . "\n"));
$titleBlock->addCell();
//if ($canAuthor) {
	$titleBlock->addCell(('<form action="?m=macroprojects&amp;a=addedit" method="post">' . "\n" 
	                      . '<input type="submit" class="button" value="' 
	                      . $AppUI->_('new macroproject') . '" />'. "\n" . '</form>' . "\n"));
//}
$titleBlock->addBreadcrumb('', 'Macroprojects');
$titleBlock->show();

$macroproject_types = dPgetSysVal('MacroProjectStatus');

// count number of macroprojects per macroproject_status
$q->addTable('macroprojects', 'p');
$q->addQuery('p.macroproject_status, COUNT(p.macroproject_id) as count');
$obj_macroproject->setAllowedSQL($AppUI->user_id, $q, null, 'p');
if ($owner > 0) {
	$q->addWhere('p.macroproject_owner = ' . $owner);
}
if (isset($department)) {
	$q->addJoin('macroproject_departments', 'pd', 'pd.macroproject_id = p.macroproject_id');
	if (!$addPwOiD) {
		$q->addWhere('pd.department_id in (' . implode(',',$dept_ids) . ')');
	}
} else if ($company_id &&!$addPwOiD) {
	$q->addWhere('p.macroproject_company = ' . $company_id);
}
$q->addGroup('macroproject_status');
$statuses = $q->loadHashList('macroproject_status');
$q->clear();
$all_macroprojects = 0;
foreach ($statuses as $k => $v) {
	$macroproject_status_tabs[$v['macroproject_status']] = ($AppUI->_($macroproject_types[$v['macroproject_status']]) 
													  . ' (' . $v['count'] . ')');
	//count all macroprojects
	$all_macroprojects += $v['count'];
}

//set file used per macroproject status title
$fixed_status = array('In Progress' => 'vw_idx_active',
					  'Complete' => 'vw_idx_complete',
					  'Archived' => 'vw_idx_archived');

/**
* Now, we will figure out which vw_idx file are available
* for each macroproject status using the $fixed_status array 
*/
$macroproject_status_file = array();
foreach ($macroproject_types as $status_id => $status_title) {
	//if there is no fixed vw_idx file, we will use vw_idx_proposed
	$macroproject_status_file[$status_id] = ((isset($fixed_status[$status_title])) 
										? $fixed_status[$status_title] : 'vw_idx_proposed');
}

// tabbed information boxes
$tabBox = new CTabBox('?m=macroprojects', DP_BASE_DIR . '/modules/macroprojects/', $tab);

$tabBox->add('vw_idx_proposed', $AppUI->_('All') . ' (' . $all_macroprojects . ')' , true,  500);
foreach ($macroproject_types as $psk => $macroproject_status) {
		$tabBox->add($macroproject_status_file[$psk], 
					 (($macroproject_status_tabs[$psk]) ? $macroproject_status_tabs[$psk] : $AppUI->_($macroproject_status)), true, $psk);
}
$min_view = true;
$tabBox->add('viewgantt', 'Gantt');
if(file_exists('./modules/macroprojects/viewfinances.php')) $tabBox->add('viewfinances', 'Finances');
$tabBox->show();
?>
