<?php  /* MACRO_PROJECTS admin_tab.viewuser.macroprojects_gantt.php, v 0.1.0 2012/05/30 */
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

global $company_id, $dept_ids, $department, $min_view, $m, $a, $user_id, $tab;

// reset the department and company filter info
// which is not used here
$company_id = $department = 0;
$macroprojFilter_extra = array('-4' => 'All w/o archived');

require(DP_BASE_DIR.'/modules/macroprojects/viewgantt.php');
?>
