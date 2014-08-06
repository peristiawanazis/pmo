<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}


$adjustStartDate = dPgetCleanParam($_POST, 'set_task_start_date');
$del = (int)dPgetParam($_POST, 'del', 0);
$task_id = (int)dPgetParam($_POST, 'task_id', 0);
$hassign = dPgetCleanParam($_POST, 'hassign');
$hperc_assign = dPgetCleanParam($_POST, 'hperc_assign');
$hdependencies = dPgetCleanParam($_POST, 'hdependencies');
$notify = (int)dPgetParam($_POST, 'task_notify', 0);
$comment = dPgetCleanParam($_POST, 'email_comment','');
$sub_form = (int)dPgetParam($_POST, 'sub_form', 0);
$send_ical = (int)dPgetParam($_POST, 'send_ical', 0);//if we want synchronise with the external calendar of assigned user
$send_ical_to_user = 0;
$q = new DBQuery();
$q->addTable('tasks_ical', 'ti');
$q->addQuery('ti.UID');
$q->addWhere('ti.task_id = ' . $task_id);
$task_ical = $q->loadResult();
if($task_ical){ $send_ical_to_user = 1;}

function setIcalEvent($task_id, $only_mail, $lastUpdate)
{
	global $send_ical, $send_ical_to_user;
	$q = new DBQuery();
	$q->addTable('tasks', 't');
	$q->addJoin('projects', 'p', 'p.project_id =  t.task_project');
	$q->addJoin('users', 'u', 'u.user_id =  t.task_creator');
	$q->addJoin('contacts', 'con', 'con.contact_id =  u.user_contact');
	$q->addJoin('tasks_ical', 'ti', 'ti.task_id =  t.task_id');
	$q->addQuery('DISTINCT t.task_name, t.task_end_date, t.task_start_date, t.task_description, con.contact_first_name, con.contact_last_name, con.contact_email, p.project_name, ti.UID, ti.created, ti.sequence');
	$q->addWhere('t.task_id = ' . $task_id);
	$rows = $q->loadList();
	$q->clear();
	$q = new DBQuery();
	$q->addTable('config');
	$q->addQuery('config_value');
	$q->addWhere('config_name =\'company_name\'');
	$location = $q->loadResult();
	$q->clear();
	
	foreach($rows as $row){
		$start_year = substr($row['task_start_date'], 0, 4);
		$start_month = substr($row['task_start_date'], 5, 2);
		$start_day = substr($row['task_start_date'], 8, 2);
		$start_hour = substr($row['task_start_date'], 11, 2);
		$start_min = substr($row['task_start_date'], 14, 2);
		$start_sec = substr($row['task_start_date'], 17, 2);

		$end_year = substr($row['task_end_date'], 0, 4);
		$end_month = substr($row['task_end_date'], 5, 2);
		$end_day = substr($row['task_end_date'], 8, 2);
		$end_hour = substr($row['task_end_date'], 11, 2);
		$end_min = substr($row['task_end_date'], 14, 2);
		$end_sec = substr($row['task_end_date'], 17, 2);

		$organiser_first_name = $row['contact_first_name'];
		$organiser_last_name = $row['contact_last_name'];
		$organiser_email = $row['contact_email'];
		
		$UID = $row['UID'] != null ? $row['UID'] : (date('YmdHis').'@dotproject.com');
		$created = $row['UID'] != null ? $row['created'] : date('Ymd\THis');
		$sequence = $row['UID'] != null ? ($row['sequence']) : 0;
		if(($lastUpdate != 2) && ($send_ical == 1)){$method='
METHOD:REQUEST';}
		else{$method='
METHOD:CANCEL';}
		$ical .=//we prepare the ical.ics text
'BEGIN:VCALENDAR'. 
$method.'
BEGIN:VEVENT
PRODID:-//Region Poitou-Charentes//NONSGML v1.0//FR
VERSION:2.0
DTSTART:'.$start_year .$start_month .$start_day .'T'. $start_hour.$start_min.$start_sec.'
DTEND:'.$end_year .$end_month .$end_day .'T'. $end_hour.$end_min.$end_sec.'
DTSTAMP:'.date('Ymd\THis').'
UID:'.$UID.'
ORGANIZER;CN='. $organiser_first_name . ' ' . $organiser_last_name .':MAILTO:'.$organiser_email.'
CREATED:'.$created.'
DESCRIPTION:'.$row['task_description'].'
LAST-MODIFIED:'.date('Ymd\THis').'
LOCATION:'.$location.'
SEQUENCE:'.$sequence.'
SUMMARY:'.$row['task_name'].' '.$row['project_name']
.($lastUpdate != 2 ? '
STATUS:CONFIRMED' : '
STATUS:CANCELLED').'
TRANSP:TRANSPARENT
ATTENDEE;ROLE=REQ-PARTICIPANT
END:VEVENT
END:VCALENDAR'
;
	}
	
	$ical = str_replace ( 'Ã©' , 'é' , $ical);
	$ical = str_replace ( 'Ã¨' , 'è' , $ical);
	$ical = str_replace ( 'Ã' , 'à' , $ical);
	$ical = str_replace ( 'Â°' , '°' , $ical);
	$ical = str_replace ( 'àª' , 'ê' , $ical);
	$ical = str_replace ( '&eacute;' , 'é' , $ical);
	$ical = str_replace ( '&acirc;' , 'â' , $ical);
	if($only_mail){
		return $organiser_email;
	}
	else{
		if(($lastUpdate != 2) && ($send_ical == 1)){
			$q->clear();
			if($row['UID'] == null){//we save ical information about this task in database
				$q->addTable('tasks_ical');
				$q->addInsert('task_id', $task_id);
				$q->addInsert('UID', $UID);
				$q->addInsert('created', $created);
				$q->addInsert('sequence', 0);
				$q->exec();
			}
			else{//we update ical information about this task in database
				$q->addTable('tasks_ical');
				$q->addUpdate('task_id', $task_id);
				$q->addUpdate('UID', $UID);
				$q->addUpdate('created', $created);
				$q->addUpdate('sequence', $sequence+1);
				$q->exec();
			}
		}
		else{
			$q->clear();
			$q->setDelete('tasks_ical');
			$q->addWhere('task_id', $task_id);
			$q->exec();
		}
		return $ical;
	}
}
$addText = $AppUI->_('add');
$editText = $AppUI->_('edit');
$delText = $AppUI->_('del');
$taskText = $AppUI->_('The Task');
$ofProjectText = $AppUI->_('of Project');
$hasBeenText = $AppUI->_('has been');
function sendIcal($task_id, $lastUpdate)
{
	global $addText, $editText, $delText, $taskText, $ofProjectText, $hasBeenText,$send_ical, $send_ical_to_user;
	$ical = setIcalEvent($task_id, 0, $lastUpdate);
	
	//we save the ical file
	$file = fopen('./modules/ical/ical.ics', 'r+');
	ftruncate($file,0);
	fputs($file, $ical);
	fclose($file);
	
	//we prepare the mail with the ical file in attached_file
	$boundary = "_".md5 (uniqid (rand()));
	
	$attached_file = file_get_contents('./modules/ical/ical.ics'); 
	$attached_file = chunk_split(base64_encode($attached_file));

	$attached = "\n\n". "--" .$boundary . "\nContent-Type: application/ics; name=\"$file\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"ical.ics\"\r\n\n".$attached_file . "--" . $boundary . "--";
	$q = new DBQuery();
	$q->addTable('config');
	$q->addQuery('config_value');
	$q->addWhere('config_name =\'site_domain\'');
	$site = $q->loadResult();
	$q->clear();
	$headers ="From: noreply@".$site." \r\n";
	$headers .= "MIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
	
	if($lastUpdate == 0){
		$updateMessage = $addText;
	}
	else if($lastUpdate == 1){
		$updateMessage = $editText;
	}
	else if($lastUpdate == 2){
		$updateMessage = $delText;
	}
	else{}
	$q = new DBQuery();
	$q->addTable('tasks', 't');
	$q->addJoin('projects', 'p', 'p.project_id =  t.task_project');
	$q->addQuery('DISTINCT t.task_name, p.project_name');
	$q->addWhere('t.task_id = ' . $task_id);
	$rows = $q->loadList();
	$q->clear();
	foreach($rows as $row){
		$message = $taskText.' "'.$row['task_name'].'" '.$ofProjectText.' "'.$row['project_name'].'" '.$hasBeenText.' '.$updateMessage;
	}
	$message = str_replace ( 'Ã©' , 'é' , $message);
	$message = str_replace ( 'Ã¨' , 'è' , $message);
	$message = str_replace ( 'Ã' , 'à' , $message);
	$message = str_replace ( 'Â°' , '°' , $message);
	$message = str_replace ( 'àª' , 'ê' , $message);
	$message = str_replace ( '&eacute;' , 'é' , $message);
	$message = str_replace ( '&acirc;' , 'â' , $message);
	$body = "--". $boundary ."\nContent-Type: text/calendar; charset=ISO-8859-1\r\n\n".$ical ."\r\n\n". "--". $boundary ."\nContent-Type: text/plain; charset=ISO-8859-1\r\n\n".$message. $attached;
	$q = new DBQuery();
	$q->addTable('user_tasks', 'ut');
	$q->addJoin('users', 'u', 'u.user_id =  ut.user_id');
	$q->addJoin('contacts', 'con', 'con.contact_id =  u.user_contact');
	$q->addQuery('con.contact_email');
	$q->addWhere('ut.task_id = ' . $task_id);
	$rows = $q->loadList();
	$q->clear();
	if($rows != null){
		foreach($rows as $row){
			$assigned_user = $row['contact_email'];
			while(!mail(((($assigned_user != NULL) ? $assigned_user : setIcalEvent($task_id, 1))),'dotProject synchronisation',$body,$headers));
		}
	}
	else{//if we don't have assigned user we send ical to the creator of this task
		while(!mail(setIcalEvent($task_id, 1, $lastUpdate),'dotProject synchronisation',$body,$headers));
	}
}

if ($sub_form) {
	// in add-edit, so set it to what it should be
	$AppUI->setState('TaskAeTabIdx', $_POST['newTab']);
	if (isset($_POST['subform_processor'])) {
		$mod = ((isset($_POST['subform_module']))
				? $AppUI->checkFileName($_POST['subform_module']) 
				: 'tasks');
		$proc = $AppUI->checkFileName($_POST['subform_processor']);
		include (DP_BASE_DIR . '/modules/' . $mod . '/' . $proc . '.php');
	}
} else {
	// Include any files for handling module-specific requirements
	foreach (findTabModules('tasks', 'addedit') as $mod) {
		$fname = (DP_BASE_DIR . '/modules/' . $mod . '/tasks_dosql.addedit.php');
		dprint(__FILE__, __LINE__, 3, ('checking for ' . $fname));
		if (file_exists($fname)) {
			require_once $fname;
		}
	}
	
	$obj = new CTask();
	
	// If we have an array of pre_save functions, perform them in turn.
	if (isset($pre_save)) {
		foreach ($pre_save as $pre_save_function) {
			$pre_save_function();
		}
	} else {
		dprint(__FILE__, __LINE__, 2, 'No pre_save functions.');
	}
	
	// Find the task if we are set
	$task_end_date = null;
	if ($task_id) {
		$obj->load($task_id);
		$task_end_date = new CDate($obj->task_end_date);
	}
	if ($_POST['task_start_date'] === '') {
		$_POST['task_start_date'] = '000000000000';
	}
	if ($_POST['task_end_date'] === '') {
		$_POST['task_end_date'] = '000000000000';
	}
	
	if (isset($_POST) && !($obj->bind($_POST))) {
		$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
		$AppUI->redirect();
	}
	
	if (!($obj->task_owner)) {
		$obj->task_owner = $AppUI->user_id;
	}
	
	// Check to see if the task_project has changed
	$move_files = false;
	if (isset($_POST['new_task_project']) && $_POST['new_task_project'] && ($obj->task_project != $_POST['new_task_project'])) {
		$move_files = $obj->task_project;
		$obj->task_project = $_POST['new_task_project'];
		$obj->task_parent  = $obj->task_id;
		// Need to ensure any files that are associated with the task also
		// get their project changed.
	}
	
	// Map task_dynamic checkboxes to task_dynamic values for task dependencies.
	if ($obj->task_dynamic != 1) {
		$task_dynamic_delay = (int)dPgetParam($_POST, 'task_dynamic_nodelay', '0');
		if (in_array($obj->task_dynamic, $tracking_dynamics)) {
			$obj->task_dynamic = $task_dynamic_delay ? 21 : 31;
		} else {
			$obj->task_dynamic = $task_dynamic_delay ? 11 : 0;
		}
	}
    
	// Make sure checkboxes are set or reset as appropriately
	$checkbox_properties = array('task_dynamic', 'task_milestone', 'task_notify');
	foreach ($checkbox_properties as $task_property) {
		if (!(array_key_exists($task_property, $_POST))) {
			$obj->$task_property = false;
		}
	}
	
	//format hperc_assign user_id=percentage_assignment;user_id=percentage_assignment;user_id=percentage_assignment;
	$tmp_ar = explode(';', $hperc_assign);
	$hperc_assign_ar = array();
	for ($i = 0, $xi = sizeof($tmp_ar); $i < $xi; $i++) {
		$tmp = explode('=', $tmp_ar[$i]);
		$hperc_assign_ar[$tmp[0]] = ((count($tmp) > 1) ? $tmp[1] : 100);
	}
	
	// let's check if there are some assigned departments to task
	$obj->task_departments = implode(',', dPgetCleanParam($_POST, 'dept_ids', array()));
	
	// convert dates to SQL format first
	if ($obj->task_start_date) {
		$date = new CDate($obj->task_start_date);
		$obj->task_start_date = $date->format(FMT_DATETIME_MYSQL);
	}
	$end_date = null;
	if ($obj->task_end_date) {
		if (mb_strpos($obj->task_end_date, '2400') !== false) {
		  $obj->task_end_date = str_replace('2400', '2359', $obj->task_end_date);
		}
		$end_date = new CDate($obj->task_end_date);
		$obj->task_end_date = $end_date->format(FMT_DATETIME_MYSQL);
	}
	
	require_once($AppUI->getSystemClass('CustomFields'));
	
	// prepare (and translate) the module name ready for the suffix
	if ($del) {
		if($send_ical_to_user || $send_ical){
				sendIcal($task_id, 2);
		}
		if (($msg = $obj->delete())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect();
		} else {
			$AppUI->setMsg('Task deleted');
			$AppUI->redirect('', -1);
		}
	} else {
		if (($msg = $obj->store())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect(); // Store failed don't continue?
		} else {
			$custom_fields = New CustomFields($m, 'addedit', $obj->task_id, 'edit');
 			$custom_fields->bind($_POST);
 			$sql = $custom_fields->store($obj->task_id); // Store Custom Fields
			
			// Now add any task reminders
			// If there wasn't a task, but there is one now, and
			// that task date is set, we need to set a reminder.
			if (empty($task_end_date) || (!(empty($end_date)) 
			                              && $task_end_date->dateDiff($end_date))) {
				$obj->addReminder();
			}

			// If there was a file that was attached to both the task, and the task
			// has moved projects, we need to move the file as well
			if ($move_files) {
				require_once $AppUI->getModuleClass('files');
				$filehandler = new CFile();
				$q = new DBQuery();
				$q->addTable('files', 'f');							
				$q->addQuery('file_id');
				$q->addWhere('file_task = ' . (int)$obj->task_id);
				$files = $q->loadColumn();
				if (!empty($files)) {
					foreach ($files as $file) {
						$filehandler->load($file);
						$realname = $filehandler->file_real_filename;
						$filehandler->file_project = $obj->task_project;
						$filehandler->moveFile($move_files, $realname);
						$filehandler->store();
					}
				}
			}
			
			$AppUI->setMsg($task_id ? 'Task updated' : 'Task added', UI_MSG_OK);
		}
		
		if (isset($hassign)) {
			$obj->updateAssigned($hassign , $hperc_assign_ar);
		}
		//send ical event to assigned user
		if($send_ical_to_user || $send_ical){
			if($task_id){
				sendIcal($obj->task_id, 1);
			}
			else{
				sendIcal($obj->task_id, 0);
			}
		}
				
		if (isset($hdependencies)) { // && !empty($hdependencies)) {
			// there are dependencies set!
			
			// backup initial start and end dates
			$tsd = new CDate ($obj->task_start_date);
			$ted = new CDate ($obj->task_end_date);
			
			// updating the table recording the 
			// dependency relations with this task
			$obj->updateDependencies($hdependencies);
			
			// we will reset the task's start date based upon dependencies
			// and shift the end date appropriately
			if ($adjustStartDate && !is_null($hdependencies)) {
				
				// load already stored task data for this task
				$tempTask = new CTask();
				$tempTask->load($obj->task_id);
				
				// shift new start date to the last dependency end date
				$nsd = new CDate ($tempTask->get_deps_max_end_date($tempTask));
				
				// prefer Wed 8:00 over Tue 16:00 as start date
				$nsd = $nsd->next_working_day();
				
				// prepare the creation of the end date
				$ned = new CDate();
				$ned->copy($nsd);
				
				if (empty($obj->task_start_date)) {
					// appropriately calculated end date via start+duration
					$ned->addDuration($obj->task_duration, $obj->task_duration_type);		
				} else { 			
					// calc task time span start - end
					$d = $tsd->calcDuration($ted);
					
					// Re-add (keep) task time span for end date.
					// This is independent from $obj->task_duration.
					// The value returned by Date::Duration() is always in hours ('1') 
					$ned->addDuration($d, '1');
				}
				
				// prefer tue 16:00 over wed 8:00 as an end date
				$ned = $ned->prev_working_day();
				
				$obj->task_start_date = $nsd->format(FMT_DATETIME_MYSQL);
				$obj->task_end_date = $ned->format(FMT_DATETIME_MYSQL);						
				
				$q = new DBQuery;
				$q->addTable('tasks', 't');							
				$q->addUpdate('task_start_date', $obj->task_start_date);	
				$q->addUpdate('task_end_date', $obj->task_end_date);
				$q->addWhere('task_id = '.$obj->task_id);
				$q->addWhere('task_dynamic != 1');
				$q->exec();
				$q->clear();
			}
		}
		
		$q = new DBQuery();
		$q->addTable('task_dependencies');
		$q->addQuery('dependencies_task_id');
		$q->addWhere('dependencies_req_task_id ='.$task_id);
		$task_son = $q->loadList();
		$q->clear();
		if($task_son != null){
			foreach($task_son as $ts){
				$q = new DBQuery();
				$q->addTable('tasks_ical', 'ti');
				$q->addQuery('ti.UID');
				$q->addWhere('ti.task_id = ' . $ts['dependencies_task_id']);
				$task_ical = $q->loadResult();
				if($task_ical){
					$send_ical = 1;
					sendIcal($ts['dependencies_task_id'], 1);
				}
				else{
					$send_ical = 0;
				}
			}
		}
		
		// If there is a set of post_save functions, then we process them
		if (isset($post_save)) {
			foreach ($post_save as $post_save_function) {
				$post_save_function();
			}
		}
		
		if ($notify && $msg = $obj->notify($comment)) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
		}
		$task = new CTask();
		$task->load($obj->task_id);
		$parent = new CTask();
		$parent->load($obj->task_parent);
		while ($parent->task_id != $parent->task_parent){
			$task = $parent;
			$parent->load($parent->task_parent);
		}
		$AppUI->redirect();
	}
} // end of if subform
?>
