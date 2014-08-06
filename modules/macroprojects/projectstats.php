<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}



function getTasksStats($macroproject){
	$macroproject_id = $macroproject['macroproject_id'];
	$q = new DBQuery();
	$q->addTable('projects', 'pr');
	$q->leftJoin('tasks', 't','task_project = project_id');
	if (!empty($macroproject_id)){		
		$q->addWhere(makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'project_id=')); //'project_id = ' . (int)$project_id);
	}
	// echo $q->prepareSelect() .'<br/>';
	$pom = !empty($macroproject)?recoverProjects($macroproject_id):0;
	if(count($pom) > 0){
		$all_tasks = $q->loadList();

	}
	$taskStats = array();
	
	
	$tasks['hours'] = 0;
	$tasks['inprogress'] = array();
	$tasks['completed'] = array();
	$tasks['pending'] = array();
	$tasks['overdue'] = array();
	$tasks['not_started'] = array();
	
	if(!empty($all_tasks)){
		foreach ($all_tasks as $task)
		{
			$tasks['percentage'] += intval($task['task_percent_complete']);
			if ($task['task_percent_complete'] == 100)
				$tasks['completed'][] = & $task;
			else
			{
				if ($task['task_end_date'] < date('Y-m-d'))
					$tasks['overdue'][] = & $task;
				else if ($task['task_percent_complete'] == 0){
					if($task['task_start_date'] < date('Y-m-d'))
						$tasks['pending'][] = & $task;
					else
						$tasks['not_started'][] = & $task;
				}else
					$tasks['inprogress'][] = & $task;
			}

		}
	}
/*
	$ontime = round(100 * (1 - (count($tasks['overdue']) / count($all_tasks)) - (count($tasks['completed']) / count($all_tasks))));
	$progressStats = array();
	$progressStats['completed'] = round(count($tasks['completed']) / count($all_tasks) * 100);
	$progressStats['inprogress'] = round(count($tasks['inprogress']) / count($all_tasks) * 100);
	$progressStats['pending'] = round(count($tasks['pending']) / count($all_tasks) * 100);

	$timeStats = array();
	$timeStats['completed'] = round(count($tasks['completed']) / count($all_tasks) * 100);
	$timeStats['ontime'] =  round(100 * (1 - (count($tasks['overdue']) / count($all_tasks)) - (count($tasks['completed']) / count($all_tasks))));
	$timeStats['overdue'] = round(count($tasks['overdue']) / count($all_tasks) * 100);
*/
	if(!empty($macroproject)){
		$taskStats['id'] = $macroproject['macroproject_id'];
		$taskStats['name'] = $macroproject['macroproject_name'];
		$taskStats['company'] = $macroproject['company_name'];
		$taskStats['company_id'] = $macroproject['macroproject_company'];
		$taskStats['color'] = $macroproject['macroproject_color_identifier'];
	}
	$taskStats['completion_percentage'] = 	!empty($all_tasks)?round($tasks['percentage']/count($all_tasks)):0;
	$taskStats['completed'] = count($tasks['completed']);
	$taskStats['p_completed'] = !empty($all_tasks)?round(count($tasks['completed']) / count($all_tasks) * 100):0;
	$taskStats['inprogress'] = count($tasks['inprogress']);
	$taskStats['p_inprogress'] = !empty($all_tasks)?round(count($tasks['inprogress']) / count($all_tasks) * 100):0;
	$taskStats['onschedule'] = count($tasks['inprogress']) + count($tasks['completed']) + count($tasks['not_started']);
	$taskStats['p_onschedule'] =  !empty($all_tasks)?round($taskStats['onschedule'] / count($all_tasks) * 100):0;
	$taskStats['pending'] = count($tasks['pending']);
	$taskStats['p_pending'] = !empty($all_tasks)?round(count($tasks['pending']) / count($all_tasks) * 100):0;
	$taskStats['overdue'] = count($tasks['overdue']);
	$taskStats['p_overdue'] = !empty($all_tasks)?round(count($tasks['overdue']) / count($all_tasks) * 100):0;
	$taskStats['not_started'] = count($tasks['not_started']);
	$taskStats['p_not_started'] = !empty($all_tasks)?round(count($tasks['not_started']) / count($all_tasks) * 100):0;
	$taskStats['started'] = $taskStats['overdue'] + $taskStats['pending'] + $taskStats['inprogress'];
	$taskStats['p_started'] = !empty($all_tasks)?round($taskStats['started'] /count($all_tasks) * 100):0;
	 $taskStats['total'] = count($all_tasks);
	 $taskStats['ontime'] = $taskStats['total'] - ($taskStats['completed'] + $taskStats['overdue']);
	 $taskStats['p_ontime'] = 100 - ($taskStats['p_completed'] + $taskStats['p_overdue']);
	

	//echo 'macroproject id : '.$macroproject_id .'<br/>';
	// echo json_encode($progressStats) .'<br/>';
	 //echo json_encode($timeStats) .'<br/>';
	return $taskStats;
	//echo json_encode($taskStats['0']) .'<br/>';
}

function getProjectsStats($macroproject){
	$macroproject_id = $macroproject['macroproject_id'];
	$q = new DBQuery();
	$q->addTable('projects', 'pr');
	
	if (!empty($macroproject_id)){
		$q->addWhere(makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'project_id='));// 'project_id = ' . (int)$project_id);
	}
	$pom = !empty($macroproject)?recoverProjects($macroproject_id):0;
	if(count($pom) > 0){
		$all_projects = $q->loadList();
	}

	$projects['proposed'] = array();
	$projects['inplanning'] = array();
	$projects['inprogress'] = array();
	$projects['active'] = array();
	$projects['active_completed'] = array();
	$projects['onhold'] = array();
	$projects['completed'] = array();
	$projects['template'] = array();
	$projects['archived'] = array();
	$projects['undefined'] = array();
	$projects['overdue'] = array();
	$projects['pending'] = array();
	$projects['not_started'] = array();
	
	if(!empty($all_projects)){
		foreach ($all_projects as $project)
		{
			if ($project['project_status']==5)
				$projects['completed'][] = & $project;
			else if($project['project_status']==3 || 0)
			{
				$projects['active'][] = & $project;
				$projects['completion_precentage'] += intval($project['project_percent_complete']);
				if (($project['project_percent_complete'] == 100))
					$projects['active_completed'][] = & $project; 
				else if ($project['project_end_date'] < date('Y-m-d'))
					$projects['overdue'][] = & $project;
				else if ($project['project_percent_complete'] == 0){
					if($project['project_start_date'] < date('Y-m-d'))
						$projects['pending'][] = & $project;
					else
						$projects['not_started'] = & $project;
				}else
					$projects['inprogress'][] = & $project;
			}
			else if($project['project_status']==2)
				$projects['inplanning'][] = & $project;
			else if($project['project_status']==1)
				$projects['proposed'][] = & $project;
			else if($project['project_status']==4)
				$projects['onhold'][] = & $project;
			else if($project['project_status']==6)
				$projects['template'][] = & $project;
			else if($project['project_status']==7)
				$projects['archived'][] = & $project;
			else
				$projects['undefined'][] = & $project;
		}
	}
	$projectStats = array();
	if(!empty($macroproject)){
		$projectStats['id'] = $macroproject['macroproject_id'];
		$projectStats['name'] = $macroproject['macroproject_name'];
		$projectStats['company'] = $macroproject['company_name'];
		$projectStats['company_id'] = $macroproject['macroproject_company'];
		$projectStats['color'] = $macroproject['macroproject_color_identifier'];
	}
	 $projectStats['total'] = count($all_projects);
	$projectStats['completion_percentage'] = !empty($projects['active'])?round($projects['completion_precentage'] / count($projects['active'])):0;
	$projectStats['completed'] = count($projects['completed']) + count($projects['active_completed']);
	$projectStats['p_completed'] = !empty($all_projects)?round(count($projects['completed']) / count($all_projects) * 100):0;
	$projectStats['p_completed_toactive'] = !empty($projects['active'])?round($projectStats['completed'] / (count($projects['active'])+count($projects['completed'])) * 100):0;
	$projectStats['inprogress'] = count($projects['inprogress']);
	$projectStats['onschedule'] = count($projects['inprogress']) + count($projects['active_completed']) + count($projects['not_started']);
	$projectStats['p_onschedule'] = !empty($projects['active'])?round($projectStats['onschedule']/count($projects['active']) * 100):0;
	$projectStats['p_inprogress'] = !empty($projects['active'])?round(count($projects['inprogress']) / count($projects['active']) * 100):0;
	$projectStats['active'] = count($projects['active']);
	$projectStats['p_active'] = !empty($all_projects)?round(count($projects['active']) / count($all_projects) * 100):0;
	$projectStats['active_completed'] = count($projects['active_completed']);
	$projectStats['p_active_completed'] = !empty($projects['active'])?round(count($projects['active_completed']) / count($projects['active']) * 100):0;
	$projectStats['pending'] = count($projects['pending']);
	$projectStats['p_pending'] = !empty($projects['active'])?round(count($projects['pending']) / count($projects['active']) * 100):0;
	$projectStats['overdue'] = count($projects['overdue']);
	$projectStats['p_overdue'] = !empty($projects['active'])?round(count($projects['overdue']) / count($projects['active']) * 100):0;
	$projectStats['not_started'] = count($projects['not_started']);
	$projectStats['p_not_started'] = !empty($projects['active'])?round(count($projects['not_started']) / count($projects['active']) * 100):0;
	$projectStats['started'] = $projectStats['overdue'] + $projectStats['pending'] + $projectStats['inprogress'];
	$projectStats['p_started'] = !empty($projects['active'])?round($projectStats['started'] / count($projects['active']) * 100):0;
		
	 $projectStats['ontime'] = $projectStats['active'] - ($projectStats['active_completed'] + $projectStats['overdue']);
	$projectStats['p_ontime'] = 100 - ($projectStats['p_active_completed'] + $projectStats['p_overdue']);
	 $projectStats['p_ontime'] = !empty($all_projects)?round(count($projectStats['ontime'])/count($all_projects) * 100):0;
	 $projectStats['proposed'] = count($projects['proposed']);
	 $projectStats['p_proposed'] = !empty($all_projects)?round(count($projects['proposed']) / count($all_projects) * 100):0;
	 $projectStats['inplanning'] = count($projects['inplanning']);
	 $projectStats['p_inplanning'] = !empty($all_projects)?round(count($projects['inplanning']) / count($all_projects) * 100):0;
	 $projectStats['onhold'] = count($projects['onhold']);
	 $projectStats['p_onhold'] = !empty($all_projects)?round(count($projects['onhold']) / count($all_projects) * 100):0;
	 $projectStats['template'] = count($projects['template']);
	 $projectStats['p_template'] = !empty($all_projects)?round(count($projects['template']) / count($all_projects) * 100):0;
	 $projectStats['archived'] = count($projects['archived']);
	 $projectStats['p_archived'] = !empty($all_projects)?round(count($projects['archived']) / count($all_projects) * 100):0;
	 $projectStats['undefined'] = count($projects['undefined']);
	 $projectStats['p_undefined'] = !empty($all_projects)?round(count($projects['undefined']) / count($all_projects) * 100):0;
	
	return $projectStats;
}



?>