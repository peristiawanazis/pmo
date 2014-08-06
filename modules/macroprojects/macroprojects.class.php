<?php /* MACRO_PROJECTS macroprojects.class.php, v 0.1.0 2012/05/30 */
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

require_once ($AppUI->getSystemClass ('dp'));
require_once ($AppUI->getLibraryClass('PEAR/Date'));
require_once ($AppUI->getModuleClass('tasks'));
require_once ($AppUI->getModuleClass('companies'));
require_once ($AppUI->getModuleClass('departments'));
require_once ($AppUI->getModuleClass('projects'));

/**
 * The macroproject Class
 */
class CMacroProject extends CDpObject {
	var $macroproject_id = NULL;
	var $macroproject_company = NULL;
	var $macroproject_company_internal = NULL;
	var $macroproject_department = NULL;
	var $macroproject_name = NULL;
	var $macroproject_short_name = NULL;
	var $macroproject_owner = NULL;
	var $macroproject_url = NULL;
	var $macroproject_demo_url = NULL;
	var $macroproject_start_date = NULL;
	var $macroproject_end_date = NULL;
	var $macroproject_actual_end_date = NULL;
	var $macroproject_status = NULL;
	var $macroproject_percent_complete = NULL;
	var $macroproject_color_identifier = NULL;
	var $macroproject_description = NULL;
	var $macroproject_target_budget = NULL;
	var $macroproject_actual_budget = NULL;
	var $macroproject_creator = NULL;
	var $macroproject_private = NULL;
	var $macroproject_departments= NULL;
	var $macroproject_contacts = NULL;
	var $macroproject_priority = NULL;
	var $macroproject_type = NULL;
	
	function CMacroProject() {
		$this->CDpObject('macroprojects', 'macroproject_id');
	}
	
	function check() {
		// ensure changes of state in checkboxes is captured
		$this->macroproject_private = intval($this->macroproject_private);
		// Make sure macroproject_short_name is the right size (issue with encoded characters)
		if (mb_strlen($this->macroproject_short_name) > 10) {
			$this->macroproject_short_name = mb_substr($this->macroproject_short_name, 0, 10);
		}
		// Make sure empty dates are nulled.  Cannot save an empty date.
		if (empty($this->macroproject_end_date)) {
			$this->macroproject_end_date = null;
		}
		
		return null; // object is ok
	}
	
	function load($oid=null, $strip=true) {
		$result = parent::load($oid, $strip);
		if ($result && $oid) {
			$working_hours = ((dPgetConfig('daily_working_hours')) 
			                  ? dPgetConfig('daily_working_hours'):8);
			
			$q = new DBQuery;
		$q->addTable('macroprojects', 'mp');
		$q->addQuery('mp.macroproject_percent_complete');
		$q->addWhere('macroproject_id =' . $oid);

		$macroprojects = $q->loadList();
		$q->exec();
		$q->clear();
		}
		return $result;
	}
	
	// overload canDelete
	function canDelete(&$msg, $oid=null) {
		// TODO: check if user permissions are considered when deleting a project
		global $AppUI;
		
		return getPermission('macroprojects', 'delete', $oid); //modification effectuée dans include/permission et classes/permissions
		
		// NOTE: I uncommented the dependencies check since it is
		// very anoying having to delete all tasks before being able
		// to delete a project.
		
		/*
		$tables[] = array('label' => 'Tasks', 'name' => 'tasks', 'idfield' => 'task_id', 
		                  'joinfield' => 'task_project');
		// call the parent class method to assign the oid
		return CDpObject::canDelete($msg, $oid, $tables);
		*/
	}
	
	function delete() {
		$this->load($this->macroproject_id);
		addHistory('macroprojects', $this->macroproject_id, 'delete', $this->macroproject_name, 
		           $this->macroproject_id);
		$q = new DBQuery;
		
		// remove the macroproject-contacts and macroproject-departments map
		$q->setDelete('macroproject_contacts');
		$q->addWhere('macroproject_id =' . $this->macroproject_id);
		$q->exec();
		$q->clear();
		$q->setDelete('macroproject_departments');
		$q->addWhere('macroproject_id =' . $this->macroproject_id);
		$q->exec();
		$q->clear();
		// remove the macroproject_project and macroproject_macroproject map
		$q->setDelete('macroproject_project');
		$q->addWhere('macroproject_id =' . $this->macroproject_id);
		$q->exec();
		$q->clear();
		$q->setDelete('macroproject_macroproject');
		$q->addWhere('macroproject_father =' . $this->macroproject_id);
		$q->exec();
		$q->clear();
		$q->setDelete('macroprojects');
		$q->addWhere('macroproject_id =' . $this->macroproject_id);
		
		$result = ((!$q->exec()) ? db_error() : NULL);
		$q->clear();
		return $result;
	}
	
	/**
	**	Overload of the dpObject::getAllowedRecords 
	**	to ensure that the allowed macroprojects are owned by allowed companies.
	**/

	function getAllowedRecords($uid, $fields='*', $macroorderby='', $index=null, $extra=null) {
		$oCpy = new CCompany ();
		
		$aCpies = $oCpy->getAllowedRecords ($uid, 'company_id, company_name');
		
		$buffer = ((count($aCpies)) 
		           ? ('(macroproject_company IN (' . implode(',', array_keys($aCpies)) . '))') 
		           : '1 = 0');
		$extra['where'] = ((($extra['where'] != '') ? ($extra['where'] . ' AND ') : '') 
		                   . $buffer);

		return parent::getAllowedRecords ($uid, $fields, $macroorderby, $index, $extra);
				
	}
	
	function getAllowedSQL($uid, $index=null) {
		$oCpy = new CCompany ();
		
		$where = $oCpy->getAllowedSQL ($uid, 'macroproject_company');
		$project_where = parent::getAllowedSQL($uid, $index);
		return array_merge($where, $project_where);
	}
	
	function setAllowedSQL($uid, &$query, $index=null, $key=null) {
		$oCpy = new CCompany;
		parent::setAllowedSQL($uid, $query, $index, $key);
		$oCpy->setAllowedSQL($uid, $query, ((($key) ? ($key . '.') : '') .'macroproject_company'));
	}
	
	/**
	 *	Overload of the dpObject::getDeniedRecords 
	 *	to ensure that the macroprojects owned by denied companies are denied.
	 *
	 */
 	function getDeniedRecords($uid) {
		$aBuf1 = parent::getDeniedRecords ($uid);
		
		$oCpy = new CCompany ();
		// Retrieve which macroprojects are allowed due to the company rules 
		$aCpiesAllowed = $oCpy->getAllowedRecords ($uid, 'company_id,company_name');
		
		$q = new DBQuery;
		$q->addTable('macroprojects');
		$q->addQuery('macroproject_id');
		if (count($aCpiesAllowed)) {
			$q->addWhere('NOT (macroproject_company IN (' . implode (',', array_keys($aCpiesAllowed)) 
			             . '))');
		}
		$sql = $q->prepare();
		$q->clear();
		$aBuf2 = db_loadColumn ($sql);
		
		return array_merge ($aBuf1, $aBuf2); 
		
	} 
	
	function getAllowedMacroProjectsInRows($userId) {
		$q = new DBQuery;
		$q->addQuery('macroproject_id, macroproject_status, macroproject_name, macroproject_description' 
		             . ', macroproject_short_name');
		$q->addTable('macroprojects');                     
		$q->addOrder('macroproject_short_name');
		$this->setAllowedSQL($userId, $q);
		$allowedMacroProjectRows = $q->exec();
		
		return $allowedMacroProjectRows;
	}
	
	function getAssignedMacroProjectsInRows($userId) {
		$q = new DBQuery;
		$q->addQuery('macroproject_id, macroproject_status, macroproject_name, macroproject_description' 
		             . ', macroproject_short_name');
		$q->addTable('macroprojects');
		//$q->addJoin('tasks', 't', 't.task_project = project_id');
		//$q->addJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
		//$q->addWhere('ut.user_id = ' . $userId);
		$q->addGroup('macroproject_id');                     
		$q->addOrder('macroproject_name');
		$this->setAllowedSQL($userId, $q);
		$allowedMacroProjectRows = $q->exec();
		
		return $allowedMacroProjectRows;
	}
	
	/* Retrieve tasks with latest task_end_dates within given project
	 * @param int Project_id
	 * @param int SQL-limit to limit the number of returned tasks
	 * @return array List of criticalTasks
	 */
	function getCriticalTasks($macroproject_id=NULL, $limit=1) {
		$macroproject_id = !empty($macroproject_id) ? $macroproject_id : $this->macroproject_id;
		$q = new DBQuery;
		$q->addTable('tasks');
		if ($macroproject_id) {
			$q->addWhere(makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project = '));
		}
		$q->addWhere("!isnull(task_end_date) AND task_end_date !=  '0000-00-00 00:00:00'");
		$q->addOrder('task_end_date DESC');
		$q->setLimit($limit);
		
		return $q->loadList();
	}
	
	function store() {
		$this->dPTrimAll();
        
		$msg = $this->check();
		if ($msg) {
			return get_class($this) . '::store-check failed - ' . $msg;
		}
		
		if ($this->macroproject_id) {
			$ret = db_updateObject('macroprojects', $this, 'macroproject_id', false);
			addHistory('macroprojects', $this->macroproject_id, 'update', $this->macroproject_name, 
			           $this->macroproject_id);
		} else {
			$ret = db_insertObject('macroprojects', $this, 'macroproject_id');
			addHistory('macroprojects', $this->macroproject_id, 'add', $this->macroproject_name, 
			           $this->macroproject_id);
		}
		
		//split out related departments and store them seperatly.
		$q = new DBQuery;
		$q->setDelete('macroproject_departments');
		$q->addWhere('macroproject_id=' . $this->macroproject_id);
		$q->exec();
		$q->clear();
		if ($this->macroproject_departments) {
			$departments = explode(',',$this->macroproject_departments);
			foreach ($departments as $department) {
				$q->addTable('macroproject_departments');
				$q->addInsert('macroproject_id', $this->macroproject_id);
				$q->addInsert('department_id', $department);
				$q->exec();
				$q->clear();
			}
		}
		
		//split out related contacts and store them seperatly.
		$q->setDelete('macroproject_contacts');
		$q->addWhere('macroproject_id=' . $this->macroproject_id);
		$q->exec();
		$q->clear();
		if ($this->macroproject_contacts) {
			$contacts = explode(',',$this->macroproject_contacts);
			foreach ($contacts as $contact) {
				if ($contact) {
					$q->addTable('macroproject_contacts');
					$q->addInsert('macroproject_id', $this->macroproject_id);
					$q->addInsert('contact_id', $contact);
					$q->exec();
					$q->clear();
				}
			}
		}
		
		return ((!$ret) ? (get_class($this) . '::store failed <br />' . db_error()) : NULL);
	}
}

/* E.g. this code is used as well in a tab for the admin/viewuser site
**
** @mixed user_id 	userId as filter for tasks/macroprojects that are shown, if nothing is specified, 
			current viewing user $AppUI->user_id is used.
*/

function macroprojects_list_data($user_id=false) {
	global $AppUI, $addPwOiD, $cBuffer, $company, $company_id, $company_prefix, $deny, $department;
	global $dept_ids, $dPconfig, $macroorderby, $orderdir, $macroprojects;//, $tasks_critical, $tasks_problems;
	global /*$tasks_sum, $tasks_summy, $tasks_total,*/ $owner, $projectTypeId, $project_status;
	global $currentTabId;
	//$addProjectsWithAssignedTasks = (($AppUI->getState('addProjWithTasks')) 
	//                                 ? $AppUI->getState('addProjWithTasks') : 0);
	
	//for getting permissions on project records
	$obj_macroproject = new CMacroProject();
	
	//Let's delete temproary tables
	$q = new DBQuery;
	$table_list = array('tasks_sum','tasks_total','tasks_summy','tasks_critical','tasks_problems','tasks_users');
	$q->dropTemp($table_list);
	$q->exec();
	$q->clear();

	// Task sum table
	// by Pablo Roca (pabloroca@mvps.org)
	// 16 August 2003

	$working_hours = ($dPconfig['daily_working_hours']?$dPconfig['daily_working_hours']:8);

	// GJB: Note that we have to special case duration type 24 
	// and this refers to the hours in a day, NOT 24 hours
	/*$q->createTemp('tasks_sum');
	$q->addTable('tasks', 't');
	$q->addQuery('t.task_project, SUM(t.task_duration * t.task_percent_complete' 
	             . ' * IF(t.task_duration_type = 24, ' . $working_hours 
	             . ', t.task_duration_type)) / SUM(t.task_duration' 
	             . ' * IF(t.task_duration_type = 24, ' . $working_hours 
	             . ', t.task_duration_type)) AS project_percent_complete, SUM(t.task_duration' 
	             . ' * IF(t.task_duration_type = 24, ' . $working_hours 
	             . ', t.task_duration_type)) AS project_duration');
	if ($user_id) {
		$q->addJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
		$q->addWhere('ut.user_id = ' . $user_id);
	}
	//$q->addWhere('t.task_id = t.task_parent'); ==>on veut le calculer en prenant en compte toutes les tâches
	$q->addGroup('t.task_project');
	$tasks_sum = $q->exec();
	$q->clear();*/
	
	// At this stage tasks_sum contains the project id, and the total of tasks as percentage complate and project duration.
	// I.e. one record per project
    
	// Task total table
	/*$q->createTemp('tasks_total');
	$q->addTable('tasks', 't');
	$q->addQuery('t.task_project, COUNT(distinct t.task_id) AS total_tasks');
	if ($user_id) {
		$q->addJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
		$q->addWhere('ut.user_id = ' . $user_id);
	}
	$q->addGroup('t.task_project');
	$tasks_total = $q->exec();
	$q->clear();*/
	
	// tasks_total contains the total number of tasks for each project.
    
	// temporary My Tasks
	// by Pablo Roca (pabloroca@mvps.org)
	// 16 August 2003
	/*$q->createTemp('tasks_summy');
	$q->addTable('tasks', 't');
	$q->addQuery('t.task_project, COUNT(DISTINCT t.task_id) AS my_tasks');
	$q->addWhere('t.task_owner = ' . (($user_id) ? $user_id : $AppUI->user_id));
	$q->addGroup('t.task_project');
	$tasks_summy = $q->exec();
	$q->clear();*/
	
	// tasks_summy contains total count of tasks for each project that I own.

	// temporary critical tasks
	/*$q->createTemp('tasks_critical');
	$q->addTable('tasks', 't');
	$q->addQuery('t.task_project, t.task_id AS critical_task' 
	             . ', MAX(t.task_end_date) AS project_actual_end_date');
	// MerlinYoda: we don't join tables if we don't get anything out of the process
	// $q->addJoin('projects', 'p', 'p.project_id = t.task_project');
	$q->addOrder('t.task_end_date DESC');
	$q->addGroup('t.task_project');
	$tasks_critical = $q->exec();
	$q->clear();

	// tasks_critical contains the latest ending task and its end date.
	
	// temporary task problem logs
	$q->createTemp('tasks_problems');
	$q->addTable('tasks', 't');
	$q->addQuery('t.task_project, tl.task_log_problem');
	$q->addJoin('task_log', 'tl', 'tl.task_log_task = t.task_id');
	$q->addWhere('tl.task_log_problem > 0');
	$q->addGroup('t.task_project');
	$tasks_problems = $q->exec();
	$q->clear();

	// tasks_problems contains an indication of any projects that have task logs set to problem.
	
	if ($addProjectsWithAssignedTasks) {
		// temporary users tasks
		$q->createTemp('tasks_users');
		$q->addTable('tasks', 't');
		$q->addQuery('t.task_project, ut.user_id');
		$q->addJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
		if ($user_id) {
			$q->addWhere('ut.user_id = ' . $user_id);
		}
		$q->addOrder('t.task_end_date DESC');
		$q->addGroup('t.task_project');
		$tasks_users = $q->exec();
		$q->clear();
	}*/

	// tasks_users contains all projects with tasks that have user assignments. (isn't this getting pointless?)
	
	// add Projects where the Project Owner is in the given department
	if ($addPwOiD && isset($department)) {
		$owner_ids = array();
		$q->addTable('users', 'u');
		$q->addQuery('u.user_id');
		$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact');
		$q->addWhere('c.contact_department = ' . $department);
		$owner_ids = $q->loadColumn();	
		$q->clear();
	}

	if (isset($department)) {
		/*
		 * If a department is specified, we want to display projects from the department 
		 * and all departments under that, so we need to build that list of departments 
		 */
		$dept_ids = array();
		$q->addTable('departments');
		$q->addQuery('dept_id, dept_parent');
		$q->addOrder('dept_parent,dept_name');
		$rows = $q->loadList();
		addDeptId($rows, $department);
		$dept_ids[] = $department;
	}
	$q->clear();
	
	
	
	$q->addTable('macroprojects', 'mp');
	$q->addQuery('mp.macroproject_id, mp.macroproject_status, mp.macroproject_color_identifier, mp.macroproject_type' 
	             . ', mp.macroproject_name, mp.macroproject_description, mp.macroproject_start_date' 
	             . ', mp.macroproject_end_date, mp.macroproject_color_identifier, mp.macroproject_company' 
	             . ', mp.macroproject_status, mp.macroproject_percent_complete, mp.macroproject_priority, com.company_name' 
	             . ', com.company_description,'// tc.critical_task, tc.project_actual_end_date' 
	             //. ', if (tp.task_log_problem IS NULL, 0, tp.task_log_problem) AS task_log_problem' 
				 //. ', tt.total_tasks, tsy.my_tasks, ts.project_percent_complete' 
				 /*. ', ts.project_duration,*/. 'u.user_username');
	$q->addJoin('companies', 'com', 'mp.macroproject_company = com.company_id');
	$q->addJoin('users', 'u', 'mp.macroproject_owner = u.user_id');
	//$q->addJoin('tasks_critical', 'tc', 'mp.project_id = tc.task_project');
	//$q->addJoin('tasks_problems', 'tp', 'mp.project_id = tp.task_project');
	//$q->addJoin('tasks_sum', 'ts', 'mp.project_id = ts.task_project');
	//$q->addJoin('tasks_total', 'tt', 'mp.project_id = tt.task_project');
	//$q->addJoin('tasks_summy', 'tsy', 'mp.project_id = tsy.task_project');	
	/*if ($addProjectsWithAssignedTasks) {
		$q->addJoin('tasks_users', 'tu', 'mp.project_id = tu.task_project');
	}*/
	if (isset($macroproject_status) && $currentTabId != 500) {
		$q->addWhere('mp.macroproject_status = '.$macroproject_status);
	}
	if (isset($department)) {
		$q->addJoin('macroproject_departments', 'pd', 'pd.macroproject_id = mp.macroproject_id');
		if (!$addPwOiD) {
			$q->addWhere('pd.department_id in (' . implode(',',$dept_ids) . ')');
		} else {
			// Show Projects where the Project Owner is in the given department
			$q->addWhere('mp.macroproject_owner IN (' 
			             . ((!empty($owner_ids)) ? implode(',', $owner_ids) : 0) . ')');
		}
	} else if ($company_id &&!$addPwOiD) {
		$q->addWhere('mp.macroproject_company = ' . $company_id);
	}
	
	if ($macroprojectTypeId > -1) {
		$q->addWhere('mp.macroproject_type = ' . $macroprojectTypeId);
	}
	
	if ($user_id && $addMacroProjectsWithAssignedTasks) {
		$q->addWhere('(tu.user_id = ' . $user_id . ' OR mp.macroproject_owner = ' . $user_id . ')');
	} else if ($user_id) {
		$q->addWhere('mp.macroproject_owner = ' . $user_id);
	}
	
	if ($owner > 0) {
		$q->addWhere('mp.macroproject_owner = ' . $owner);
	}
	
	$q->addGroup('mp.macroproject_id');
	$q->addOrder($macroorderby . ' ' . $orderdir);
	$obj_macroproject->setAllowedSQL($AppUI->user_id, $q, null, 'mp');
	$macroprojects = $q->loadList();
	
	
	
	// retrieve list of records
	// modified for speed
	// by Pablo Roca (pabloroca@mvps.org)
	// 16 August 2003
	// get the list of permitted companies
	$obj_company = new CCompany();
	$companies = $obj_company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 
	                                             'company_name');
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
	$cBuffer = '<select name="department" onchange="javascript:document.pickCompany.submit()" class="text">';
	$cBuffer .= ('<option value="company_0" style="font-weight:bold;">' . $AppUI->_('All') 
	             . '</option>'."\n");
	$company = '';
	foreach ($rows as $row) {
		if ($row['dept_parent'] == 0) {
			if ($company != $row['company_id']) {
				$cBuffer .= ('<option value="' . $AppUI->___($company_prefix . $row['company_id']) 
				             . '" style="font-weight:bold;"' 
				             . (($company_id == $row['company_id']) ? 'selected="selected"' : '') 
				             . '>' . $AppUI->___($row['company_name']) . '</option>' . "\n");
				$company = $row['company_id'];
			}
			
			if ($row['dept_parent'] != null) {
				showchilddept($row);
				findchilddept($rows, $row['dept_id']);
			}
		}
	}
	$cBuffer .= '</select>';
	
}

//select all macroprojects who contains this macroproject  
function recoverFatherMacroProjects($macroproject_id, $where) 
{
	$allMacroprojects = "";
	$q = new DBQuery;
	$q->clear();
	$q->addTable('macroproject_macroproject');
	$q->addQuery('macroproject_father');
	$q->addWhere('macroproject_son ='.$macroproject_id);
	$macroprojectsofmacro = $q->loadList();
	if(count($macroprojectsofmacro) > 0)//if macroproject contains macroproject
	{
		foreach($macroprojectsofmacro as $macroReed)
		{
			$allMacroprojects .= recoverFatherMacroProjects($macroReed['macroproject_father'], $where);
		}
	}
	$allMacroprojects .= " AND $where $macroproject_id ";
	return $allMacroprojects;
}
//select all macroprojects include in a macroproject
function recoverMacroProjects($macroproject_id) 
{
	$allMacroprojects = "";
	$q = new DBQuery;
	$q->clear();
	$q->addTable('macroproject_macroproject');
	$q->addQuery('macroproject_son');
	$q->addWhere('macroproject_father ='.$macroproject_id);
	$macroprojectsofmacro = $q->loadList();
	if(count($macroprojectsofmacro) > 0)//if macroproject contains macroproject
	{
		foreach($macroprojectsofmacro as $macroReed)
		{
			$allMacroprojects .= recoverMacroProjects($macroReed['macroproject_son']);
		}
	}
	$allMacroprojects .= " or macroproject_id = $macroproject_id";
	return $allMacroprojects;
}
//select all projects include in a macroproject
function recoverProjects($macroproject_id)
{
	$q = new DBQuery;
	$q->clear();
	$q->addTable('macroproject_project');
	$q->addQuery('project_id');
	$q->addWhere('macroproject_id = 0'.recoverMacroProjects($macroproject_id));
	//$q->addWhere('macroproject_id = ' . $macroproject_id);
	$projectsofmacro = $q->loadList();
	$q->clear();
	return $projectsofmacro;
}
//make where clause for work on each project of a macroproject
function makeWhereClauseEachProjectOfAMacroProject($macroproject_id, $whereClause)
{
	$projectsofmacro = recoverProjects($macroproject_id);
	$q = new DBQuery;
	$q->clear();
	$idxMacro=0;
	if(count($projectsofmacro) > 0)
	{
		$finalWhere = "";
		foreach($projectsofmacro as $projectReed)
		{
			$idxMacro++;
			$projectPrint = $projectReed['project_id'];
			if ($idxMacro < count($projectsofmacro)){//if we don't work on the last project of the array projectofmacro we add an or after the conditions
				$finalWhere .= "($whereClause $projectPrint) or ";
			}
			else
			{
				$finalWhere .= "($whereClause $projectPrint)";
			}
		}
	}
	else{
		$finalWhere = ($whereClause.' 10');
	}
	return $finalWhere;
}

//update macroproject_percent_complete
function updateMacroProjectPercentComplete($macroproject_id)
{
	$q = new DBQuery();
	$q->addTable('macroprojects', 'ma');
	$q->addQuery('ma.macroproject_id, SUM(t.task_duration * t.task_duration_type * t.task_percent_complete) / SUM(t.task_duration * t.task_duration_type) AS macroproject_percent_complete');
	$q->addWhere('macroproject_id =' . $macroproject_id);
	$q->addWhere(makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 't.task_project='));//for work on all tasks of all projects of this macroproject
	$q->addJoin('tasks', 't', '1');
	$macroprojects = $q->loadList();
	$q->exec();
	$q->clear();

	foreach($macroprojects as $row)
	{
	echo "Macroproject ID : ".$row['macroproject_id'];
	if ($row['macroproject_id']!=''){	// if macroproject contains tasks
		$q->addTable('macroprojects', 'mp');
		$q->addWhere('macroproject_id = ' . $row['macroproject_id']);
		$q->addUpdate('macroproject_percent_complete', $row['macroproject_percent_complete']);
		$q->exec();
		$q->clear();
		} 
	}
}
?>
