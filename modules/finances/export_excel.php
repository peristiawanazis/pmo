<?php
//Export the financial view

require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('macroprojects');

// Set today
$today = new CDate();
$default[0] = $today->getYear();														// Default array for $years
$yearsGET 		= dPgetParam($_GET, 'years', $default);
$years = explode("y", $yearsGET); 

$hideNull 		= dPgetParam($_GET, 'hideNull', 1);
$tax 		= dPgetParam($_GET, 'tax', 0);												// 0: without, 1: with	 				Default: without
$company_id 		= dPgetParam($_GET, 'companyId', 0);
$department_id 		= dPgetParam($_GET, 'departmentId', 0);
$project_type 		= dPgetParam($_GET, 'projectType', 0);
$project_status 		= dPgetParam($_GET, '$projectStatus', 0);
$printproject 		= dPgetParam($_GET, 'project', 0);
$printmacroproject 		= dPgetParam($_GET, 'macroproject', 0);

$line=0;
$filename ="finances.xls";
$contents = "";
header('Content-type: application/ms-excel');
header('Content-Disposition: attachment; filename='.$filename);
if($printmacroproject){
	$contents .= $AppUI->_('MacroProject / Project / Task(Tax)')."\t";
}
else{
	$contents .= $AppUI->_('Project / Task(Tax)')."\t";
}
$contents .= $AppUI->_('INVESTMENT')."\t\t\t".$AppUI->_('OPERATION')."\n";
$contents .= "\t".$AppUI->_('Equipment')."\t".$AppUI->_('Intangible')."\t".$AppUI->_('Service')."\t".$AppUI->_('Equipment')."\t".$AppUI->_('Intangible')."\t".$AppUI->_('Service')."\t"."TOTAL"."\n";
$line=2;

if($printmacroproject){
	$macroprojects = loadMacroProjects($macroproject_type, $macroproject_status);
	$macroproject_num = 0;
	foreach($macroprojects as $mp){
		$tasks = loadMPTasks($mp->macroproject_id, $years);
		if ($tasks != null) {
			$line++;
			$tempContents = "";
			$tempProjectContents = "";
			$tempMacroProjectContents = "";
			$taskOfThisProject=0;
			$lineOfProject = array();
			$taskOfMacroProject = array();
			$actualProject = -1;//line3
			foreach($tasks as $task){
				if(!in_array($task->task_id, $taskOfMacroProject)){
					$taskOfMacroProject[] = $task->task_id;
					if($actualProject != $task->task_project){
						$line++;
						if ($actualProject != -1){//if it's not the first time we read through of $tasks
							$project = new CProject();
							$project->load($actualProject);//we load the last project
							if($taskOfThisProject != 0){
								$tempProjectContents .= "  ".$project->project_name."\t";
								$tempProjectContents .= "=somme(B".($line-$taskOfThisProject-$macroproject_num).":B".($line-1-$macroproject_num).")\t";
								$tempProjectContents .= "=somme(C".($line-$taskOfThisProject-$macroproject_num).":C".($line-1-$macroproject_num).")\t";
								$tempProjectContents .= "=somme(D".($line-$taskOfThisProject-$macroproject_num).":D".($line-1-$macroproject_num).")\t";
								$tempProjectContents .= "=somme(E".($line-$taskOfThisProject-$macroproject_num).":E".($line-1-$macroproject_num).")\t";
								$tempProjectContents .= "=somme(F".($line-$taskOfThisProject-$macroproject_num).":F".($line-1-$macroproject_num).")\t";
								$tempProjectContents .= "=somme(G".($line-$taskOfThisProject-$macroproject_num).":G".($line-1-$macroproject_num).")\t";
								$tempProjectContents .= "=somme(B".($line-$taskOfThisProject-$macroproject_num-1).":G".($line-$taskOfThisProject-$macroproject_num-1).")\n";
							}
							else{
								$tempProjectContents .= "\n";
							}
							$tempMacroProjectContents .= $tempProjectContents.$tempContents;
							$tempProjectContents = "";
							$tempContents = "";
							$actualProject = $task->task_project;
							$lineOfProject[] = $line-$taskOfThisProject-1-$macroproject_num;
							$taskOfThisProject = 0;
						}
						else{
							$actualProject = $task->task_project;
						}
					}
					$line++;
					// Load the budget of each tasks
					$budget = new CBudget();
					$budget->loadFromTask($task->task_id);
					
					$tempContents .= "    ".$task->task_name."\t";
					
					if($tax){
						$budget->display_tax = 1;
					}
					else{
						$budget->display_tax = 0;
					}
					$tempContents .= $budget->get_equipment_investment(1,"","")."\t";
					$tempContents .= $budget->get_intangible_investment(1,"","")."\t";
					$tempContents .= $budget->get_service_investment(1,"","")."\t";
					$tempContents .= $budget->get_equipment_operation(1,"","")."\t";
					$tempContents .= $budget->get_intangible_operation(1,"","")."\t";
					$tempContents .= $budget->get_service_operation(1,"","")."\t";
					$tempContents .= "=somme(B".($line-$macroproject_num).":G".($line-$macroproject_num).")\n";
					$taskOfThisProject++;
				}
			}
			$line++;
			$project = new CProject();
			$project->load($actualProject);//we load the last project
			if($taskOfThisProject != 0){
				$tempProjectContents .= "  ".$project->project_name."\t";
				$tempProjectContents .= "=somme(B".($line-$taskOfThisProject-$macroproject_num).":B".($line-1-$macroproject_num).")\t";
				$tempProjectContents .= "=somme(C".($line-$taskOfThisProject-$macroproject_num).":C".($line-1-$macroproject_num).")\t";
				$tempProjectContents .= "=somme(D".($line-$taskOfThisProject-$macroproject_num).":D".($line-1-$macroproject_num).")\t";
				$tempProjectContents .= "=somme(E".($line-$taskOfThisProject-$macroproject_num).":E".($line-1-$macroproject_num).")\t";
				$tempProjectContents .= "=somme(F".($line-$taskOfThisProject-$macroproject_num).":F".($line-1-$macroproject_num).")\t";
				$tempProjectContents .= "=somme(G".($line-$taskOfThisProject-$macroproject_num).":G".($line-1-$macroproject_num).")\t";
				$tempProjectContents .= "=somme(B".($line-$taskOfThisProject-$macroproject_num-1).":G".($line-$taskOfThisProject-$macroproject_num-1).")\n";
			}
			$tempMacroProjectContents .= $tempProjectContents.$tempContents;
			$tempProjectContents = "";
			$tempContents = "";
			$lineOfProject[] = $line-$taskOfThisProject-1-$macroproject_num;
			
			$equipment_investment_total = "=0";
			$intangible_investment_total = "=0";
			$service_investment_total = "=0";
			$equipment_operation_total = "=0";
			$intangible_operation_total = "=0";
			$service_operation_total = "=0";
			if($lineOfProject != NULL){
				foreach($lineOfProject as $lop){
					$equipment_investment_total .= "+B".$lop;
					$intangible_investment_total .= "+C".$lop;
					$service_investment_total .= "+D".$lop;
					$equipment_operation_total .= "+E".$lop;
					$intangible_operation_total .= "+F".$lop;
					$service_operation_total .= "+G".$lop;
				}
			}
			$contents .= $mp->macroproject_name."\t".$equipment_investment_total."\t".$intangible_investment_total."\t".$service_investment_total."\t".$equipment_operation_total."\t".$intangible_operation_total."\t".$service_operation_total."\t=somme(B".($lineOfProject[0]-1).":G".($lineOfProject[0]-1).")\n".$tempMacroProjectContents;
			$macroproject_num++;
		}
	}	
}
else{
	$projects = loadProjects($company_id, $department_id, $project_type, $project_status);

	foreach($projects as $project){
		$line++;
		$negLine = 0;
		$projectBudget = 0;
		$tempContents = "";
		$tempProjectContents = "";
		if(getPermission('projects', 'view', $project->project_id)) { // Check permission
			$tasks = loadTasks($project->project_id, $years); // Load only corresponding tasks
			if ($tasks != null) {
				foreach($tasks as $task) {
					// Load the budget of each tasks
					$budget = new CBudget();
					$budget->loadFromTask($task->task_id);
					if($hideNull !=1 || !$budget->isNull()){
						$tempContents .= "  ".$task->task_name."\t";
						$projectBudget = 1;
						if($tax){
							$budget->display_tax = 1;
						}
						else{
							$budget->display_tax = 0;
						}
						$tempContents .= $budget->get_equipment_investment(1,"","")."\t";
						$tempContents .= $budget->get_intangible_investment(1,"","")."\t";
						$tempContents .= $budget->get_service_investment(1,"","")."\t";
						$tempContents .= $budget->get_equipment_operation(1,"","")."\t";
						$tempContents .= $budget->get_intangible_operation(1,"","")."\t";
						$tempContents .= $budget->get_service_operation(1,"","")."\t";
						$tempContents .= "=somme(B".($line+1).":G".($line+1).")\n";
						$line++;
					}
					else{
						$negLine++;
					}
				}
			}
			if(count($tasks) != 0){
				$tempProjectContents .= $project->project_name."\t";
				$tempProjectContents .= "=somme(B".($line-count($tasks)+$negLine+1).":B".$line.")\t";
				$tempProjectContents .= "=somme(C".($line-count($tasks)+$negLine+1).":C".$line.")\t";
				$tempProjectContents .= "=somme(D".($line-count($tasks)+$negLine+1).":D".$line.")\t";
				$tempProjectContents .= "=somme(E".($line-count($tasks)+$negLine+1).":E".$line.")\t";
				$tempProjectContents .= "=somme(F".($line-count($tasks)+$negLine+1).":F".$line.")\t";
				$tempProjectContents .= "=somme(G".($line-count($tasks)+$negLine+1).":G".$line.")\t";
				$tempProjectContents .= "=somme(B".($line-count($tasks)+$negLine).":G".($line-count($tasks)+$negLine).")\n";
			}
			else{
				$tempContents .= "\n";
			}
			if(($hideNull == 0) || ($projectBudget == 1 && $hideNull == 1) || ($projectBudget == 1 && $hideNull == 2) || ($projectBudget == 0 && $hideNull == 3)){
				$contents .= $tempProjectContents.$tempContents;
				$lineOfProject[] = $line-count($tasks)+$negLine;
			}
			else{
				$line--;
			}
		}
	}
	$contents .= "TOTAL"."\t";
	$equipment_investment_total = "=0";
	$intangible_investment_total = "=0";
	$service_investment_total = "=0";
	$equipment_operation_total = "=0";
	$intangible_operation_total = "=0";
	$service_operation_total = "=0";
	foreach($lineOfProject as $lop){
		$equipment_investment_total .= "+B".$lop;
		$intangible_investment_total .= "+C".$lop;
		$service_investment_total .= "+D".$lop;
		$equipment_operation_total .= "+E".$lop;
		$intangible_operation_total .= "+F".$lop;
		$service_operation_total .= "+G".$lop;
	}
	$contents .= $equipment_investment_total."\t".$intangible_investment_total."\t".$service_investment_total."\t".$equipment_operation_total."\t".$intangible_operation_total."\t".$service_operation_total."\n";
	$line++;
	$contents .= "TOTAL"."\t";
	$contents .= "=B".$line."+C".$line."+D".$line."\t\t\t"."=E".$line."+F".$line."+G".$line."\n";
	$line++;
	$contents .= "TOTAL"."\t";
	$contents .= "=B".$line."+E".$line."\n";
}

//clean accents error
$contents = str_replace ( '├й' , 'щ' , $contents);
$contents = str_replace ( '├и' , 'ш' , $contents);
$contents = str_replace ( '├' , 'р' , $contents);
$contents = str_replace ( '┬░' , '░' , $contents);
$contents = str_replace ( 'рк' , 'ъ' , $contents);
$contents = str_replace ( '&eacute;' , 'щ' , $contents);
$contents = str_replace ( '&acirc;' , 'т' , $contents);
if($AppUI->user_locale == 'fr'){
	$contents = str_replace ( '.' , ',' , $contents);
}

echo $contents;
?>