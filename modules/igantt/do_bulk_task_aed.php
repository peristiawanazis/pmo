<?php /* IGANTT do_bulk_task_aed.php,v 0.1.1 2009/02/10 */
/*
Copyright (c) 2008 -2009 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	PHP routine called to update tasks after updating the Gantt graphics
*
* Author:		Pierre-Yves SIMONOT, Euxenis, <pierre-yves.simonot@euxenis.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
* version 0.1.1
*	Fix bug when no task has been updated (invalid ID error message was displayed erroneously)
*
*/
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

global $AppUI;

// Retrieve and check parameters
$project_id = dPgetParam( $_POST, 'project_id', 0 );
$bulk_task_id = dPgetParam( $_POST, 'bulk_task_id', '' );
$task_id_list = explode( ',', $bulk_task_id );
$task_start_date_list = explode( ',', dPgetParam( $_POST, 'bulk_task_start_date', '' ));
$task_end_date_list = explode( ',', dPgetParam( $_POST, 'bulk_task_end_date', '' ));
$bulk_task_dependencies = dPgetParam( $_POST, 'bulk_dependencies', '' );
$task_dependencies = explode( ';', $bulk_task_dependencies );

$redirect = "?m=projects&a=view&project_id=$project_id&tab=0";

if ( count($task_id_list) != count($task_start_date_list) || count($task_id_list) != count($task_end_date_list) ) {
	$AppUI->setMsg("NoMatchingArray", UI_MSG_ERROR );
	$AppUI->redirect( $redirect );
	}

include ($AppUI->getModuleClass("tasks"));
$obj = new CTask();
$task_arr = array();
$error = 0;

// Update dates for all non dynamic tasks
if ( $bulk_task_id ) {
	for ( $i=0; $i<count($task_id_list); $i++ ) {
		if ( !$obj->load($task_id_list[$i]) ) {
			$AppUI->setMsg("InvalidID", UI_MSG_ERROR, true);
			$AppUI->setMsg($task_id_list[$i], UI_MSG_ERROR, true);
			$error++;
			continue;
		} else {
			if ( $obj->task_dynamic != 1 ) {
				$task_arr[$obj->task_id] = $obj;
				if ( $task_start_date_list[$i] != "0000-00-00 00:00:00" )
					$obj->task_start_date = $task_start_date_list[$i];
				if ( $task_end_date_list[$i] != "0000-00-00 00:00:00" )
					$obj->task_end_date = $task_end_date_list[$i];
				$obj->store();
			}
		}
	}
}

// Add new dependencies
if ( $bulk_task_dependencies ) {
	for ( $i=0; $i<count($task_dependencies); $i++ ) {
		list ($task_req, $task_dep ) = explode( ",", $task_dependencies[$i] );
		if ( ! $obj->load($task_dep) ) {
			$AppUI->setMsg("InvalidID", UI_MSG_ERROR, true);
			$AppUI->setMsg($task_dep, UI_MSG_ERROR, true);
			$error++;
			continue;
		} else {
			$task_dep_list = $obj->getDependencies();
			$task_dep_list = $task_dep_list ? $task_dep_list.",".$task_req : $task_req;
			$obj->updateDependencies( $task_dep_list );
			$task_arr[$obj->task_id] = $obj;
			if ( $msg = $obj->check() ) {
				$AppUI->setMsg( $msg, UI_MSG_ERROR, true);
				$obj->updateDependencies("");
				$error++;
			}
		}
	}
}

// Now shift dependent tasks
foreach ( $task_arr as $task )
	$obj->shiftDependentTasks();

// Display error/succes message and return to project task list
if ( $error == 0 && ( $bulk_task_id || $bulk_task_dependencies ) ) {
	$AppUI->setMsg("Tasks", UI_MSG_OK);
	$AppUI->setMsg( "updated", UI_MSG_OK, true);
	}
$AppUI->redirect( $redirect );
?>
