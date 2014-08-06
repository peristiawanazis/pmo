<?php /* IGANTT igantt.ajax.php v0.1.2 2009/02/10 */
/*
* Copyright (c) 2008 -2009 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	Library of PHP functions called by XAJAX to display a Gantt chart
*			This work includes the use of the XAJAX library that is distributed under the BSD licence
*
* Author:		Pierre-Yves SIMONOT, Euxenis, <pierre-yves.simonot@euxenis.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
* version 0.1.0
* 	Creation
* version 0.1.1
*	test graph date range validity line 118
*	new displayTask function to allow for task edition
*	new updateTask function to process task update from detailed information overlay
* version 01.2.
*	process enhanced scale option
*
*/

if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

function convText( $text ) {
/*
*	Utility function for character conversion
*/
	global $locale_char_set;
	return strtolower( $locale_char_set ) == "utf-8"  ? ($text) : iconv( $locale_char_set, "UTF-8", $text );
}

function drawGraph( $params ) {
/*
*	Generate a full graph according to option params
*		@param	array of options
*				'project_id'	=> project ID
*				'sdate'		=> Graph start date in Unix time
*				'edate'		=> Graph end date in Unix time
*				'workingDays	=> show only working days if true
*/
global $AppUI;
$perms =& $AppUI->acl();

$project_id = intval($params['project_id']);
$edate = intval($params['edate']);
$sdate = intval($params['sdate']);
$width = intval($params['width']);
$working_days = $params['workingDays'] ? true : false;
$enhanced_scale = $params['enhancedScale'] ? true : false;

//include iGantt specific XAJAX response class
include_once ( DP_BASE_DIR."/modules/igantt/igantt.ajax.class.php" );

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');
$tf = $AppUI->getPref('TIMEFORMAT');

// Get project data
include_once ($AppUI->getModuleClass("projects"));
$project = new CProject();
if ( !$project->load($project_id) ) {
	// Traitement d'erreur via xajax
	$response->append( "graph", "innerHTML", "<b>".$AppUI->_("Invalid project ID")."</b><br />");
	return $response;
	}

// pull tasks
$task =& new CTask();
$q = new DBQuery();
$q->clear();
$q->addTable('tasks', 't');
$q->addWhere('t.task_project = '.$project_id);
$q->addQuery('t.task_id, t.task_parent, t.task_name, t.task_start_date, t.task_end_date, t.task_percent_complete, t.task_milestone, t.task_dynamic');
$q->addOrder('t.task_start_date, t.task_end_date');
// get any specifically denied tasks
$task->setAllowedSQL($AppUI->user_id, $q);
$proTasks = $q->loadList();
$q->clear();

$end_max = '0000-00-00 00:00:00';
$start_min = date('Y-m-d H:i:s');

//pull the tasks into an array and determine full project date range
foreach ($proTasks as $row) {
	if( !$row['task_start_date'] || $row['task_start_date'] == '0000-00-00 00:00:00'){
		$row['task_start_date'] = date('Y-m-d H:i:s');
	}
	$tsd = new CDate($row['task_start_date']);
	if ($tsd->before(new CDate($start_min)) && !$sdate )
		$start_min = $row['task_start_date'];

	// calculate or set blank task_end_date if unset
	if( !$row['task_end_date'] || $row['task_end_date'] == '0000-00-00 00:00:00') {
		$row['task_end_date'] = date('Y-m-d H:i:s');
		}
	$ted = new CDate($row['task_end_date']);
	if ($ted->after(new CDate($end_max)) && !$edate )
		$end_max = $row['task_end_date'];

	$tasks[] = $row;
}

// Set graph date range (if start date and/or end date not set use full project date range
$start_date = $sdate ? new CDate($sdate) : new CDate($start_min) ;
$end_date = $edate ? new CDate($edate) : new CDate($end_max) ;

// Intialize Gantt graph and set Graph title and label column tiltes
$graph = new iGanttResponse( $start_date->format(FMT_DATETIME_MYSQL), $end_date->format(FMT_DATETIME_MYSQL), $width, $working_days);
$graph->setDateFormat( $df, $tf );
$graph->setWorkingHours( dPgetConfig( 'cal_day_start' ), dPgetConfig( 'cal_day_end' ), dPgetConfig( 'cal_day_increment') );
$graph->setTableTitle(convText($project->project_name));
$graph->setColTitle( array( $AppUI->_('Task name'), $AppUI->_('Start date'), $AppUI->_('End date')),
					 array( 240, 80, 80 ) );
$graph->setExpandCollapse( $AppUI->_('Task name'), STATIC_EXPAND);

// check day_diff and set scale headers
$day_diff = $end_date->dateDiff($start_date);
if ( $day_diff < 0 ) {
	$graph->setError( "Invalid start or end date range : ".$start_date->format($df)." - ".$end_date->format($df));
}
if ($day_diff > 240){
        //more than 240 days
	  $graph->setScaleHeader(GANTT_HYEAR | GANTT_HMONTH);
} else if ($day_diff > 70){
        //more than 70 days and less of 241
	  $graph->setScaleHeader(GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK );
} else  if ( $day_diff < 7 ) {
	$graph->setScaleHeader(GANTT_HMONTH | GANTT_HWEEK | GANTT_HDAY | GANTT_HHOUR );
} else {
	$graph->setScaleHeader(GANTT_HMONTH | GANTT_HWEEK | GANTT_HDAY );
}
// Enhanced scale if set
$graph->setScaleEnhanced( $enhanced_scale );

function findgchild( &$tarr, $parent, $level=0 ){
/*
*	Recursive search ofparent task  all children
*		@param	task array
*		@param	parent task ID
*		@param	task hierarchy level
*/
	global $gantt_arr;
	$level = $level+1;
	$n = count( $tarr );
	for ($x=0; $x < $n; $x++) {
		if($tarr[$x]['task_parent'] == $parent && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']){
//			showgtask( $tarr[$x], $level );
			$gantt_arr[] = array( $tarr[$x], $level );
			findgchild( $tarr, $tarr[$x]['task_id'], $level);
		}
	}
}

// Create task tree based on parent/child relationship
global $gantt_arr;
$gantt_arr = array();
$tnums = count( $tasks );
for ($i=0; $i < $tnums; $i++) {
	$t = $tasks[$i];
	if ($t['task_parent'] == $t['task_id']) {
		$gantt_arr[] = array( $t, 0 );
		findgchild( $tasks, $t['task_id'] );
	}
}

// Draw Gantt bar for each task
$row = 0;
for($i = 0; $i < count($gantt_arr); $i ++ ) {
	$task->bind($gantt_arr[$i][0]);
	$level = $gantt_arr[$i][1];
	$id = $task->task_id;
	$name = convText($task->task_name);
	$name = strlen( $name ) > 40-$level ? substr( $name, 0, 37-$level ).'...' : $name ;
	$start = new CDate($task->task_start_date);
	$end = new CDate($task->task_end_date);
	$progress = (int)$task->task_percent_complete;
	if ($progress > 100)
		$progress = 100;
	elseif ($progress < 0)
		$progress = 0;
	$type = $task->task_milestone ? GANTT_MILESTONE : ( $task->task_dynamic == 1 ? GANTT_DYNAMIC : GANTT_ACTIVITY );
	$canMove = $perms->checkModuleItem( "tasks", "edit", $task->task_id );
	$graph->newBar( $type, $level, $id, $task->task_parent, $task->task_start_date, $task->task_end_date, $progress,
					array( $name, $start->format($df), $end->format($df) ), $canMove );
	// Set task dependencies
	$req_task = explode( ',', $task->getDependencies());
	foreach ( $req_task as $req )
		if ( $req )
			$graph->setConstrain( $req, $id, CONSTRAIN_ENDSTART);
	}

$graph->stroke();
$graph->script("document.getElementById(\"taskDetails\").style.display=\"none\";");
$graph->script("hideLoading();");
return $graph;
}

function displayTask( $task_id ) {
/*
*	Send back task detailed information for display in overlay
*/
global $AppUI;
global $task, $alltasks, $taskOptions;

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');
$tf = $AppUI->getPref('TIMEFORMAT');

// Initialize XAJAX response and load task data
$response = new xajaxResponse();
include_once ( $AppUI->getModuleClass('tasks') );
$task = new CTask();
if ( !$task->load($task_id) ) {
	$AppUI->setMsg("invalidTaskID", UI_MSG_ERROR);
	$response->assign( "error", "innerHTML", $AppUI->getMsg() );
	return $response;
	}

// Check edit permission
$perms =& $AppUI->acl();
//$noEdit = ( $perms->checkModuleItem('tasks', 'edit', $task_id) && $task->task_percent_complete < 100 ) ? "" : "disabled";
// PYB : Permettre l'édition même si la tache est à 100%
$noEdit = ( $perms->checkModuleItem('tasks', 'edit', $task_id) ) ? "" : "disabled";

// Task ID, name and owner
$response->assign("task_id", "value", $task->task_id);
$response->assign("task_name", "value", $task->task_name);
$response->assign("task_name", "disabled", $noEdit);
$response->script("setSelectOptionIndex(document.getElementById(\"task_owner\"), ".$task->task_owner.", \"$noEdit\");");

// Task start  date
$noEditStartDate = $noEdit || $task->task_percent_complete > 0 || $task->task_dynamic == 1;
$date = new CDate($task->task_start_date);
$response->assign("show_task_start_date", "value", $date->format($df) );
$response->assign("task_start_date", "value", $date->format(FMT_TIMESTAMP_DATE) );
$response->script("setSelectOptionIndex(document.getElementById(\"start_hour\"), ".$date->getHour().", \"$noEditStartDate\");");
$response->script("setSelectOptionIndex(document.getElementById(\"start_minute\"), ".$date->getMinute().", \"$noEditStartDate\");");
$response->script("setAMPM(document.getElementById(\"start_hour\"));");
$response->script("document.getElementById(\"startDateBtn\").style.visibility=\"". ( $noEditStartDate ? "hidden" : "visible" )."\";");

// Task end date
$noEditEndDate = $noEdit || $task->task_dynamic == 1;
$date = new CDate($task->task_end_date);
$response->assign("show_task_end_date", "value", $date->format($df) );
$response->assign("task_end_date", "value", $date->format(FMT_TIMESTAMP_DATE) );
$response->script("setSelectOptionIndex(document.getElementById(\"end_hour\"), ".$date->getHour().", \"$noEditEndDate\");");
$response->script("setSelectOptionIndex(document.getElementById(\"end_minute\"), ".$date->getMinute().", \"$noEditEndDate\");");
$response->script("setAMPM(document.getElementById(\"end_hour\"));");
$response->script("document.getElementById(\"endDateBtn\").style.visibility=\"". ( $noEditEndDate ? "hidden" : "visible" )."\";");

// Task priority, access and type
$response->script("setSelectOptionIndex(document.getElementById(\"task_priority\"), ".$task->task_priority.", \"$noEdit\");");
$response->script("setSelectOptionIndex(document.getElementById(\"task_access\"), ".$task->task_access.", \"$noEdit\");");
$response->script("setSelectOptionIndex(document.getElementById(\"task_type\"), ".$task->task_type.", \"$noEdit\");");

// Task duration and progress
$response->assign("task_duration", "value", $task->task_duration);
$response->assign("task_duration", "disabled", $noEditEndDate);
$response->script("setSelectOptionIndex(document.getElementById(\"task_duration_type\"), ".$task->task_duration_type.", \"$noEditEndDate\");");
$percent = 5*round($task->task_percent_complete/5);
$response->script( "setSelectOptionIndex(document.getElementById(\"task_percent_complete\"), ".$percent.", \"$noEditEndDate\");");

// Generate task parent options
$task->_query->clear();
$task->_query->addTable('tasks');
$task->_query->addQuery('task_id, task_parent, task_name, task_dynamic');
$task->_query->addWhere('task_project = '.$task->task_project );
$task->_query->addOrder('task_start_date, task_end_date');
$alltasks = $task->_query->loadList();
$task->_query->clear();

function constructTaskOptions( $taskId, $taskName, $level=0 ) {
global $alltasks, $task;
// First generate option for the current task
	$selected = $task->task_parent == $taskId ? 'selected="selected"' : '' ;
	$name = ( strlen($taskName) > (45-$level) ) ? substr( $taskName, 0, 42-$level ) . '...' : $taskName;
	$name = (( $level > 0 ) ? str_repeat('&nbsp;', $level) : '' ) . dPFormSafe($name);
	$str = "<option value=\"{$taskId}\" $selected >$name</option>\n";
// Then browse the task array to process children
	$level++;
	for ( $j=0; $j<count($alltasks); $j++ ) {
		$item =& $alltasks[$j];
		if ( $item['task_id'] != $item['task_parent'] && $item['task_parent'] == $taskId && $item['task_id'] != $task->task_id  ) {
			$str .= constructTaskOptions( $item['task_id'], $item['task_name'], $level );
		}
	}
	return $str;
}

$parentOptions = "<option value=\"{$task->task_id}\" >".$AppUI->_('None')."</option>\n";
for ( $i=0; $i<count($alltasks); $i++ ) {
	$item =& $alltasks[$i];
	if ( $item['task_id'] == $item['task_parent'] && $item['task_id'] != $task->task_id ) {
		$parentOptions .= constructTaskOptions( $item['task_id'], $item['task_name'] );
		}
	}
// Update parent task select field
$response->clear("task_parent", "innerHTML");
$response->assign("task_parent", "innerHTML", $parentOptions);
$response->script("setSelectOptionIndex(document.getElementById(\"task_parent\"), \"{$task->task_parent}\", \"$noEdit\");");

// Task dependency
$depTaskOptions = "<option value=\"-1\">(".$AppUI->_("Remove all dependencies").")</option>\n";
$depTaskOptions .= "<option value=\"0\" selected=\"selected\">(".$AppUI->_("Add dependency").")</option>\n";
foreach ( $alltasks as $t ) {
	if ( $t['task_dynamic'] != 1 && $t['task_id'] != $task->task_id ) {
		$name = ( strlen($t['task_name']) > 45 ) ? substr( $t['task_name'], 0, 42 ) . '...' : $t['task_name'];
		$depTaskOptions .= "<option value=\"{$t['task_id']}\">{$name}</option>\n";
	}
}
$response->clear("task_dependency", "innerHTML");
$response->assign("task_dependency", "innerHTML", $depTaskOptions);
$response->script("setSelectOptionIndex(document.getElementById(\"task_dependency\"), \"0\", \"$noEdit\");");

// Task assignees : zero task assign and update task assignee list for unassign
$response->script("setSelectOptionIndex(document.getElementById(\"task_assign\"), \"0\", \"$noEdit\");");
$response->script("setSelectOptionIndex(document.getElementById(\"task_assign_perc\"), \"100\", \"$noEdit\");");
$assignees = $task->getAssignedUsers();
$assigneeSelect = "";
if ( count($assignees) > 0 ) {
	$assigneeSelect .= "<option value=\"-1\">(".$AppUI->_("Unassign all users").")</option>\n";
	$assigneeSelect .= "<option value=\"0\" selected=\"selected\">(".$AppUI->_("Unassign user").")</option>\n";
	foreach ( $assignees as $uid => $a )
		$assigneeSelect .= "<option value=\"$uid\">".$a['contact_first_name']." ".$a['contact_last_name']."</option>\n";
}
$response->clear("task_unassign", "innerHTML");
$response->assign("task_unassign", "innerHTML", $assigneeSelect);
$response->script("setSelectOptionIndex(document.getElementById(\"task_unassign\"), \"0\", \"$noEdit\");");

// Task description
$response->assign("task_description", "value", $task->task_description);
$response->assign("task_description", "disabled", $noEdit);

// Make task details and any error message visible
$response->script("document.getElementById(\"editTaskBtn\").style.display=". ( $noEdit == "disabled" ? "\"none\"" : "\"block\"" ) .";");
$response->script("document.getElementById(\"taskDetails\").style.display=\"block\";");
$response->assign("error", "innerHTML", $AppUI->getMsg() );
$response->script("hideLoading();");

return $response;
}

function updateTask( $taskParams, $graphParams ) {
/*
*	Update task information and show updated graph
*/
global $AppUI;

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');
$tf = $AppUI->getPref('TIMEFORMAT');

// Check edit permission
$task_id = $taskParams['task_id'];
$perms =& $AppUI->acl();
if ( !$task_id ) {
	$AppUI->setMsg("invalidID", UI_MSG_ERROR);
	$graph = drawGraph( $graphParams );
	return $graph;
	}

if ( !$perms->checkModuleItem('tasks', 'edit', $task_id) ) {
	$AppUI->setMsg("accessDeniedMsg", UI_MSG_ERROR);
	$graph = drawGraph( $graphParams ) ;
	return $graph;
	}

// load task data
include_once ( $AppUI->getModuleClass('tasks') );
$task = new CTask();
if ( !$task->load($task_id) ) {
	$AppUI->setMsg("invalidID", UI_MSG_ERROR);
	$graph = drawGraph( $graphParams );
	return $graph;
	}

// Set hour minutes in task start/end dates
$hour = $taskParams['start_hour'];
$minute = $taskParams['start_minute'];
$date = new CDate( $taskParams['task_start_date']);
$date->setTime($hour, $minute);
$taskParams['task_start_date'] = $date->format(FMT_DATETIME_MYSQL);
$hour = $taskParams['end_hour'];
$minute = $taskParams['end_minute'];
$date = new CDate( $taskParams['task_end_date']);
$date->setTime($hour, $minute);
$taskParams['task_end_date'] = $date->format(FMT_DATETIME_MYSQL);

// Update task data
if ( ! $msg = $task->bind( $taskParams ) ) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR);
	$graph = drawGraph( $graphParams );
	return $graph;
}

// Update task assignees starting with unassignments
$unassign = $taskParams['task_unassign'];
if ( $unassign < 0 ) {		// Delete all existing assignments
	$task->_query->clear();
	$task->_query->setDelete('user_tasks');
	$task->_query->addWhere('task_id = ' . $task->task_id);
	$task->_query->exec();
	$task->_query->clear();
} else if ( $unassign > 0 ) {	// Delete a single assignment
	$task->removeAssigned( $unassign );
}

$assign = intval($taskParams['task_assign']);
if ( $assign > 0 ) {
	$perc = intval($taskParams['task_assign_perc']);
	$task->updateAssigned( $assign, array( $assign => $perc ), false );
}

// Update dependencies
$dependency = intval($taskParams['task_dependency']);
if ( $dependency > 0 ) {
	$dependencies = explode( ',', $task->getDependencies() );
	if ( !in_array( $dependency, $dependencies ) )
		$dependencies[count($dependencies)] = $dependency;
	$task->updateDependencies( implode( ',', $dependencies ) );
} else if ( $dependency < 0 ) {
	$task->updateDependencies( "" );
}

if ( $msg = $task->store() ) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR);
} else {
	$AppUI->setMsg( "Task updated", UI_MSG_OK);
}

$graph = drawGraph( $graphParams );
return $graph;
}
?>