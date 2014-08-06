<?php /* IGANTT projects_tab.igantt.php,v 0.1.0 2008/12/29  */
/*
Copyright (c) 2008 -2009 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	Generates the iGantt tab in dotProject project view after checking browser compatibility
*			This work includes the use of the XAJAX library that is distributed under the BSD licence
*
* Author:		Pierre-Yves SIMONOT, Euxenis, <pierre-yves.simonot@euxenis.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

global $project_id;
?>
<div id="divAlert"  style="display:none;">
You are using : <span id="browserName"></span><br />
<span><?php echo $AppUI->_("wrongBrowser"); ?></span> 
</div>
<script language="javascript" >
/*
document.getElementById("browserName").innerHTML=navigator.appName;
if ( navigator.appName == "Microsoft Internet Explorer" ) {
	document.getElementById("divAlert").style.display="block";
} else {
*/	window.open("index.php?m=igantt&a=vw_igantt&suppressHeaders=1&project_id=<?php echo $project_id; ?>", "Interactive_Gantt_chart", "fullscreen=yes,resizable=yes,scrollbars=yes");
/*}
*/
</script>
<form id="updateTasks" name="updateTasks" action="./index.php?m=igantt" method="post" >
	<input type="hidden" name="dosql" value="do_bulk_task_aed" />
	<input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
	<input type="hidden" name="bulk_task_id" value="" />
	<input type="hidden" name="bulk_task_start_date" value="" />
	<input type="hidden" name="bulk_task_end_date" value="" />
	<input type="hidden" name="bulk_dependencies" value="" />
</form>