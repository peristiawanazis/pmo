<?php /* MACRO_PROJECTS vw_files.php, v 0.1.0 2012/05/30 */
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

GLOBAL $AppUI, $macroproject_id, $deny, $canRead, $canEdit, $dPconfig;

$showMacroProject = false;
require(DP_BASE_DIR.'/modules/files/index_table.php');
?>
