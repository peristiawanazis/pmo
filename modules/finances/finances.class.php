<?php /* FINANCES finances.class.php, v 0.1.0 2012/07/20 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	PHP function page of the Finances module.
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

class CBudget extends CDpObject {
	var $budget_id = NULL;
	var $task_id = NULL;
	var $Tax = "0.00";
	var $only_financial = 0;
	var $display_tax = 0;
	var $equipment_investment = "0.00";
	var $intangible_investment = "0.00";
	var $service_investment = "0.00";
	var $equipment_operation = "0.00";
	var $intangible_operation = "0.00";
	var $service_operation = "0.00";
	
	
	function CBudget() {
		$this->CDpObject('budget', 'budget_id');
	}

	function loadFromTask($task_id) {
		if(countChildren($task_id) != 0) return -1;
		$q = new DBQuery;
		$q->addTable('budget');
		$q->addQuery('budget_id');
		$q->addWhere('task_id = ' . $task_id );
		$sql = $q->prepare();
		$q->clear();
		$budget_id = db_loadResult($sql);
		if($budget_id == null) {
			$this->Tax = mostCommonTax();
			return 0;
		}
		else $this->load($budget_id);
		return 1;
	}
	
	function store() {
		if(countChildren($this->task_id) != 0) return false;
		if($this->task_id <= 0) return false;
		$q = new DBQuery;
		$q->addTable('budget');
		$q->addQuery('budget_id');
		$q->addWhere('task_id = ' . $this->task_id );
		$sql = $q->prepare();
		$q->clear();
		$budget_id = db_loadResult($sql);
		if($budget_id == null) {
			$q->addTable('budget');
			$q->addInsert('task_id', $this->task_id);
			db_exec($q->prepare());
			$budget_id = db_insert_id();
		}
		$q = new DBQuery;
		$q->addTable('budget', 'b');
		$q->addUpdate('Tax',$this->Tax);
		$q->addUpdate('only_financial',$this->only_financial);
		$q->addUpdate('display_tax',$this->display_tax);
		$q->addUpdate('equipment_investment',$this->equipment_investment);
		$q->addUpdate('intangible_investment',$this->intangible_investment);
		$q->addUpdate('service_investment',$this->service_investment);
		$q->addUpdate('equipment_operation',$this->equipment_operation);
		$q->addUpdate('intangible_operation',$this->intangible_operation);
		$q->addUpdate('service_operation',$this->service_operation);
		$q->addWhere('task_id = '.$this->task_id);
		$sql = $q->prepare();
		$q->clear();
		db_exec($sql);
	}
	
	function get_equipment_investment($mult = 1, $symbol = "", $sep = " ") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->equipment_investment*$tax*$mult,2,'.',$sep).$symbol; }
	function get_intangible_investment($mult = 1, $symbol = "", $sep = " ") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->intangible_investment*$tax*$mult,2,'.',$sep).$symbol; }
	function get_service_investment($mult = 1, $symbol = "", $sep = " ") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->service_investment*$tax*$mult,2,'.',$sep).$symbol; }
	function get_equipment_operation($mult = 1, $symbol = "", $sep = " ") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->equipment_operation*$tax*$mult,2,'.',$sep).$symbol; }
	function get_intangible_operation($mult = 1, $symbol = "", $sep = " ") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->intangible_operation*$tax*$mult,2,'.',$sep).$symbol; }
	function get_service_operation($mult = 1, $symbol = "", $sep = " ") { $tax =  1; if($this->display_tax) $tax += ($this->Tax/100); return number_format($this->service_operation*$tax*$mult,2,'.',$sep).$symbol; }

	
	function get_investment($mult = 1, $symbol = "", $sep = " ") {
		$res =  $this->get_equipment_investment($mult,"","");
		$res += $this->get_intangible_investment($mult,"","");
		$res += $this->get_service_investment($mult,"","");
		return number_format($res,2,'.',$sep).$symbol;
	}
	
	function get_operation($mult = 1, $symbol = "", $sep = " ") {
		$res =  $this->get_equipment_operation($mult,"","");
		$res += $this->get_intangible_operation($mult,"","");
		$res += $this->get_service_operation($mult,"","");
		return number_format($res,2,'.',$sep).$symbol;
	}
	
	function get_total($mult = 1, $symbol = "", $sep = " ") {
		$res =  $this->get_investment($mult,"","");
		$res += $this->get_operation($mult,"","");
		return number_format($res,2,'.',$sep).$symbol;
	}
	
	/* Return the best currency display mode for this budget
	*/ 
	function bestCurrency() {
		$ret = 2;
		if($this->equipment_investment != 0){
			if($this->equipment_investment <=1000000) $ret=1;
			if($this->equipment_investment <=1000) return 0;
		}
		if($this->intangible_investment != 0){
			if($this->intangible_investment <=1000000) $ret=1;
			if($this->intangible_investment <=1000) return 0;
		}
		if($this->service_investment != 0){
			if($this->service_investment <=1000000) $ret=1;
			if($this->service_investment <=1000) return 0;
		}
		if($this->equipment_operation != 0){
			if($this->equipment_operation <=1000000) $ret=1;
			if($this->equipment_operation <=1000) return 0;
		}
		if($this->intangible_operation != 0){
			if($this->intangible_operation <=1000000) $ret=1;
			if($this->intangible_operation <=1000) return 0;
		}
		if($this->service_operation != 0){
			if($this->service_operation <=1000000) $ret=1;
			if($this->service_operation <=1000) return 0;
		}
		return $ret;
	}
	
	function addTax($amount){
		return $amount + $amount * $this->Tax/100;
	}
	
	function isNull(){
		if ($this->equipment_investment + $this->intangible_investment + $this->service_investment + $this->equipment_operation + $this->intangible_operation + $this->service_operation > 0) return false;
		else return true;
	}
}

/* Update one value in data base
** @param	string	var
** @param	float	val
** @param	bool	tax
*/ 
function updateValue($var, $val, $tax) {
	global $AppUI;
	if($val == null) $val = "0.00";
	if(!is_numeric($val)) return -1;	
	$tab = explode('_',$var);
	if(count($tab) != 5) return -1;
	$project_id = $tab[1];
	$task_id = $tab[3];
	$type = $tab[4];
	if (!getPermission('tasks', 'edit', $task_id)) {
		$AppUI->redirect("m=public&a=access_denied");
		return -1;
	}
	$q = new DBQuery();
	$q->addTable('budget');
	$q->addQuery('COUNT(*)');
	$q->addWhere('task_id = '.$task_id);
	
	if($q->loadResult() == 0){
		$q->clear();
		$q->addTable('budget');
		$q->addInsert('task_id', $task_id);
		$q->addInsert('Tax', mostCommonTax());
		db_exec($q->prepare());
	}
	if($tax){
		$q->clear();
		$q->addTable('budget');
		$q->addQuery('Tax');
		$q->addWhere('task_id = '.$task_id);
		$val = number_format($val/($q->loadResult()/100 + 1),2,'.','');
	}
	$q->clear();
	$q->addTable('budget');
	switch($type){
		case "ei": $q->addUpdate('equipment_investment', $val); break;
		case "ii": $q->addUpdate('intangible_investment', $val); break;
		case "si": $q->addUpdate('service_investment', $val); break;
		case "eo": $q->addUpdate('equipment_operation', $val); break;
		case "io": $q->addUpdate('intangible_operation', $val); break;
		case "so": $q->addUpdate('service_operation', $val); break;
		default: return -1;
	}
	$q->addWhere('task_id = '.$task_id);
	return $q->exec();
}

/* Loads the various macroprojects according to filters.
** @param	int		macroproject_type
** @param	int		macroproject_status
** @return	CMacroProject[]	macroprojects
*/ 
function loadMacroProjects($macroproject_type = 0, $macroproject_status = 0) {
	$q = new DBQuery;
	$q->addTable('macroprojects', 'p');
	$q->addQuery('DISTINCT(p.macroproject_id)');
	if($macroproject_type){
		$q->addWhere('p.macroproject_type = ' . ($macroproject_type - 1));
	}
	if($macroproject_status){
		$q->addWhere('p.macroproject_status = ' . ($macroproject_status - 1));
	}
	$q->addOrder('p.macroproject_name DESC');
	$macroprojectsId = 	$q->loadColumn();
	$macroprojects 	= 	Array();
	foreach($macroprojectsId as $key => $row)
	{
		$macroprojects[$key] = new CMacroProject();
		$macroprojects[$key]->load($row);
	}
	return $macroprojects;
}

/* Loads the various projects according to filters.
** @param	int		company_id
** @param	int		department_id
** @param	int		project_type
** @param	int		project_status
** @return	CProject[]	projects
*/ 
function loadProjects($company_id, $department_id, $project_type = 0, $project_status = 0) {
	$q = new DBQuery;
	$q->addTable('projects', 'p');
	$q->addQuery('DISTINCT(p.project_id)');
	if ($company_id)
		$q->addWhere('(project_company = '.$company_id.' OR project_company_internal = '.$company_id.')');
	elseif ($department_id == -1){
		$q->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
		$q->addWhere('p.project_id NOT IN (SELECT pd.project_id FROM '.dPgetConfig('dbprefix', '').'project_departments pd)');
	}elseif ($department_id){
		$q->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
		$q->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
		$q->addWhere('(pd.department_id = '.$department_id.' OR d.dept_parent = '.$department_id.')');
	}
	if($project_type){
		$q->addWhere('p.project_type = ' . ($project_type - 1));
	}
	if($project_status){
		$q->addWhere('p.project_status = ' . ($project_status - 1));
	}else{
		$q->addWhere('p.project_status <= 4');    // All non Archived/Finished/Model projects
	}
	$q->addOrder('p.project_name ASC');
	$projectsId = 	$q->loadColumn();
	$projects 	= 	Array();
	foreach($projectsId as $key => $row)
	{
		$projects[$key] = new CProject();
		$projects[$key]->load($row);
	}
	return $projects;
}

/* Loads the various tasks of this macroproject and corresponding to the years.
** @param	int			mp_id
** @param	int[]		years
** @return	CTask[]	tasks
*/ 
function loadMPTasks($mp_id, $years) {
	$q = new DBQuery;
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(task_id)');
	$q->addWhere(/*'('.*/makeWhereClauseEachProjectOfAMacroProject($mp_id, 'task_project = ')/*.')'*/);
	$q->addWhere('task_parent = t.task_id');
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');
	$ids = $q->loadColumn(); 				// here you have all top level task's ids
	
	$add_ids = Array();
	foreach($ids as $id){
		if(haveRangedChildren($id, $years))
			$add_ids[] = $id;
	}
	
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(task_id)');
	$q->addWhere(/*'('.*/makeWhereClauseEachProjectOfAMacroProject($mp_id, 'task_project = ')/*.')'*/);
	$q->addWhere('task_parent = t.task_id');
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_project, task_id, task_start_date , task_end_date');
	if ($years[0] != 1) {
		$where = '( false ';
		foreach($add_ids as $add_id) {
			$where .= ' OR task_id='.$add_id;
		}
		foreach($years as $year) {
			$where .= ' OR ( task_start_date >= '.$year.'0101 AND task_start_date <= '.$year.'1231 )';
		}
		$where .= ')';
		$q->addWhere($where);
	}
	$ids = $q->loadColumn(); 				// here you have all top level task's ids
	
	$tasks_ids = Array();

	foreach ($ids as $id) {				 	// add the id to the list and all is childs too
		$tasks_ids[] = $id;
		$child_ids = getAllChildrenIds($id,$years);
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

/* Loads the various tasks of this project and corresponding to the years.
** @param	int			project_id
** @param	int[]		years
** @return	CTask[]	tasks
*/ 
function loadTasks($project_id, $years) {
	$q = new DBQuery;
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(task_id)');
	$q->addWhere('task_project = '.$project_id);
	$q->addWhere('task_parent = t.task_id');
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');
	$ids = $q->loadColumn(); 				// here you have all top level task's ids
	
	$add_ids = Array();
	foreach($ids as $id){
		if(haveRangedChildren($id, $years))
			$add_ids[] = $id;
	}
	
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(task_id)');
	$q->addWhere('task_project = '.$project_id);
	$q->addWhere('task_parent = t.task_id');
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');
	if ($years[0] != 1) {
		$where = '( false ';
		foreach($add_ids as $add_id) {
			$where .= ' OR task_id='.$add_id;
		}
		foreach($years as $year) {
			$where .= ' OR ( task_start_date >= '.$year.'0101 AND task_start_date <= '.$year.'1231 )';
		}
		$where .= ')';
		$q->addWhere($where);
	}
	$ids = $q->loadColumn(); 				// here you have all top level task's ids
	
	$tasks_ids = Array();

	foreach ($ids as $id) {				 	// add the id to the list and all is childs too
		$tasks_ids[] = $id;
		$child_ids = getAllChildrenIds($id,$years);
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

/* Return true if the corresponding task have at least one child which have start_date corresponding to years
** @param 	int		task_id
** @param 	int[]	years 
** @return 	boolean
*/
function haveRangedChildren($task_id, $years){
	if(countChildren($task_id, $years))
		return true;
	else {
		$children = getChildrenIds($task_id);
		foreach($children as $child){
			if(haveRangedChildren($child, $years))
				return true;
		}
	}
	return false;
}

/* Return the most common value of Tax in database
** @return	float	Tax
*/ 
function mostCommonTax() {
	$q = new DBQuery;
	$q->addTable('budget', 'b');
	$q->addQuery('Tax, COUNT(Tax)');
	$q->addWhere('Tax != 0.00');
	$q->addGroup('Tax');
	$q->addOrder('COUNT(Tax) DESC LIMIT 0,1');
	return $q->loadResult();
}

/* Return the number of children of this task
** @param	int		task_id
** @param	int[]	years(optionnal, default: null)
** @return	int		children number
*/ 
function countChildren($task_id, $years=null){
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('COUNT(DISTINCT(t.task_id))');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	if ($years != null && $years[0] != 1) {
		$where = '( false ';
		foreach($years as $year) {
			$where .= ' OR ( task_start_date >= '.$year.'0101 AND task_start_date <= '.$year.'1231 )';
		}
		$where .= ')';
		$q->addWhere($where);
	}
	return $q->loadResult(); 
}


/* Return the number of last descendant children of this task
** @param	int		task_id
** @return	int		children number
*/ 
function countLastChildren($task_id){
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	$ids = $q->loadColumn(); 
	
	$res = 0;
	foreach($ids as $id){
		if(countChildren($id) == 0)
			$res += 1;
		else
			$res += countLastChildren($id);
	}
	return $res;
}

/* Return the children ids of this task
** @param	int		task_id
** @return	int[]	children id
*/ 
function getChildrenIds($task_id){
	$q = new DBQuery; 
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	return $q->loadColumn(); 
}

/* Recursive function that return an array of all children's ID of a task
** @param	int			task_id
** @param	int[]	years(optionnal, default: null)
** @return	int[]		child_ids
*/ 
function getAllChildrenIds($task_id, $years=null) {
	$q = new DBQuery();
	$add_ids = Array();
	if ($years != null && $years[0] != 1) {
		$q->addTable('tasks', 't');
		$q->addQuery('DISTINCT(task_id)');
		$q->addWhere('task_parent != t.task_id');
		$q->addWhere('task_parent = '.$task_id);
		$q->addWhere('task_status >= 0');
		$q->addOrder('task_start_date , task_end_date, task_id');
		$ids = $q->loadColumn();
		foreach($ids as $id){
			if(haveRangedChildren($id, $years))
				$add_ids[] = $id;
		}
	}


	$q->clear();
	$q->addTable('tasks', 't');
	$q->addQuery('DISTINCT(t.task_id)');
	$q->addWhere('task_parent != t.task_id');
	$q->addWhere('task_parent = '.$task_id);
	$q->addWhere('task_status >= 0');
	$q->addOrder('task_start_date , task_end_date, task_id');
	if ($years != null && $years[0] != 1) {
		$where = '( false ';
		foreach($add_ids as $add_id) {
			$where .= ' OR task_id='.$add_id;
		}
		foreach($years as $year) {
			$where .= ' OR ( task_start_date >= '.$year.'0101 AND task_start_date <= '.$year.'1231 )';
		}
		$where .= ')';
		$q->addWhere($where);
	}
	$child_ids = $q->loadColumn();
	if ($child_ids == null) {
		return Array();
	} else {
		foreach ($child_ids as $id) {
			$ret[] = $id;
			$c = getAllChildrenIds($id, $years);
			if ($c != Array()) {
				foreach ($c as $i)
					$ret[] = $i;
			}
		}
		return $ret;
	}
}
