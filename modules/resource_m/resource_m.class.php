<?php
/* RESOURCE_M resource_m.php, v 0.2.0 2012/07/19 */
/*
* Copyright (c) 2011-2012 Region Poitou-Charentes (France)
*
* Description:	PHP function page of the Resource Management module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
* version 0.1.2
*	Fix array_multisort warning with PHP 5.3+.
*	Optimize the getAssignment function.
* version 0.1.2.1
*   New algorithm of sorting tasks.
* version 0.1.2.2
*	Fix the 1 hour tasks bug.
* version 0.2.0
*	Change the way to indicate to JS script what it need to compute
*	Integrate the project color identifier
*	Integrate filter icons
*/

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}



/* Calculate the number of different days in a date range.
** @param	CDate	start
** @param	CDate	end
** @return	int		dayCount 
*/ 
function dayCount($start, $end) {
	return ($start->dateDiff($end)+1);
}

/* Calculate the number of different weeks in a date range.
** @param	CDate	start
** @param	CDate	end
** @return	int		weekCount 
*/ 
function weekCount($start, $end) {
	$res = ceil(dayCount($start, $end)/7);
	if ($start->getDayOfWeek() > $end->getDayOfWeek() ) 
		return ($res+1);
	return $res;
}

/* Calculate the number of different months in a date range.
** @param	CDate	start
** @param	CDate	end
** @return	int		monthCount 
*/ 
function monthCount($start, $end) {
	return ceil(dayCount($start, $end)/30);
}

/* Calculate the number of different years in a date range.
** @param	CDate	start
** @param	CDate	end
** @return	int		yearCount 
*/ 
function yearCount($start, $end) {
	return ceil(dayCount($start, $end)/365);
}

/* Get the next value of this date inluding the granularity.
** @param	CDate	ccurrent
** @param	int		show
*/ 
function nextDate($ccurrent, $show) {
	switch ($show) {
		case 0 : $ccurrent->addYears(1);break;
		case 1 : $ccurrent->addMonths(1);break;
		case 2 : $ccurrent->addDays(7);break;
		case 3 : $ccurrent->addDays(1);break;
		case 4 : $ccurrent->addHours(1);break;
		case T_DEFAULT: break;
	}
}

/* Get the previous value of this date inluding the granularity.
** @param	CDate	ccurrent
** @param	int		show
*/ 
function prevDate($ccurrent, $show) {
	switch ($show) {
		case 0 : $ccurrent->addYears(-1);break;
		case 1 : $ccurrent->addMonths(-1);break;
		case 2 : $ccurrent->addDays(-7);break;
		case 3 : $ccurrent->addDays(-1);break;
		case 4 : $ccurrent->addHours(-1);break;
		case T_DEFAULT: break;
	}
}

/* Get the first day of the week of this date.
** @param	"%Y%m%d"	date
** @return	"%Y%m%d"	first day of the week
*/ 
function getStartOfWeek($date) {
	$cdate=new CDate($date);
	if($cdate->format('%w') == 0)
		return $date;
	else {
		$cdate->addDays(-1);
		return getStartOfWeek($cdate->format('%Y%m%d'));
	}
}

/* Get the last day of the week of this date.
** @param	"%Y%m%d"	date
** @return	"%Y%m%d"	last day of the week
*/ 
function getEndOfWeek($date) {
	$cdate=new CDate($date);
	if($cdate->format('%w') == 6)
		return $date;
	else {
		$cdate->addDays(1);
		return getEndOfWeek($cdate->format('%Y%m%d'));
	}
}

/* Tell if a CDate is a worked day between start and end SQL Date.
** @param	CDate	ccurrent
** @param	string	start	SQL Date
** @param	string	end		SQL Date
** @return	boolean
*/ 
function isRangedDate($ccurrent,$start,$end) {
	if(!$ccurrent->isWorkingDay())
		return false;
	if($ccurrent->format('%Y-%m-%d') < substr($start, 0, 10))
		return false;
	if($ccurrent->format('%Y-%m-%d') > substr($end, 0, 10))
		return false;
	return true;
}






/* Loads the differents users including filters.
** @param	int 		contact_id
** @param	int 		company_id
** @param	int 		department_id
** @param	int 		project_id
** @return	CContact[]	users
*/ 
function loadUsersIncludindFilters($contact_id, $company_id, $department_id, $project_id) {
	$q = new DBQuery;
	$q->addTable('users', 'u');
	$q->addQuery('DISTINCT(contact_id)');
	$q->addJoin('contacts', 'con', 'user_contact = contact_id');
	if ($contact_id)
		$q->addWhere('contact_id = '.$contact_id);
	if ($company_id)
		$q->addWhere('contact_company = '.$company_id);
	if ($department_id){
		$q->addJoin('departments', 'd', 'con.contact_department = d.dept_id');
		$q->addWhere('con.contact_department = '.$department_id.' OR d.dept_parent = '.$department_id);

	}
	if($project_id){
		$q->addJoin('user_tasks', 'ut', 'ut.user_id = u.user_id');
		$q->addJoin('tasks', 't', 't.task_id = ut.task_id');
		$q->addWhere('t.task_project = '.$project_id);
	}
	$q->addOrder('contact_last_name, contact_first_name');
	$usersId = 	$q->loadColumn();
	
	$users	 =	Array();
	foreach($usersId as $key => $row) {
		$users[$key] = new CContact();
		$users[$key]->load($row);
	}
	return $users;
}

/* Loads the differents projects of this tasks.
** @param	CTask[]		tasks
** @return	CProject[]	projects
*/ 
function loadProjectsOf($tasks) {
	$q = new DBQuery;
	$q->addTable('projects', 'p');
	$q->addQuery('DISTINCT(project_id)');
	$where = 'false';
	foreach ($tasks as $task) {
		$where .= ' OR project_id = '.$task->task_project;
	}
	$q->addWhere($where);
	$projectsId = 	$q->loadColumn();
	
	$projects 	= 	Array();
	foreach($projectsId as $key => $row)
	{
		$projects[$key] = new CProject();
		$projects[$key]->load($row);
	}
	return $projects;
}

/* Loads the differents task (sorted) of this user.
** @param	int			contact_id
** @param	"%Y%m%d"	start_date
** @param	"%Y%m%d"	end_date
** @return	CTask[]		tasks
*/ 
function loadTasksOf($contact_id, $start_date, $end_date) {
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
	$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
	$q->addWhere('(u.user_contact = '.$contact_id.' OR t.task_owner = '.getUserId($contact_id).')');
	$q->addWhere('task_start_date < str_to_date("'.$end_date.'", "%Y%m%d")');
	$q->addWhere('task_end_date > str_to_date("'.$start_date.'", "%Y%m%d")');
	$q->addWhere('task_parent = t.task_id');
	$q->addOrder('task_start_date , task_end_date, task_id');
	$ids = $q->loadColumn(); 				// here you have all top level task's ids
	
	$tasks_ids = Array();

	foreach ($ids as $id) {				 	// add the id to the list and all is childs too
		$tasks_ids[] = $id;
		$child_ids = getChildIds($id, $contact_id, $start_date, $end_date);
		foreach ($child_ids as $c_id)
			$tasks_ids[] = $c_id;
	}

	$tasks = Array();
	foreach($tasks_ids as $key => $row) { 	// create the CTask array from the ids
		$tasks[$key] = new CTask();
		$tasks[$key]->load($row);
	}
	
	return $tasks;
}

/* Recursive function that return an array of all children's ID of a task
** @param	int			task_id
** @param	int			contact_id
** @param	"%Y%m%d"	start_date
** @param	"%Y%m%d"	end_date
** @return	int[]		child_ids
*/ 
function getChildIds($task_id, $contact_id, $start_date, $end_date) {
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
	$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
	$q->addWhere('(u.user_contact = '.$contact_id.' OR t.task_owner = '.getUserId($contact_id).')');
	$q->addWhere('task_start_date < str_to_date("'.$end_date.'", "%Y%m%d")');
	$q->addWhere('task_end_date > str_to_date("'.$start_date.'", "%Y%m%d")');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	$q->addOrder('task_start_date , task_end_date, task_id');
	$child_ids = $q->loadColumn(); 
	
	if ($child_ids == null)	{
		return Array(); 
	} else {
		foreach ($child_ids as $id) {
			$ret[] = $id;
			$c = getChildIds($id, $contact_id, $start_date, $end_date);
			if ($c != Array()) {
				foreach ($c as $i)
					$ret[] = $i;
			}
		}
		return $ret;
	}
}



/* Edit Duration values of a task and adapt the user assignment to this task if requested by user.
** @param	int			user_id
** @param	int			task_id
** @param	int			new_sdate
** @param	int			new_edate
** @param	boolean		adapt
*/ 
function editDur($user_id, $task_id, $new_sdate, $new_edate, $adapt=false) {
	global $dPconfig;
	$cnew_sdate = new CDate($new_sdate);
	$cnew_edate = new CDate($new_edate);
	$new_dur = $cnew_sdate->calcDuration($cnew_edate);
	if ($adapt) { // If user request an adaptation
		// Take the old assignment of this user to this task
		$q = new DBQuery; 
		$q->addTable('user_tasks', 'ut');
		$q->addQuery('perc_assignment');
		$q->addWhere('user_id = '.$user_id);
		$q->addWhere('task_id = '.$task_id);
		$old_percent = $q->loadResult();
		
		// Take the old start and end date of this task
		$q = new DBQuery; 
		$q->addTable('tasks', 't');
		$q->addQuery('task_start_date');
		$q->addWhere('task_id = '.$task_id);
		$cold_sdate = new CDate($q->loadResult());
		$q = new DBQuery; 
		$q->addTable('tasks', 't');
		$q->addQuery('task_end_date');
		$q->addWhere('task_id = '.$task_id);
		$cold_edate = new CDate($q->loadResult());
		
		// Calculate the new percent from the old values and the new dates
		$old_dur = $cold_sdate->calcDuration($cold_edate);
		$mult = $old_dur / $new_dur;
		$new_percent =  $old_percent * $mult;
		
		// Update assignment
		$q = new DBQuery; 
		$q->addTable('user_tasks', 'ut');
		$q->addUpdate('perc_assignment', $new_percent);
		$q->addWhere('user_id = '.$user_id);
		$q->addWhere('task_id = '.$task_id);
		$q->exec();
	}
	$dur_type = ($new_dur > $dPconfig['daily_working_hours']) ? '24' : '1'; 
	if ($dur_type != 1)
		$new_dur = $new_dur / $dPconfig['daily_working_hours'] ;
		
	// Update duration
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addUpdate('task_start_date',$cnew_sdate->format(FMT_DATETIME_MYSQL));
	$q->addUpdate('task_end_date',$cnew_edate->format(FMT_DATETIME_MYSQL));
	$q->addUpdate('task_duration',$new_dur);
	$q->addUpdate('task_duration_type',$dur_type);
	$q->addWhere('task_id = '.$task_id);
	$q->exec();	
	
	// Update dependencies by loading parents
	$task = new CTask();
	$task->load($task_id);
	$task->shiftDependentTasks();
}

/* Edit Assignment values of a user to a task and adapt the task duration if requested by user.
** @param	int			user_id
** @param	int			task_id
** @param	int			new_percent
** @param	int			aff_style
** @param	boolean		adapt
*/ 
function editAssign($user_id, $task_id, $new_percent, $aff_style, $adapt=false) {
	global $dPconfig;
	if ($aff_style)
		$new_percent = round($new_percent * 100 / $dPconfig['daily_working_hours']); 	// Change from hours to %.
	if ($adapt) { // If user request an adaptation
		// Take the old assignment of this user to this task
		$q = new DBQuery; 
		$q->addTable('user_tasks', 'ut');
		$q->addQuery('perc_assignment');
		$q->addWhere('user_id = '.$user_id);
		$q->addWhere('task_id = '.$task_id);
		$old_percent = $q->loadResult();
		
		// Take the start and end date of this task
		$q = new DBQuery; 
		$q->addTable('tasks', 't');
		$q->addQuery('task_start_date');
		$q->addWhere('task_id = '.$task_id);
		$csdate = new CDate($q->loadResult());
		$q = new DBQuery; 
		$q->addTable('tasks', 't');
		$q->addQuery('task_end_date');
		$q->addWhere('task_id = '.$task_id);
		$cedate = new CDate($q->loadResult());
		
		// Calculate the new duration and new end date from the old values and the new assignment
		$old_dur = $csdate->calcDuration($cedate);
		$mult = $old_percent / $new_percent;
		$new_dur =  $old_dur * $mult;
		$nedate = new CDate($csdate);
		$nedate->addDuration($new_dur);
		
		$dur_type = ($new_dur > $dPconfig['daily_working_hours']) ? '24' : '1'; 
		if ($dur_type != 1)
			$new_dur = $new_dur / $dPconfig['daily_working_hours'] ;
		
		
		// Update duration
		$q = new DBQuery; 
		$q->addTable('tasks', 't');
		$q->addUpdate('task_end_date',$nedate->format(FMT_DATETIME_MYSQL));
		$q->addUpdate('task_duration',$new_dur);
		$q->addUpdate('task_duration_type',$dur_type);
		$q->addWhere('task_id = '.$task_id);
		$q->exec();	
	}
	$task = new CTask();
	$task->load($task_id);
	$task->shiftDependentTasks();
	// Update assignment
	$q = new DBQuery; 
	$q->addTable('user_tasks', 'ut');
	$q->addUpdate('perc_assignment', $new_percent);
	$q->addWhere('user_id = '.$user_id);
	$q->addWhere('task_id = '.$task_id);
	$q->exec();
}

/* Get an avergage of the assignment of this user on this task on the period next to this date.
** @param	CDate	ccurrent
** @param	CTask	task
** @param	int		user_id
** @param	int		scale
** @param	int		show
** @return	float	assign		
*/
function getAssignment($ccurrent, $task, $user_id, $scale, $show) {
	if($show < 3) { // The assignment should be calculate for a ranged date so we need to average.
		//$pcntl	= function_exists('pcntl_fork');
		
		// Initialization of dates.
		$ctmp 	= new CDate($ccurrent); 
		$cnext 	= new CDate($ccurrent);	
		nextDate($cnext, $show);
		$show	= 3; 
		prevDate($cnext, $show);
		prevDate($ctmp, $show);
		
		
		$assign = 0;
		$i		= 0;
		//$pid_a 	= Array();
		
		while ($ctmp < $cnext) {
			/*if ($pcntl)	{
				$pid 	= pcntl_fork();
				if ($pid == -1)
					die('dupplication impossible');
				else if ($pid) 
					$pid_a[] = $pid;
				else if($ctmp->isWorkingDay()) {
					$i++;
					if(isRangedDate($ctmp, $task->task_start_date, $task->task_end_date))
						$assign += getDirectAssignment($ctmp,$task,$user_id,$scale);
				}
			} else { */
				if($ctmp->isWorkingDay()) {
					$i++;
					if(isRangedDate($ctmp, $task->task_start_date, $task->task_end_date))
						$assign += getDirectAssignment($ctmp, $task, $user_id, $scale); // Get assignment for this date and add it to the total.
				}
			//}
			nextDate($ctmp,$show);	
		}
		//if ($pcntl)
			//pcntl_waitpid  ( -1 , $status);
		if($assign)
			$assign = round($assign/$i,1); // Average
		else
			$assign = null;
	} else { // We can directly check the assignment.
		if(!isRangedDate($ccurrent, $task->task_start_date, $task->task_end_date))
			return null;
		$assign	= getDirectAssignment($ccurrent, $task, $user_id, $scale);
	}
	return $assign;
}

/* Function used by getAssignment(), directly check the assignment of this user to this task for this date, don't make any test before.
** @param	CDate	ccurrent
** @param	CTask	task
** @param	int		user_id
** @param	int		scale
** @return	float	assign		
*/
function getDirectAssignment($ccurrent, $task, $user_id, $scale) {
	if($task->task_dynamic == 1) { // Sum assignments of children
		$childsId	= $task->getChildren();
		$assign 	= getChildrenAssign($ccurrent, $childsId, $user_id);
	} else // Get the data base value
		$assign 	= getUserTaskAssign($task->task_id, $user_id);
	if ($assign == 0)
		return null;
	return (round($assign*4*$scale/100)/4);
}

/* Recursive Function used by getDirectAssignment(), sum the assignments of this user to this task children for this date.
** @param	CDate	ccurrent
** @param	int	childId
** @param	int		user_id
** @return	float	assign		
*/
function getChildrenAssign($ccurrent, $childsId, $user_id) {
	$childsAssign = Array();
	foreach($childsId as $key => $row) {
		$child = new CTask();
		$child->load($row);
		if(!isRangedDate($ccurrent, $child->task_start_date, $child->task_end_date))
			$childsAssign[$key] = 0;
		else if($child->task_dynamic == 1) {
			$childsId = $child->getChildren();
			$childsAssign[$key] = getChildrenAssign($ccurrent, $childsId, $user_id);
		} else
			$childsAssign[$key] = getUserTaskAssign($child->task_id, $user_id);
	}
	return array_sum($childsAssign);
}


/* Function used by getDirectAssignment() and getChildrenAssign(), directly get the assignment of this user to this task in data base.
** @param	int		taskid
** @param	int		user_id
** @return	float	assign		
*/
function getUserTaskAssign($taskid, $user_id) {
	$q = new DBQuery;
	$q->addTable('user_tasks', 'ut');
	$q->addQuery('perc_assignment');
	$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
	$q->addWhere('u.user_contact = '.$user_id);
	$q->addWhere('task_id='.$taskid);
	return $q->loadResult();
}







/* Get an array of parent start dates of a task begening from the task_id start date to the last parent start date.
** @param	int			task_id
** @return	string[]	parentsdate		Array of SQL Date
*/ 
function getParentStartDate($task_id) {
	$parentsdate = Array();
	do {
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->addQuery('DISTINCT(task_id), task_parent, task_start_date');
		$q->addWhere('task_id = '.$task_id);
		$parent = $q->loadList();
		
		$task_id = $parent[0]['task_parent'];
		$parentsdate[] = $parent[0]['task_start_date'];
	} while ($parent[0]['task_id'] != $parent[0]['task_parent']);
	return $parentsdate;
}

/* Sort the tasks in the correct display order.
** @param	Array(string,CTask[])	parents
** @return	CTask[]		tasks		Sorted parents['obj'] array
*/ 
function orderingArrayTask($parents) {
	$maxLength = 0 ;
	foreach($parents as &$parent) {
		$length 	= count($parent['parent']);
		$maxLength 	= max($length,$maxLength);
	}
	for($i = 0; $i < $maxLength; $i++)
		$tmp[$i] 	= Array();

	foreach($parents as &$parent) {
		$length 	= count($parent['parent']);
		for ($i = 1; $i <= $length; $i++) 
			$tmp[$i-1][] 	= &$parent['parent'][$i-1];
			
		for ($i = $length; $i < $maxLength; $i++)
			$tmp[$i][] 		= '0000-00-00 00:00:00';
	}
	
	$args=Array();
	
	for($i=0;$i<$maxLength;$i++)
		$args[] = &$tmp[$i];
		
	$args[] = &$parents;
    call_user_func_array('array_multisort', $args);
	$tasks	= Array();
	foreach($parents as &$parent)
		$tasks[]	= $parent['obj'];
		
    return $tasks;
}













/* Get the contact_id of a user from is user_id.
** @param	int		user_id
** @return	int		contact_id
*/ 
function getContactId($user_id) {
	$q = new DBQuery;
	$q->addTable('users', 'u');
	$q->addQuery('DISTINCT(user_contact)');
	$q->addWhere('user_id = '.$user_id);
	return $q->loadResult();
}

/* Get the user_id of a user from is contact_id.
** @param	int		contact_id
** @return	int		user_id
*/ 
function getUserId($contact_id) {
	$q = new DBQuery;
	$q->addTable('users', 'u');
	$q->addQuery('DISTINCT(user_id)');
	$q->addWhere('user_contact = '.$contact_id);
	return $q->loadResult();
}






/* Get the correct background-color  of a user from is contact_id.
** @param	int			value
** @param	int			max_value
** @return	string		css option
*/ 
function getBackgroundColor($value, $max_value) {
	$start 	= Array(230,238,221); 	// Background color for 0% 
	$end 	= Array(170,221,170); 	// Background color for 100% 
	$rgb	= Array();
	for ($j = 0; $j < 3; $j++) {
	  $rgb[$j] = round($start[$j] + ($value/$max_value)*($end[$j]-$start[$j])); // Calculate the background color from value/max_value
	}
	return 'background-color: rgb('.$rgb[0].','.$rgb[1].','.$rgb[2].');';
}

/* Generate the differents <td></td> for a task line.
** @param	int			colCount
** @param	"%Y%m%d"	start_date
** @param	int			show
** @param	CTask		task
** @param	int			contact_id
** @param	int			aff_style
** @param	string		class
** @param	int			scale
** @param	string		hpd_string
** @param	string		percent_string
** @return	string		tds
*/ 
function generateTdTasks($colCount, $start_date, $show, $task, $contact_id, $aff_style, $class, $scale, $hpd_string, $percent_string) {
	$tds 		= '';
	$ccurrent 	= new CDate($start_date);
	for ($i = 0; $i < $colCount; $i++) {
		if ($task->task_dynamic==1)
			$tds .= '<td class="tdContentTask todo '.$class.'_c_'.$i.'" rel="_c_'.$i.'"></td>'; // Initialize to todo. It will be calculate by javascript.
		else {
			$assign	= getAssignment($ccurrent, $task, $contact_id, $scale, $show); // Get an average of the assignment of this user to this task between current date and next date
		
			switch($assign) {
				case null  				: $style	= 'background-color: #ECECEC;'; break; 			// No assign
				case ($assign <= $scale): $style	= getbackgroundColor($assign,$scale); break;	// Assign <= 100% 
				case ($assign > $scale) : $style	= 'background-color: #CC6666'; break;			// Assign > 100%
				default           		: break;
			}
			$assign = str_replace('.', '', $assign);
            $assign = str_replace(',', '.', $assign);
			$title = '';
			if($assign > 0){
				if($aff_style) $title = 'title="'.$hpd_string.'"';
				else $title = 'title="'.$percent_string.'"';
			}
			$tds .= '<td class="tdContentTask '.$class.'_c_'.$i.'" '.$title.' style="'.$style.'">'.$assign.'</td>';
		}
		nextDate($ccurrent,$show);
	}
	return $tds;
}

/* Generate the differents <td></td> for a project line.
** @param	int			colCount
** @param	string		class
** @return	string		tds
*/ 
function generateTdProjects($colCount, $class) {
	$tds ='';
	for ($i = 0; $i < $colCount; $i++) {
		$tds .= '<td class="tdContentProject todo '.$class.'_c_'.$i.'" rel="_c_'.$i.'"></td>'; // Initialize to todo. It will be calculate by javascript.
	}
	return $tds;
}

/* Generate the differents <td></td> for a user line.
** @param	int			colCount
** @return	string		tds
*/ 
function generateTdUsers($colCount) {
	$tds ='';
	for ($i = 0; $i < $colCount; $i++) {
		$tds .= '<td class="tdContentUser todo" rel="_c_'.$i.'"></td>'; 	// Initialize to todo. It will be calculate by javascript.
	}
	return $tds;
}

/* Generate a <a href></a> filter link
** @param	string		filter_name	Can be "contact", "company" or "project"
** @param	string		filter_id
** @param	string		string		The string that we will be display as link
** @param	string		color		style color
** @return	string		html link
*/ 
function generateFilterLink($filter_name,$filter_id,$string,$color='auto') {
	$res  = '<a onclick="javascript:$(\'select[name='.$filter_name.'_id]\').val(\''.$filter_id.'\'); '
			.'$(\'#submitButton\').click();" '
			.'onmouseenter="javascript:$(this).append($(\'<img style=\\\'float:right;\\\' src=\\\'./modules/resource_m/images/filter.png\\\' height=\\\'12\\\' width=\\\'12\\\' />\'));" '
			.'onMouseOut="$(this).find(\'img:last\').remove();" '
			.'style="cursor: pointer; color: '.$color.';">'.$string.'</a>';
	return $res;
}

/* Generate a <a href></a> date range link
** @param	int			sdate		New start date
** @param	int			edate		New end date
** @param	string		string		The string that we will be display as link
** @return	string		html link
*/ 
function generateRangeLink($sdate,$edate,$string) {
	$res  = '<a onclick="javascript:$(\'input#start_date\').val(\''.$sdate.'\'); '
			.'$(\'input#end_date\').val(\''.$edate.'\'); '
			.'$(\'#submitButton\').click();" '
			.'style="cursor: pointer;">'.$string.'</a>';
	return $res;
}
?>