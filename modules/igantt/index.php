<?php /* IGANTT index.php,v 0.1.0 2009/01/14  */
/*
Copyright (c) 2008 -2009 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	For dP version prior to V1.2.1 this page will allow to access iGantt from the module menu bar
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
// check permissions for this module
$perms =& $AppUI->acl();
$canView = $perms->checkModule( 'igantt', 'view' ) && $perms->checkModule( 'projects', 'view');
if (!$canView) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// Retrieve the list of not archived projects
include ( $AppUI->getModuleClass('projects') );
$project = new CProject();
//$projects = $project->getAllowedRecords( $AppUI->user_id, 'project_id,project_name', 'project_name', null, $extra );
$q  = new DBQuery;
$q->addTable( 'projects' );
$q->addQuery( 'project_id, CONCAT_WS( " :: ", company_name, project_name)' );
$q->addWhere( 'project_status<>7');
$project->setAllowedSQL( $AppUI->user_id, $q );
$q->addOrder( 'company_name, project_name');
$projects = $q->loadHashList();
$projects = arrayMerge( array( '0' => "(".$AppUI->_('Select a project', UI_OUTPUT_RAW)."...)" ), $projects );

// Create title Block
$titleBlock = new CTitleBlock('Interactive Gantt chart', 'applet3-48.png', $m, "$m.$a");
$titleBlock->show();
?>
<script language="javascript">
function submitIt() {
	var opt = document.projectFrm.project_id;
	var projectId = opt.options[opt.selectedIndex].value;
	if ( projectId > 0 ) {
		document.updateTasks.project_id.value=projectId;
		window.open("index.php?m=igantt&a=vw_igantt&suppressHeaders=1&project_id="+projectId, "Interactive_Gantt_chart", "fullscreen=yes,resizable=yes,scrollbars=yes");
	} else {
		alert("<?php echo $AppUI->_("Select a project"); ?>");
	}
}
</script>
<div id="divAlert" style="display:none;">
You are using : <span id="browserName"></span><br />
<span><?php echo $AppUI->_("wrongBrowser"); ?></span> 
</div>
<div id="projectSelect" style="display:none;">
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="tbl">
<form name="projectFrm" action="?m=igantt" method="post">
<tr>
	<td width="90%" nowrap >
		<?php echo $AppUI->_('Project');?>: <?php echo arraySelect( $projects, 'project_id','class="text" style="width:500px"', 0  );?>
	</td>
	<td width="10%" align="right">
	<input type="button" class="button" value="<?php echo $AppUI->_( 'display Gantt' );?>" onclick="submitIt()" />
	</td>
</tr>
</form>
</table>
</div>
<script language="javascript" >
document.getElementById("browserName").innerHTML=navigator.appName;
if ( navigator.appName == "Microsoft Internet Explorer" ) {
	document.getElementById("divAlert").style.display="block";
} else {
	document.getElementById("projectSelect").style.display="block";
}
</script>
<form id="updateTasks" name="updateTasks" action="./index.php?m=igantt" method="post" >
	<input type="hidden" name="dosql" value="do_bulk_task_aed" />
	<input type="hidden" name="project_id" value="" />
	<input type="hidden" name="bulk_task_id" value="" />
	<input type="hidden" name="bulk_task_start_date" value="" />
	<input type="hidden" name="bulk_task_end_date" value="" />
	<input type="hidden" name="bulk_dependencies" value="" />
</form>
