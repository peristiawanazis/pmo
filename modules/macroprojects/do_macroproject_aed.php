<?php /* MACRO_PROJECTS do_macroproject-aed.php, v 0.1.0 2012/05/30 */
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

$obj = new CMacroProject();
$msg = '';

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}

require_once($AppUI->getSystemClass('CustomFields'));
// convert dates to SQL format first
if ($obj->macroproject_start_date) {
	$date = new CDate($obj->macroproject_start_date);
	$obj->macroproject_start_date = $date->format(FMT_DATETIME_MYSQL);
}
if ($obj->macroproject_end_date) {
	$date = new CDate($obj->macroproject_end_date);
	$date->setTime(23, 59, 59);
	$obj->macroproject_end_date = $date->format(FMT_DATETIME_MYSQL);
}
if ($obj->macroproject_actual_end_date) {
	$date = new CDate($obj->macroproject_actual_end_date);
	$obj->macroproject_actual_end_date = $date->format(FMT_DATETIME_MYSQL);
}

// let's check if there are some assigned departments to macroproject
if (!dPgetParam($_POST, "macroproject_departments", 0)) {
	$obj->macroproject_departments = implode(",", dPgetParam($_POST, "dept_ids", array()));
}

$del = dPgetParam($_POST, 'del', 0);

// prepare (and translate) the module name ready for the suffix
if ($del) {
	$macroproject_id = dPgetParam($_POST, 'macroproject_id', 0);
	$canDelete = $obj->canDelete($msg, $macroproject_id);
	if (!$canDelete) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	}
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		$AppUI->setMsg("MacroProject deleted", UI_MSG_ALERT);
		$AppUI->redirect("m=macroprojects");
	}
} 
else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$isNotNew = @$_POST['macroproject_id'];
		
		/*if ($importTask_macroprojectId = dPgetParam($_POST, 'import_tasks_from', '0')) {
			$scale_macroproject = dPgetParam($_POST, 'scale_macroproject', '0');
			$obj->importTasks($importTask_macroprojectId, $scale_macroproject);
		}
		$AppUI->setMsg($isNotNew ? 'MacroProject updated' : 'MacroProject inserted', UI_MSG_OK, true);
*/
 		$custom_fields = New CustomFields($m, 'addedit', $obj->macroproject_id, 'edit');
 		$custom_fields->bind($_POST);
 		$sql = $custom_fields->store($obj->macroproject_id); // Store Custom Fields


	}
	if($isNotNew) {
		$AppUI->redirect();
	}
	else {
		$AppUI->redirect("m=macroprojects&a=addproject&macroproject_id=".$obj->macroproject_id);
	}
}
?>