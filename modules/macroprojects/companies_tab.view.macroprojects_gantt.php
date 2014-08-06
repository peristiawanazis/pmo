<?php /* MACRO_PROJECTS companies_tab.viewuser.macroprojects_gantt.php, v 0.1.0 2012/05/30 */
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

global $m, $a, $addPwOiD, $AppUI, $cBuffer, $company_id, $min_view, $priority, $macroprojects, $tab;

$df = $AppUI->getPref('SHDATEFORMAT');

$mpstatus =  dPgetSysVal('MacroProjectStatus');

$macroprojFilter_extra = array('-4' => 'All w/o archived');

// load the companies class to retrieved denied companies
require_once($AppUI->getModuleClass('companies'));

// retrieve any state parameters
if (isset($_GET['tab'])) {
	$AppUI->setState('DeptMacroProjIdxTab', $_GET['tab']);
}

?>
<?php
$min_view = true;
require(DP_BASE_DIR.'/modules/macroprojects/viewgantt.php');
?>
