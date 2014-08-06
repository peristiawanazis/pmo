<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

GLOBAL $min_view, $m, $a, $user_id, $tab, $tasks, $sortByName, $project_id, $gantt_map, $currentGanttImgSource, $filter_task_list, $caller;

$base_url = dPgetConfig('base_url');
$min_view = defVal(@$min_view, false);
$mode = dPgetParam($_POST, 'mode', '0');
$showfinancial = dPgetParam($_POST, 'showfinancial', '0');
$project_id = defVal(@$_GET['project_id'], 0);
$i = 0;
if (isset($_GET['macroproject_id'])){
	$macroproject_id = defVal(@$_GET['macroproject_id'], 0);
	/* $tq = new DBQuery;
	$tq->addTable('macroproject_project');
	$tq->addQuery('project_id');
	$tq->addWhere('macroproject_id = ' .$macroproject_id);
	$projectsofmacro = $tq->loadList();	 */
	$projectsofmacro = recoverProjects($macroproject_id);
}
if ($mode == 0 && isset($_GET['macroproject_id']))
{
	$tq = new DBQuery;
	$tq->addTable('macroprojects');
	$tq->addQuery('macroproject_id, macroproject_start_date, macroproject_end_date');
	$tq->addWhere('macroproject_id = ' .$macroproject_id);
	$macroprojects = $tq->loadHashList('macroproject_id');
}
//else{	
	// sdate and edate passed as unix time stamps
	$sdate = dPgetParam($_POST, 'sdate', 0);
	$edate = dPgetParam($_POST, 'edate', 0);
//}echo $edate;
//if set GantChart includes user labels as captions of every GantBar
$showLabels = dPgetParam($_POST, 'showLabels', '0');
$showLabels = (($showLabels != '0') ? '1' : $showLabels);

$showWork = dPgetParam($_POST, 'showWork', '0');
$showWork = (($showWork != '0') ? '1' : $showWork);

$showWork_days = dPgetParam($_POST, 'showWork_days', '0');
$showWork_days = (($showWork_days != '0') ? '1' : $showWork_days);
/////////////////////////////////////////// New variables for use in Gantt formatting are defined here //////////////////////////////////////////////////////////////////////
$showTaskNameOnly = dPgetParam($_REQUEST, 'showTaskNameOnly', '0');
$showTaskNameOnly = (($showTaskNameOnly != '0') ? '1' : $showTaskNameOnly);

$showNoMilestones = dPgetParam($_POST, 'showNoMilestones', '0');
$showNoMilestones = (($showNoMilestones != '0') ? '1' : $showNoMilestones);

$showhgrid = dPgetParam($_POST, 'showhgrid', '0');
$showhgrid = (($showhgrid != '0') ? '1' : $showhgrid);

$addLinksToGantt = dPgetParam($_REQUEST, 'addLinksToGantt', '0');
$addLinksToGantt = (($addLinksToGantt !='0')? '1' : $addLinksToGantt);

$printpdf = dPgetParam($_REQUEST, 'printpdf', '0');
$printpdf = (($printpdf != '0') ? '1' : $printpdf);

$printpdfhr = dPgetParam($_REQUEST, 'printpdfhr', '0');
$printpdfhr = (($printpdfhr != '0') ? '1' : $printpdfhr);

$ganttTaskFilter = intval(dPgetParam($_REQUEST, 'ganttTaskFilter', '0'));

$monospacefont = dPgetParam($_REQUEST, 'monospacefont', '0');
$monospacefont = (($monospacefont != '0')? '1' : $monospacefont);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////set sort by name as default ////////////////////////////////////

$sortByName = dPgetParam($_REQUEST, 'sortByName');

if ($sortByName =='1') {
	$sortByName = dPgetParam($_POST, 'sortByName', '1');
} else {
	$sortByName = dPgetParam($_POST, 'sortByName', '0');
}
$sortByName = (($sortByName != '0') ? '1' : $sortByName);


////////////////////////end mod to show sort by name as default//////////////////
if ($a == 'todo') {
	if (isset($_POST['show_form'])) {
		$AppUI->setState('TaskDayShowArc', (int)dPgetParam($_POST, 'showArcProjs', 0));
		$AppUI->setState('TaskDayShowLow', (int)dPgetParam($_POST, 'showLowTasks', 0));
		$AppUI->setState('TaskDayShowHold', (int)dPgetParam($_POST, 'showHoldProjs', 0));
		$AppUI->setState('TaskDayShowDyn', (int)dPgetParam($_POST, 'showDynTasks', 0));
		$AppUI->setState('TaskDayShowPin', (int)dPgetParam($_POST, 'showPinned', 0));
	}
	$showArcProjs = $AppUI->getState('TaskDayShowArc', 0);
	$showLowTasks = $AppUI->getState('TaskDayShowLow', 1);
	$showHoldProjs = $AppUI->getState('TaskDayShowHold', 0);
	$showDynTasks = $AppUI->getState('TaskDayShowDyn', 0);
	$showPinned = $AppUI->getState('TaskDayShowPin', 0);

} else {
	$showPinned = (int)dPgetParam($_POST, 'showPinned', '0');
	$showPinned = (($showPinned != '0') ? '1' : $showPinned);
	$showArcProjs = (int)dPgetParam($_POST, 'showArcProjs', '0');
	$showArcProjs = (($showArcProjs != '0') ? '1' : $showArcProjs);
	$showHoldProjs = (int)dPgetParam($_POST, 'showHoldProjs', '0');
	$showHoldProjs = (($showHoldProjs != '0') ? '1' : $showHoldProjs);
	$showDynTasks = (int)dPgetParam($_POST, 'showDynTasks', '0');
	$showDynTasks = (($showDynTasks != '0') ? '1' : $showDynTasks);
	$showLowTasks = (int)dPgetParam($_POST, 'showLowTasks', '0');
	$showLowTasks = (($showLowTasks != '0') ? '1' : $showLowTasks);
	
}
if(count($projectsofmacro) > 1)
{
	foreach($projectsofmacro as $project)
	{
		$idxProject++;
		if (isset($_GET['macroproject_id'])){
		$project_id = $project['project_id'];
		}
		/**
		  * prepare the array with the tasks to display in the task filter
		  * (for the most part this is code harvested from gantt.php)
		  *  //prepare les taches comprises dans le filtre
		  */ 
		$filter_task_list = array();
		$q = new DBQuery;
		$q->addTable('projects');
		$q->addQuery('project_id, project_color_identifier, project_name' 
					 . ', project_start_date, project_end_date');
		$q->addJoin('tasks', 't1', 'project_id = t1.task_project');
		$q->addWhere('project_status != 7');
		$q->addGroup('project_id');
		$q->addOrder('project_name');
		//$projects->setAllowedSQL($AppUI->user_id, $q);
		$projects = $q->loadHashList('project_id');
		$q->clear();

		$q->addTable('tasks', 't');
		$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
		$q->addQuery('t.task_id, task_parent, task_name, task_start_date, task_end_date' 
					 . ', task_duration, task_duration_type, task_priority, task_percent_complete' 
					 . ', task_order, task_project, task_milestone, project_name, task_dynamic');

		$q->addWhere('project_status != 7 AND task_dynamic = 1');
		if ($project_id) {
			$q->addWhere('task_project = ' . $project_id);
		}
		$task =& new CTask;
		$task->setAllowedSQL($AppUI->user_id, $q);
		$proTasks = $q->loadHashList('task_id');
		$q->clear();
		$filter_task_list = array ();
		$orrarr[] = array('task_id'=>0, 'order_up'=>0, 'order'=>'');
		foreach ($proTasks as $row) {
			$projects[$row['task_project']]['tasks'][] = $row;
		}
		unset($proTasks);
		$parents = array();
		if($idxProject <= 1)
		{
		function showfiltertask(&$a, $level=0) {
			/* Add tasks to the filter task aray */  //selectionne les taches filtre
			global $filter_task_list, $parents;
			$filter_task_list[] = array($a, $level);
			$parents[$a['task_parent']] = true;
		}
		function findfiltertaskchild(&$tarr, $parent, $level=0) {
			GLOBAL $projects, $filter_task_list;
			$level = $level + 1;
			$n = count($tarr);
			for ($x=0; $x < $n; $x++) {
				if ($tarr[$x]['task_parent'] == $parent && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']){
					showfiltertask($tarr[$x], $level);
					findfiltertaskchild($tarr, $tarr[$x]['task_id'], $level);
				}
			}
		}
		}
		foreach ($projects as $p) {
			global $parents, $task_id;
			$parents = array();
			$tnums = count($p['tasks']);
			for ($i=0; $i < $tnums; $i++) {
				$t = $p['tasks'][$i];
				if (!(isset($parents[$t['task_parent']]))) {
					$parents[$t['task_parent']] = false;
				}
				if ($t['task_parent'] == $t['task_id']) {
					showfiltertask($t);
					findfiltertaskchild($p['tasks'], $t['task_id']);
				}
			}
			// Check for ophans.
			foreach ($parents as $id => $ok) {
				if (!($ok)) {
					findfiltertaskchild($p['tasks'], $id);
				}
			}
		}
		/**
		 * the results of the above bits are stored in $filter_task_list (array)
		 * 
		 */

		// months to scroll
		$scroll_date = 1;

		$display_option = dPgetParam($_POST, 'display_option', 'all');
		// format dates
		$df = $AppUI->getPref('SHDATEFORMAT');

		if ($display_option == 'custom') {
			// custom dates
			$start_date = ((intval($sdate)) ? new CDate($sdate) : new CDate());
			$end_date = ((intval($edate)) ? new CDate($edate) : new CDate());
		} else {
			// month
			$start_date = new CDate();
			$start_date->day = 1;
			$end_date = new CDate($start_date);
			$end_date->addMonths($scroll_date);
		}

		// setup the title block
		if (!@$min_view) {
			$titleBlock = new CTitleBlock('Gantt Chart', 'applet-48.png', $m, "$m.$a");
			$titleBlock->addCrumb('?m=tasks', 'tasks list');
			$titleBlock->addCrumb(('?m=projects&amp;a=view&amp;project_id=' . $project_id), 'view this project');
			//$titleBlock->addCrumb('#" onclick="javascript:toggleLayer(\'displayOptions\');', 'show/hide display options');
			$titleBlock->show();
		}
		?>
		<script language="javascript" type="text/javascript">
		// <![CDATA[
		var calendarField = "";

		function popCalendar(field) {
			calendarField = field;
			idate = eval("document.editFrm." + field + ".value");
			window.open('?m=public&'+'a=calendar&'+'dialog=1&'+'callback=setCalendar&'+'date=' + idate, 
						"calwin", "width=250, height=230, scrollbars=no, status=no");
		}
		/**
		 *	@param string Input date in the format YYYYMMDD
		 *	@param string Formatted date
		 */
		function setCalendar(idate, fdate) {
			fld_date = eval("document.editFrm." + calendarField);
			fld_fdate = eval("document.editFrm.show_" + calendarField);
			fld_date.value = idate;
			fld_fdate.value = fdate;
		}
		function scrollPrev() {
			f = document.editFrm;
		<?php
			$new_start = new CDate($start_date);	
			$new_start->day = 1;
			$new_end = new CDate($end_date);
			$new_start->addMonths(-$scroll_date);
			$new_end->addMonths(-$scroll_date);
			echo ('f.sdate.value="' . $new_start->format(FMT_TIMESTAMP_DATE) . '";');
			echo ('f.edate.value="' . $new_end->format(FMT_TIMESTAMP_DATE) . '";');
		?>
			document.editFrm.display_option.value = "custom";
			f.submit()
		}
		function scrollNext() {
			f = document.editFrm;
		<?php
			$new_start = new CDate($start_date);	
			$new_start->day = 1;
			$new_end = new CDate($end_date);
			$new_start->addMonths($scroll_date);
			$new_end->addMonths($scroll_date);
			echo ('f.sdate.value="' . $new_start->format(FMT_TIMESTAMP_DATE) . '";');
			echo ('f.edate.value="' . $new_end->format(FMT_TIMESTAMP_DATE) . '";');
		?>
			document.editFrm.display_option.value = "custom";
			document.editFrm.printpdf.value = "0";
			document.editFrm.printpdfhr.value = "0";
			f.submit()
		}
		function showThisMonth() {
			document.editFrm.display_option.value = "this_month";
			document.editFrm.printpdf.value = "0";
			document.editFrm.printpdfhr.value = "0";
			document.editFrm.submit();
		}
		function showFullProject() {
			document.editFrm.display_option.value = "all";
			document.editFrm.printpdf.value = "0";
			document.editFrm.printpdfhr.value = "0";
			document.editFrm.submit();
		}
		function toggleLayer( whichLayer ) {
			var elem, vis;
			//if( document.getElementById ) // this is the way the standards work
				elem = document.getElementById( whichLayer );
			//else if( document.all ) // this is the way old msie versions work
			//	elem = document.all[whichLayer];
			//else if( document.layers ) // this is the way nn4 works
			//	elem = document.layers[whichLayer];
			vis = elem.style;
			// if the style.display value is blank we try to figure it out here
			//if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
			//	vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
				vis.display = (vis.display==''||vis.display=='block')?'none':'block';
		}
		function printPDF() {
			document.editFrm.printpdf.value = "1";
			document.editFrm.printpdfhr.value = "0";
			document.editFrm.submit();
		}
		function printPDFHR() {
			document.editFrm.printpdf.value = "0";
			document.editFrm.printpdfhr.value = "1";
			document.editFrm.submit();
		}
		function submitIt() {
			document.editFrm.printpdf.value = "0";
			document.editFrm.printpdfhr.value = "0";
			document.editFrm.submit();
		}
		function doMenu(item) { 
			obj=document.getElementById(item); 
			col=document.getElementById("x" + item); 
			if (obj.style.display=="none") { 
				obj.style.display="block"; 
				col.innerHTML="<?php echo $AppUI->_('Hide Additional Gantt Options'); ?>"; 
			} else { 
				obj.style.display="none"; 
				col.innerHTML="<?php echo $AppUI->_('Show Additional Gantt Options'); ?>"; 
			}
		} 

		//]]>
		</script>

		<?php ////////////////////// New checkboxes with additional formatting go here, this is with the view of displaying the options in an ajax box in the future /////////////////////////// -->
		if($idxProject == 1){
		?>
		<div id="displayOptions" style="display:block"> <!-- start of div used to show/hide formatting options -->
		<br />
		<form name="editFrm" method="post" action="?<?php 
		echo 'm=' . $m . '&amp;a=' . $a . '&amp;tab=' . $tab . '&amp;macroproject_id=' . $macroproject_id . '&amp;mode=' . $mode . '&amp;showfinancial=' . $showfinancial; ?>">
		<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
		<input type="hidden" name="printpdf" value="<?php echo $printpdf; ?>" />
		<input type="hidden" name="printpdfhr" value="<?php echo $printpdfhr; ?>" />
		<input type="hidden" name="caller" value="<?php echo $a; ?>" />
		
		<table border="0" align="center" class="tbl" border="0" cellpadding="2" cellspacing="0" style="min-width:990px">
		<tr> <!--  Date selection options go in this row -->
			<td align="right"><em><?php echo $AppUI->_('Date Filter'); ?>:</em></td>
			<td align="right">
				<table border="0" cellpadding="4" cellspacing="0">
				<tr>
					<td align="left" valign="top" width="20">
						<?php if ($display_option != "all") { ?>
						<a href="javascript:scrollPrev()">
							<img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_('previous');?>" border="0" />
						</a>
						<?php } ?>
					</td>
				
					<td align="right" nowrap="nowrap"><?php echo $AppUI->_('From');?>:</td>
					<td align="left" nowrap="nowrap">
						<input type="hidden" name="sdate" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
						<input type="text" class="text" name="show_sdate" value="<?php echo $start_date->format($df);?>" size="12" disabled="disabled" />
						<a href="javascript:popCalendar('sdate')">
							<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
						</a>
					</td>
				
					<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To');?>:</td>
					<td align="left" nowrap="nowrap">
						<input type="hidden" name="edate" value="<?php echo $end_date->format(FMT_TIMESTAMP_DATE);?>" />
						<input type="text" class="text" name="show_edate" value="<?php echo $end_date->format($df);?>" size="12" disabled="disabled" />
						<a href="javascript:popCalendar('edate')">
							<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
						</a>
					</td>
					<td align="left">
						<input type="button" class="button" value="<?php echo $AppUI->_('submit custom date');?>" onclick='document.editFrm.display_option.value="custom";document.editFrm.printpdf.value="0";submit();'/>
					</td>
				
					<td align="right" valign="top" width="20">
						<?php if ($display_option != "all") { ?>
						<a href="javascript:scrollNext()">
							<img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_('next');?>" border="0" />
						</a>
						<?php } ?>
					</td>
				</tr>
				</table>
			</td>
			<td align="right"><em><?php echo $AppUI->_('Quick Date Filter'); ?>:</em></td>
			<td align="right">
				<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td align="right">
						<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('show this month');?>" onclick='javascript:showThisMonth()' />
					&nbsp;</td>
					<td align="right">
						<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('show full project');?>" onclick='javascript:showFullProject()' />
					&nbsp;</td>
				</tr>
				</table>
			</td>
			
		</tr>

		<tr> <!--  Task selection options plus Print to PDF go in this row -->
			<td align="right"><em><?php echo $AppUI->_('Display mode'); ?>:</em></td>
		<!--  show mode  -->
			<td align="right">
				<table border="0" cellpadding="4" cellspacing="0">
				<tr><td width="210">
		<!--		<label for="ganttTaskFilter"><?php //echo $AppUI->_('Filter:')?></label>&nbsp;-->
				<select name="mode" id="mode" class="text" onchange="javascript:submitIt()" size="1">
					<?php 						
						echo '<option value="0"'
								.(($mode ==  0) ? ' selected="selected">' : '>')
								. $AppUI->_('Grouped') . '</option>';
						echo "\n";
						echo '<option value="2"'
								.(($mode ==  2) ? ' selected="selected">' : '>')
								. $AppUI->_('Simplified') . '</option>';
						echo "\n";
						echo '<option value="1"'
								.(($mode ==  1) ? ' selected="selected">' : '>')
								. $AppUI->_('Separated') . '</option>';
						echo "\n";
					?>
				</select>
				</td>
				<td align="right" valign="top" width="20">&nbsp;</td>
				</tr>
				</table>
			</td>
			<td align="right"><em><?php echo $AppUI->_('Print to PDF'); ?>:</em></td>
			<td align="right">
				<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td align="right">
						<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('low resolution');?>" onclick='javascript:printPDF()' />
					&nbsp;</td>
					<td align="right">
						<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('high resolution');?>" onclick='javascript:printPDFHR()' />
					&nbsp;</td>
				</tr>
				</table>
			</td>
			
		</tr>
		<tr>
		<td align="right"><em><?php echo $AppUI->_('Task Viewable'); ?>:</em></td>
		<!--  task viewable  -->
			<td align="right">
				<table border="0" cellpadding="4" cellspacing="0">
				<tr><td width="210">

				<select name="showfinancial" id="showfinancial" class="text" onchange="javascript:submitIt()" size="1">
					<?php 						
						echo '<option value="0"'
								.(($showfinancial ==  0) ? ' selected="selected">' : '>')
								. $AppUI->_('All') . '</option>';
						echo "\n";
						echo '<option value="1"'
								.(($showfinancial ==  1) ? ' selected="selected">' : '>')
								. $AppUI->_('Only Administrative') . '</option>';
						echo "\n";
						echo '<option value="2"'
								.(($showfinancial ==  2) ? ' selected="selected">' : '>')
								. $AppUI->_('Only Financial') . '</option>';
						echo "\n";
					?>
				</select>
				</td>
				<td align="right" valign="top" width="20">&nbsp;</td>
				</tr>
				</table>
			</td>
			<td align="right">
			</td>
			<td align="right">
			</td>
		</tr>
		<tr align="left"> <!--  Additional Gantt FOrmatting options go in this row. (show/hide behaviour) -->
			<th colspan="4" align="left"><em><a href="javascript:doMenu('ganttoptions')" id="xganttoptions"><?php echo $AppUI->_('Show Additional Gantt Options'); ?></a></em></th>
		</tr>
		<tr align="left">
			<td colspan="4">
			<table border="0" id="ganttoptions" style="display:none" width="100%" align="center"><tr><td width="100%">
			<table  border="0" cellpadding="2" cellspacing="0" width="100%" align="center">
					<tr>
						<td>&nbsp;<?php echo $AppUI->_('Tasks'); ?>&nbsp;:</td>

					<!-- sort tasks by name (instead of date) -->					
						<td valign="top">
							<input type="checkbox" name="sortByName" id="sortByName" <?php echo (($sortByName == 1) ? 'checked="checked"' : ''); ?> />
							<label for="sortByName"><?php echo $AppUI->_('Sort by Name'); ?></label>
						</td>

					<!-- show task names only -->	
						<td valign="top">
							<input type="checkbox" name="showTaskNameOnly" id="showTaskNameOnly" <?php echo (($showTaskNameOnly == 1) ? 'checked="checked"' : ''); ?> />
							<label for="showTaskNameOnly"><?php echo $AppUI->_('Show names only'); ?></label>
						</td>
						
					<!--  use monoSpace Font (recommended when showing task names only) -->
						<td valign="top">
							<input type="checkbox" name="monospacefont" id="monospacefont" <?php echo (($monospacefont == 1) ? 'checked="checked"' : ''); ?> />
							<label for="monospacefont"><?php echo $AppUI->_('Use MonoSpace Font'); ?></label>
						</td>
					
					<!--  add links to gantt -->	
						<td valign="top">
							<input type="checkbox" name="addLinksToGantt" id="addLinksToGantt" <?php echo (($addLinksToGantt == 1) ? 'checked="checked"' : ''); ?> />
							<label for="addLinksToGantt"><?php echo $AppUI->_('Add links to Gantt'); ?></label>
						</td>
						
						<td colspan=2 rowspan=2 valign="middle">&nbsp;<input type="button" style="float: right; width: 110px;" class="button" value="<?php echo $AppUI->_('submit');?>" onclick='javascript:submitIt()' />	</td>
					</tr>
					<tr class="tbl" >
						<td>&nbsp;<?php echo $AppUI->_('Other'); ?>&nbsp;:</td>
			
					<!-- show no milestones -->	
						<td class="alternate" valign="top">
							<input type="checkbox" name="showNoMilestones" id="showNoMilestones" <?php echo (($showNoMilestones == 1) ? 'checked="checked"' : ''); ?> />
							<label for="showNoMilestones"><?php echo $AppUI->_('Hide Milestones'); ?></label>
						</td>

					<!-- show horizontal grid --> 
						<td class="alternate" valign="top">
							<input type="checkbox" name="showhgrid" id="showhgrid" <?php echo (($showhgrid == 1) ? 'checked="checked"' : ''); ?> />
							<label for="showhgrid"><?php echo $AppUI->_('Show horizontal grid'); ?></label>
						</td>
						
						<td  class="alternate" valign="top">
							<input type="checkbox" name="showLabels" id="showLabels" <?php	echo (($showLabels == 1) ? 'checked="checked"' : ''); ?> />
							<label for="showLabels"><?php echo $AppUI->_('Show captions'); ?></label>
						</td>
						
						<td class="alternate" valign="top">
							<input type="checkbox" name="showWork" id="showWork" <?php echo (($showTaskNameOnly == 1) ? 'disabled="disabled"': ''); echo (($showWork == 1) ? 'checked="checked"' : ''); ?> />
							<label for="showWork"><?php echo $AppUI->_('Show work instead of duration (Hours)'); ?></label>
		<!--				</td>-->
						
		<!--			<td class="alternate" valign="top">-->
		<!--				<input type="checkbox" name="showWork_days" id="showWork_days" <?php //echo (($showWork_days == 1) ? 'checked="checked"' : ''); ?> />-->
		<!--				<label for="showWork_days"><?php //echo $AppUI->_('Show work instead of duration (Days)'); ?></label>-->
						</td>
		<!--				<td class="alternate" align="right">
							&nbsp;&nbsp;<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('submit');?>" onclick='javascript:submitIt()' />
						</td>
					</tr>
		-->
		<?php //////////////////// New checkboxes with additional formatting go above, this is with the view of displaying the options in an ajax box in the future ////////////////////////////////////////// 
		?>
					<?php if($a == 'todo') { ?>
					<input type="hidden" name="show_form" value="1" />
					<tr>
							<td>&nbsp;<?php echo $AppUI->_('To Do Options'); ?>:&nbsp;</td>
							<td  valign="bottom" nowrap="nowrap">
								<input type="checkbox" name="showPinned" id="showPinned" <?php echo $showPinned ? 'checked="checked"' : ''; ?> />
								<label for="showPinned"><?php echo $AppUI->_('Pinned Only'); ?></label>
							</td>
							<td valign="bottom" nowrap="nowrap">
								<input type="checkbox" name="showArcProjs" id="showArcProjs" <?php echo $showArcProjs ? 'checked="checked"' : ''; ?> />
								<label for="showArcProjs"><?php echo $AppUI->_('Archived Projects'); ?></label>
							</td>
							<td  valign="bottom" nowrap="nowrap">
								<input type="checkbox" name="showHoldProjs" id="showHoldProjs" <?php echo $showHoldProjs ? 'checked="checked"' : ''; ?> />
								<label for="showHoldProjs"><?php echo $AppUI->_('Projects on Hold'); ?></label>
							</td>
							<td valign="bottom" nowrap="nowrap">
								<input type="checkbox" name="showDynTasks" id="showDynTasks" <?php echo $showDynTasks ? 'checked="checked"' : ''; ?> />
								<label for="showDynTasks"><?php echo $AppUI->_('Dynamic Tasks'); ?></label>
							</td>
							<td valign="bottom" nowrap="nowrap">
								<input type="checkbox" name="showLowTasks" id="showLowTasks" <?php echo $showLowTasks ? 'checked="checked"' : ''; ?> />
								<label for="showLowTasks"><?php echo $AppUI->_('Low Priority Tasks'); ?></label>
							</td>
							<td>&nbsp;</td>
					</tr>
				<?php } ?>
			</table></td></tr></table>
		</td></tr>
			
		</table>
		</form>
		</div> <!-- end of div used to show/hide formatting options -->
		<br />
		<br />
		<table cellspacing="0" cellpadding="0" border="1" align="center">
		<tr>
			<td valign="top" align="center">
		<?php } ?>
		<?php
		if ($a != 'todo') {
			$q = new DBQuery;
			$q->addTable('tasks');
			$q->addQuery('COUNT(*) AS N');
			$q->addWhere('task_project=' . $project_id);
			$cnt = $q->loadList();
			$q->clear();
		} else {
			$cnt[0]['N'] = ((empty($tasks)) ? 0 : 1);
		}
		///////////////////////////////////new check box variables need to be passed here to gantt.php ////////////////////////////////////////////////////////////////////////////////////////
		if ($cnt[0]['N'] > 0) {
		/*
			<Script>
			if (navigator.appName == 'Netscape' && document.layers != null) {
				wid = window.innerWidth;
				hit = window.innerHeight;
			}
			if (document.all != null){
				wid = document.body.clientWidth;
				hit = document.body.clientHeight;
			}
			document.write('Height '+hit+', Width '+wid);
			</script>
		*/
		//	include 'gantt.php';
			if($mode == 1)
			{
			$src = ('?m=tasks&amp;a=gantt&amp;suppressHeaders=1&amp;project_id=' . $project_id 
					. (($display_option == 'all') ? '' 
					   : ('&amp;start_date=' . $start_date->format('%Y-%m-%d') 
						  . '&amp;end_date=' . $end_date->format('%Y-%m-%d'))) 
					. "&amp;width=' + ((navigator.appName=='Netscape'" 
					. "?window.innerWidth:document.body.offsetWidth)*0.95) + '" 
					. '&amp;showLabels=' . $showLabels . '&amp;showWork=' . $showWork 
					. '&amp;sortByName=' . $sortByName . '&amp;showTaskNameOnly=' . $showTaskNameOnly 
					. '&amp;showhgrid=' . $showhgrid . '&amp;showPinned=' . $showPinned 
					. '&amp;showArcProjs=' . $showArcProjs . '&amp;showHoldProjs=' . $showHoldProjs 
					. '&amp;showDynTasks=' . $showDynTasks . '&amp;showLowTasks=' . $showLowTasks 
					. '&amp;caller=' . $a . '&amp;user_id=' . $user_id
				. '&amp;printpdf=' . $printpdf . '&amp;showNoMilestones=' . $showNoMilestones
				. '&amp;addLinksToGantt=' . $addLinksToGantt . '&amp;ganttTaskFilter=' . $ganttTaskFilter
				. '&amp;monospacefont=' . $monospacefont . '&amp;showWork_days=' . $showWork_days
				. '&amp;mode=' . $mode . '&amp;showfinancial=' . $showfinancial);
			if ($addLinksToGantt == 1) {
				?>
				<iframe width="980px" height="500px" align="middle" src="<?php echo DP_BASE_URL . '/index.php' . $src; ?>" title="Please wait while the Gantt Chart is generated. (this might take up to a couple of minutes to complete)"><?php echo $AppUI->_('Your current browser does not support frames. As a result this feature is not available.'); ?></iframe>
				<?php
			} else {
				?>
				<script language="javascript" type="text/javascript"> document.write('<img alt="<?php echo $AppUI->_('Please wait while the Gantt chart is generated... (this might take a minute or two)'); ?>" src="<?php echo $src; ?>" />') </script>
				<?php
			}

			//If we have a problem displaying this we need to display a warning.
			//Put it at the bottom just in case
			if (! dPcheckMem(32*1024*1024)) {
				echo "</td>\n</tr>\n<tr>\n<td>";
				echo '<span style="color: red; font-weight: bold;">' . $AppUI->_('invalid memory config') . '</span>';
				echo "\n";
			}
			} 
		}
		else {
			echo $AppUI->_('No tasks to display');
		}
	}
	if($mode == 0){//mode together task
		$src = ('?m=tasks&amp;a=gantt&amp;suppressHeaders=1&amp;project_id=' . $project_id 
				. (($display_option == 'all') ? '' 
				   : ('&amp;start_date=' . $start_date->format('%Y-%m-%d') 
					  . '&amp;end_date=' . $end_date->format('%Y-%m-%d'))) 
				. "&amp;width=' + ((navigator.appName=='Netscape'" 
				. "?window.innerWidth:document.body.offsetWidth)*0.95) + '" 
				. '&amp;showLabels=' . $showLabels . '&amp;showWork=' . $showWork 
				. '&amp;sortByName=' . $sortByName . '&amp;showTaskNameOnly=' . $showTaskNameOnly 
				. '&amp;showhgrid=' . $showhgrid . '&amp;showPinned=' . $showPinned 
				. '&amp;showArcProjs=' . $showArcProjs . '&amp;showHoldProjs=' . $showHoldProjs 
				. '&amp;showDynTasks=' . $showDynTasks . '&amp;showLowTasks=' . $showLowTasks 
				. '&amp;caller=' . $a . '&amp;user_id=' . $user_id
			. '&amp;printpdf=' . $printpdf . '&amp;showNoMilestones=' . $showNoMilestones
			. '&amp;addLinksToGantt=' . $addLinksToGantt . '&amp;ganttTaskFilter=' . $ganttTaskFilter
			. '&amp;monospacefont=' . $monospacefont . '&amp;showWork_days=' . $showWork_days
			.'&amp;macroproject_id=' . $macroproject_id . '&amp;mode=' . $mode . '&amp;showfinancial=' . $showfinancial);//a ajouter si on fait un affichage tache mélanger
		if ($addLinksToGantt == 1) {
			?>
			<iframe width="980px" height="500px" align="middle" src="<?php echo DP_BASE_URL . '/index.php' . $src; ?>" title="Please wait while the Gantt Chart is generated. (this might take up to a couple of minutes to complete)"><?php echo $AppUI->_('Your current browser does not support frames. As a result this feature is not available.'); ?></iframe>
			<?php
		} else {
			?>
			<script language="javascript" type="text/javascript"> document.write('<img alt="<?php echo $AppUI->_('Please wait while the Gantt chart is generated... (this might take a minute or two)'); ?>" src="<?php echo $src; ?>" />') </script>
			<?php
		}

		//If we have a problem displaying this we need to display a warning.
		//Put it at the bottom just in case
		if (! dPcheckMem(32*1024*1024)) {
			echo "</td>\n</tr>\n<tr>\n<td>";
			echo '<span style="color: red; font-weight: bold;">' . $AppUI->_('invalid memory config') . '</span>';
			echo "\n";
		}
	}
	if($mode == 2){//mode condense
		$src = ('?m=tasks&amp;a=gantt&amp;suppressHeaders=1&amp;project_id=' . $project_id 
				. (($display_option == 'all') ? '' 
				   : ('&amp;start_date=' . $start_date->format('%Y-%m-%d') 
					  . '&amp;end_date=' . $end_date->format('%Y-%m-%d'))) 
				. "&amp;width=' + ((navigator.appName=='Netscape'" 
				. "?window.innerWidth:document.body.offsetWidth)*0.95) + '" 
				. '&amp;showLabels=' . $showLabels . '&amp;showWork=' . $showWork 
				. '&amp;sortByName=' . $sortByName . '&amp;showTaskNameOnly=' . $showTaskNameOnly 
				. '&amp;showhgrid=' . $showhgrid . '&amp;showPinned=' . $showPinned 
				. '&amp;showArcProjs=' . $showArcProjs . '&amp;showHoldProjs=' . $showHoldProjs 
				. '&amp;showDynTasks=' . $showDynTasks . '&amp;showLowTasks=' . $showLowTasks 
				. '&amp;caller=' . $a . '&amp;user_id=' . $user_id
			. '&amp;printpdf=' . $printpdf . '&amp;showNoMilestones=' . $showNoMilestones
			. '&amp;addLinksToGantt=' . $addLinksToGantt . '&amp;ganttTaskFilter=' . $ganttTaskFilter
			. '&amp;monospacefont=' . $monospacefont . '&amp;showWork_days=' . $showWork_days
			.'&amp;macroproject_id=' . $macroproject_id . '&amp;mode=' . $mode . '&amp;showfinancial=' . $showfinancial);//a ajouter si on fait un affichage tache mélanger
		if ($addLinksToGantt == 1) {
			?>
			<iframe width="980px" height="500px" align="middle" src="<?php echo DP_BASE_URL . '/index.php' . $src; ?>" title="Please wait while the Gantt Chart is generated. (this might take up to a couple of minutes to complete)"><?php echo $AppUI->_('Your current browser does not support frames. As a result this feature is not available.'); ?></iframe>
			<?php
		} else {
			?>
			<script language="javascript" type="text/javascript"> document.write('<img alt="<?php echo $AppUI->_('Please wait while the Gantt chart is generated... (this might take a minute or two)'); ?>" src="<?php echo $src; ?>" />') </script>
			<?php
		}

		//If we have a problem displaying this we need to display a warning.
		//Put it at the bottom just in case
		if (! dPcheckMem(32*1024*1024)) {
			echo "</td>\n</tr>\n<tr>\n<td>";
			echo '<span style="color: red; font-weight: bold;">' . $AppUI->_('invalid memory config') . '</span>';
			echo "\n";
		}
	}
}
else{

if(count($projectsofmacro) != 0)
{
		foreach($projectsofmacro as $project)
		{
			if (isset($_GET['macroproject_id'])){
			$project_id = $project['project_id'];
			}
		}
}
/**
  * prepare the array with the tasks to display in the task filter
  * (for the most part this is code harvested from gantt.php)
  * 
  */ 
$filter_task_list = array();
$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('project_id, project_color_identifier, project_name' 
             . ', project_start_date, project_end_date');
$q->addJoin('tasks', 't1', 'project_id = t1.task_project');
$q->addWhere('project_status != 7');
$q->addGroup('project_id');
$q->addOrder('project_name');
//$projects->setAllowedSQL($AppUI->user_id, $q);
$projects = $q->loadHashList('project_id');
$q->clear();

$q->addTable('tasks', 't');
$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
$q->addQuery('t.task_id, task_parent, task_name, task_start_date, task_end_date' 
             . ', task_duration, task_duration_type, task_priority, task_percent_complete' 
             . ', task_order, task_project, task_milestone, project_name, task_dynamic');

$q->addWhere('project_status != 7 AND task_dynamic = 1');
if ($project_id) {
	$q->addWhere('task_project = ' . $project_id);
}
$task =& new CTask;
$task->setAllowedSQL($AppUI->user_id, $q);
$proTasks = $q->loadHashList('task_id');
$q->clear();
$filter_task_list = array ();
$orrarr[] = array('task_id'=>0, 'order_up'=>0, 'order'=>'');
foreach ($proTasks as $row) {
	$projects[$row['task_project']]['tasks'][] = $row;
}
unset($proTasks);
$parents = array();
if($i <= 1)
{
function showfiltertask(&$a, $level=0) {
	/* Add tasks to the filter task aray */
	global $filter_task_list, $parents;
	$filter_task_list[] = array($a, $level);
	$parents[$a['task_parent']] = true;
}
function findfiltertaskchild(&$tarr, $parent, $level=0) {
	GLOBAL $projects, $filter_task_list;
	$level = $level + 1;
	$n = count($tarr);
	for ($x=0; $x < $n; $x++) {
		if ($tarr[$x]['task_parent'] == $parent && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']){
			showfiltertask($tarr[$x], $level);
			findfiltertaskchild($tarr, $tarr[$x]['task_id'], $level);
		}
	}
}
}
foreach ($projects as $p) {
	global $parents, $task_id;
	$parents = array();
	$tnums = count($p['tasks']);
	for ($i=0; $i < $tnums; $i++) {
		$t = $p['tasks'][$i];
		if (!(isset($parents[$t['task_parent']]))) {
			$parents[$t['task_parent']] = false;
		}
		if ($t['task_parent'] == $t['task_id']) {
			showfiltertask($t);
			findfiltertaskchild($p['tasks'], $t['task_id']);
		}
	}
	// Check for ophans.
	foreach ($parents as $id => $ok) {
		if (!($ok)) {
			findfiltertaskchild($p['tasks'], $id);
		}
	}
}
/**
 * the results of the above bits are stored in $filter_task_list (array)
 * 
 */

// months to scroll
$scroll_date = 1;

$display_option = dPgetCleanParam($_POST, 'display_option', 'all');

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

if ($display_option == 'custom') {
	// custom dates
	$start_date = ((intval($sdate)) ? new CDate($sdate) : new CDate());
	$end_date = ((intval($edate)) ? new CDate($edate) : new CDate());
} else {
	// month
	$start_date = new CDate();
	$start_date->day = 1;
   	$end_date = new CDate($start_date);
	$end_date->addMonths($scroll_date);
}

// setup the title block
if (!@$min_view) {
	$titleBlock = new CTitleBlock('Gantt Chart', 'applet-48.png', $m, "$m.$a");
	$titleBlock->addCrumb('?m=tasks', 'tasks list');
	$titleBlock->addCrumb(('?m=projects&amp;a=view&amp;project_id=' . $project_id), 'view this project');
	//$titleBlock->addCrumb('#" onclick="javascript:toggleLayer(\'displayOptions\');', 'show/hide display options');
	$titleBlock->show();
}
?>
<script language="javascript" type="text/javascript">
// <![CDATA[
var calendarField = "";

function popCalendar(field) {
	calendarField = field;
	idate = eval("document.editFrm." + field + ".value");
	window.open('?m=public&'+'a=calendar&'+'dialog=1&'+'callback=setCalendar&'+'date=' + idate, 
	            "calwin", "width=250, height=230, scrollbars=no, status=no");
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar(idate, fdate) {
	fld_date = eval("document.editFrm." + calendarField);
	fld_fdate = eval("document.editFrm.show_" + calendarField);
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

function scrollPrev() {
	f = document.editFrm;
<?php
	$new_start = new CDate($start_date);	
	$new_start->day = 1;
	$new_end = new CDate($end_date);
	$new_start->addMonths(-$scroll_date);
	$new_end->addMonths(-$scroll_date);
	echo ('f.sdate.value="' . $new_start->format(FMT_TIMESTAMP_DATE) . '";');
	echo ('f.edate.value="' . $new_end->format(FMT_TIMESTAMP_DATE) . '";');
?>
	document.editFrm.display_option.value = "custom";
	f.submit()
}

function scrollNext() {
	f = document.editFrm;
<?php
	$new_start = new CDate($start_date);	
	$new_start->day = 1;
	$new_end = new CDate($end_date);
	$new_start->addMonths($scroll_date);
	$new_end->addMonths($scroll_date);
	echo ('f.sdate.value="' . $new_start->format(FMT_TIMESTAMP_DATE) . '";');
	echo ('f.edate.value="' . $new_end->format(FMT_TIMESTAMP_DATE) . '";');
?>
	document.editFrm.display_option.value = "custom";
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	f.submit()
}

function showThisMonth() {
	document.editFrm.display_option.value = "this_month";
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}

function showFullProject() {
	document.editFrm.display_option.value = "all";
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}
function toggleLayer( whichLayer ) {
	var elem, vis;
	//if( document.getElementById ) // this is the way the standards work
		elem = document.getElementById( whichLayer );
	//else if( document.all ) // this is the way old msie versions work
	//	elem = document.all[whichLayer];
	//else if( document.layers ) // this is the way nn4 works
	//	elem = document.layers[whichLayer];
	vis = elem.style;
	// if the style.display value is blank we try to figure it out here
	//if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
	//	vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
		vis.display = (vis.display==''||vis.display=='block')?'none':'block';
}
function printPDF() {
	document.editFrm.printpdf.value = "1";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}
function printPDFHR() {
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "1";
	document.editFrm.submit();
}
function submitIt() {
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}
function doMenu(item) { 
	obj=document.getElementById(item); 
	col=document.getElementById("x" + item); 
	if (obj.style.display=="none") { 
		obj.style.display="block"; 
		col.innerHTML="<?php echo $AppUI->_('Hide Additional Gantt Options'); ?>"; 
	} else { 
		obj.style.display="none"; 
		col.innerHTML="<?php echo $AppUI->_('Show Additional Gantt Options'); ?>"; 
	}
} 

//]]>
</script>

<?php ////////////////////// New checkboxes with additional formatting go here, this is with the view of displaying the options in an ajax box in the future /////////////////////////// -->
?>
<div id="displayOptions" style="display:block"> <!-- start of div used to show/hide formatting options -->
<br />
<form name="editFrm" method="post" action="?<?php 
echo ('m=' . $m . '&amp;a=' . $a . '&amp=tab=' . $tab . '&amp;project_id=' . $project_id . '&amp;showfinancial=' . $showfinancial); ?>">
<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
<input type="hidden" name="printpdf" value="<?php echo $printpdf; ?>" />
<input type="hidden" name="printpdfhr" value="<?php echo $printpdfhr; ?>" />
<input type="hidden" name="caller" value="<?php echo $a; ?>" />
<table border="0" align="center" class="tbl" border="0" cellpadding="2" cellspacing="0" style="min-width:990px">
<tr> <!--  Date selection options go in this row -->
	<td align="right"><em><?php echo $AppUI->_('Date Filter'); ?>:</em></td>
	<td align="right">
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td align="left" valign="top" width="20">
				<?php if ($display_option != "all") { ?>
				<a href="javascript:scrollPrev()">
					<img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_('previous');?>" border="0" />
				</a>
				<?php } ?>
			</td>
		
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('From');?>:</td>
			<td align="left" nowrap="nowrap">
				<input type="hidden" name="sdate" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
				<input type="text" class="text" name="show_sdate" value="<?php echo $start_date->format($df);?>" size="12" disabled="disabled" />
				<a href="javascript:popCalendar('sdate')">
					<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
				</a>
			</td>
		
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To');?>:</td>
			<td align="left" nowrap="nowrap">
				<input type="hidden" name="edate" value="<?php echo $end_date->format(FMT_TIMESTAMP_DATE);?>" />
				<input type="text" class="text" name="show_edate" value="<?php echo $end_date->format($df);?>" size="12" disabled="disabled" />
				<a href="javascript:popCalendar('edate')">
					<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
				</a>
			</td>
			<td align="left">
				<input type="button" class="button" value="<?php echo $AppUI->_('submit custom date');?>" onclick='document.editFrm.display_option.value="custom";document.editFrm.printpdf.value="0";submit();'/>
			</td>
		
			<td align="right" valign="top" width="20">
				<?php if ($display_option != "all") { ?>
			  	<a href="javascript:scrollNext()">
			  		<img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_('next');?>" border="0" />
			  	</a>
				<?php } ?>
			</td>
		</tr>
		</table>
	</td>
	<td align="right"><em><?php echo $AppUI->_('Quick Date Filter'); ?>:</em></td>
	<td align="right">
		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td align="right">
				<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('show this month');?>" onclick='javascript:showThisMonth()' />
			&nbsp;</td>
			<td align="right">
				<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('show full project');?>" onclick='javascript:showFullProject()' />
			&nbsp;</td>
		</tr>
		</table>
	</td>
	
</tr>

<tr> <!--  Task selection options plus Print to PDF go in this row -->
	<td align="right"><em><?php echo $AppUI->_('Task Filter'); ?>:</em></td>
<!--  task filter  -->
	<td align="right">
		<table border="0" cellpadding="4" cellspacing="0">
		<tr><td width="210">
<!--		<label for="ganttTaskFilter"><?php //echo $AppUI->_('Filter:')?></label>&nbsp;-->
		<select name="ganttTaskFilter" id="ganttTaskFilter" class="text" onchange="javascript:submitIt()" size="1">
			<?php 
				echo '<option value="0" '. (($ganttTaskFilter == '' OR $ganttTaskFilter == 0) ? ' selected="selected">' : '>') . '&lt;'.$AppUI->_('Show all tasks').'&gt; </option>';
				echo "\n";	
				for ($i =0; $i < count($filter_task_list); $i++) {
					$filter_task_name = $filter_task_list[$i][0]['task_name'];
					$filter_task_level = $filter_task_list[$i][1];
					$filter_task_name = ((strlen($filter_task_name) > 71) ? substr($filter_task_name, 0, (68 - $filter_task_level)) . '...': $filter_task_name);
					for ($ii = 1; $ii <= $filter_task_level; $ii++) {
						$filter_task_name = '&nbsp;&nbsp;'. $filter_task_name ;
								}
					echo ('<option value="' . $filter_task_list[$i][0]['task_id'].'"'
						.(($ganttTaskFilter == $filter_task_list[$i][0]['task_id']) ? ' selected="selected">' : '>')
						. $filter_task_name . '</option>');
					echo "\n";
					$filter_task_name = '';
					$filter_task_level = '';
				}?>
		</select>
		</td>
		<td align="right" valign="top" width="20">&nbsp;</td>
		</tr>
		</table>
	</td>
	<td align="right"><em><?php echo $AppUI->_('Print to PDF'); ?>:</em></td>
	<td align="right">
		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td align="right">
				<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('low resolution');?>" onclick='javascript:printPDF()' />
			&nbsp;</td>
			<td align="right">
				<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('high resolution');?>" onclick='javascript:printPDFHR()' />
			&nbsp;</td>
		</tr>
		</table>
	</td>
	
</tr>
<tr>
		<td align="right"><em><?php echo $AppUI->_('Task Viewable'); ?>:</em></td>
		<!--  task viewable  -->
			<td align="right">
				<table border="0" cellpadding="4" cellspacing="0">
				<tr><td width="210">

				<select name="showfinancial" id="showfinancial" class="text" onchange="javascript:submitIt()" size="1">
					<?php 						
						echo '<option value="0"'
								.(($showfinancial ==  0) ? ' selected="selected">' : '>')
								. $AppUI->_('All') . '</option>';
						echo "\n";
						echo '<option value="1"'
								.(($showfinancial ==  1) ? ' selected="selected">' : '>')
								. $AppUI->_('Only Administrative') . '</option>';
						echo "\n";
						echo '<option value="2"'
								.(($showfinancial ==  2) ? ' selected="selected">' : '>')
								. $AppUI->_('Only Financial') . '</option>';
						echo "\n";
					?>
				</select>
				</td>
				<td align="right" valign="top" width="20">&nbsp;</td>
				</tr>
				</table>
			</td>
			<td align="right">
			</td>
			<td align="right">
			</td>
</tr>

<tr align="left"> <!--  Additional Gantt FOrmatting options go in this row. (show/hide behaviour) -->
	<th colspan="4" align="left"><em><a href="javascript:doMenu('ganttoptions')" id="xganttoptions"><?php echo $AppUI->_('Show Additional Gantt Options'); ?></a></em></th>
</tr>
<tr align="left">
	<td colspan="4">
	<table border="0" id="ganttoptions" style="display:none" width="100%" align="center"><tr><td width="100%">
	<table  border="0" cellpadding="2" cellspacing="0" width="100%" align="center">
			<tr>
				<td>&nbsp;<?php echo $AppUI->_('Tasks'); ?>&nbsp;:</td>

			<!-- sort tasks by name (instead of date) -->					
				<td valign="top">
					<input type="checkbox" name="sortByName" id="sortByName" <?php echo (($sortByName == 1) ? 'checked="checked"' : ''); ?> />
					<label for="sortByName"><?php echo $AppUI->_('Sort by Name'); ?></label>
				</td>

			<!-- show task names only -->	
				<td valign="top">
					<input type="checkbox" name="showTaskNameOnly" id="showTaskNameOnly" <?php echo (($showTaskNameOnly == 1) ? 'checked="checked"' : ''); ?> />
					<label for="showTaskNameOnly"><?php echo $AppUI->_('Show names only'); ?></label>
				</td>
				
			<!--  use monoSpace Font (recommended when showing task names only) -->
				<td valign="top">
					<input type="checkbox" name="monospacefont" id="monospacefont" <?php echo (($monospacefont == 1) ? 'checked="checked"' : ''); ?> />
					<label for="monospacefont"><?php echo $AppUI->_('Use MonoSpace Font'); ?></label>
				</td>
			
			<!--  add links to gantt -->	
				<td valign="top">
					<input type="checkbox" name="addLinksToGantt" id="addLinksToGantt" <?php echo (($addLinksToGantt == 1) ? 'checked="checked"' : ''); ?> />
					<label for="addLinksToGantt"><?php echo $AppUI->_('Add links to Gantt'); ?></label>
				</td>
				
				<td colspan=2 rowspan=2 valign="middle">&nbsp;<input type="button" style="float: right; width: 110px;" class="button" value="<?php echo $AppUI->_('submit');?>" onclick='javascript:submitIt()' />	</td>
			</tr>
			<tr class="tbl" >
				<td>&nbsp;<?php echo $AppUI->_('Other'); ?>&nbsp;:</td>
	
			<!-- show no milestones -->	
				<td class="alternate" valign="top">
					<input type="checkbox" name="showNoMilestones" id="showNoMilestones" <?php echo (($showNoMilestones == 1) ? 'checked="checked"' : ''); ?> />
					<label for="showNoMilestones"><?php echo $AppUI->_('Hide Milestones'); ?></label>
				</td>

			<!-- show horizontal grid --> 
				<td class="alternate" valign="top">
					<input type="checkbox" name="showhgrid" id="showhgrid" <?php echo (($showhgrid == 1) ? 'checked="checked"' : ''); ?> />
					<label for="showhgrid"><?php echo $AppUI->_('Show horizontal grid'); ?></label>
				</td>
				
				<td  class="alternate" valign="top">
					<input type="checkbox" name="showLabels" id="showLabels" <?php	echo (($showLabels == 1) ? 'checked="checked"' : ''); ?> />
					<label for="showLabels"><?php echo $AppUI->_('Show captions'); ?></label>
				</td>
				
				<td class="alternate" valign="top">
					<input type="checkbox" name="showWork" id="showWork" <?php echo (($showTaskNameOnly == 1) ? 'disabled="disabled"': ''); echo (($showWork == 1) ? 'checked="checked"' : ''); ?> />
					<label for="showWork"><?php echo $AppUI->_('Show work instead of duration (Hours)'); ?></label>
<!--				</td>-->
				
<!--			<td class="alternate" valign="top">-->
<!--				<input type="checkbox" name="showWork_days" id="showWork_days" <?php //echo (($showWork_days == 1) ? 'checked="checked"' : ''); ?> />-->
<!--				<label for="showWork_days"><?php //echo $AppUI->_('Show work instead of duration (Days)'); ?></label>-->
	 			</td>
<!--				<td class="alternate" align="right">
					&nbsp;&nbsp;<input type="button" style="width: 110px;" class="button" value="<?php echo $AppUI->_('submit');?>" onclick='javascript:submitIt()' />
				</td>
			</tr>
-->
<?php //////////////////// New checkboxes with additional formatting go above, this is with the view of displaying the options in an ajax box in the future ////////////////////////////////////////// 
?>
			<?php if($a == 'todo') { ?>
			<input type="hidden" name="show_form" value="1" />
			<tr>
					<td>&nbsp;<?php echo $AppUI->_('To Do Options'); ?>:&nbsp;</td>
					<td  valign="bottom" nowrap="nowrap">
						<input type="checkbox" name="showPinned" id="showPinned" <?php echo $showPinned ? 'checked="checked"' : ''; ?> />
						<label for="showPinned"><?php echo $AppUI->_('Pinned Only'); ?></label>
					</td>
					<td valign="bottom" nowrap="nowrap">
						<input type="checkbox" name="showArcProjs" id="showArcProjs" <?php echo $showArcProjs ? 'checked="checked"' : ''; ?> />
						<label for="showArcProjs"><?php echo $AppUI->_('Archived Projects'); ?></label>
					</td>
					<td  valign="bottom" nowrap="nowrap">
						<input type="checkbox" name="showHoldProjs" id="showHoldProjs" <?php echo $showHoldProjs ? 'checked="checked"' : ''; ?> />
						<label for="showHoldProjs"><?php echo $AppUI->_('Projects on Hold'); ?></label>
					</td>
					<td valign="bottom" nowrap="nowrap">
						<input type="checkbox" name="showDynTasks" id="showDynTasks" <?php echo $showDynTasks ? 'checked="checked"' : ''; ?> />
						<label for="showDynTasks"><?php echo $AppUI->_('Dynamic Tasks'); ?></label>
					</td>
					<td valign="bottom" nowrap="nowrap">
						<input type="checkbox" name="showLowTasks" id="showLowTasks" <?php echo $showLowTasks ? 'checked="checked"' : ''; ?> />
						<label for="showLowTasks"><?php echo $AppUI->_('Low Priority Tasks'); ?></label>
					</td>
					<td>&nbsp;</td>
			</tr>
		<?php } ?>
	</table></td></tr></table>
</td></tr>

</table>
</form>
</div> <!-- end of div used to show/hide formatting options -->
<br />
<br />
<table cellspacing="0" cellpadding="0" border="1" align="center">
<tr>
	<td valign="top" align="center">
<?php
if ($a != 'todo') {
	$q = new DBQuery;
	$q->addTable('tasks');
	$q->addQuery('COUNT(*) AS N');
	$q->addWhere('task_project=' . $project_id);
	$cnt = $q->loadList();
	$q->clear();
} else {
	$cnt[0]['N'] = ((empty($tasks)) ? 0 : 1);
}
///////////////////////////////////new check box variables need to be passed here to gantt.php ////////////////////////////////////////////////////////////////////////////////////////
if ($cnt[0]['N'] > 0) {
/*
    <Script>
    if (navigator.appName == 'Netscape' && document.layers != null) {
        wid = window.innerWidth;
        hit = window.innerHeight;
    }
    if (document.all != null){
        wid = document.body.clientWidth;
        hit = document.body.clientHeight;
    }
    document.write('Height '+hit+', Width '+wid);
    </script>
*/
//	include 'gantt.php';
	
	$src = ('?m=tasks&amp;a=gantt&amp;suppressHeaders=1&amp;project_id=' . $project_id 
	        . (($display_option == 'all') ? '' 
	           : ('&amp;start_date=' . $start_date->format('%Y-%m-%d') 
	              . '&amp;end_date=' . $end_date->format('%Y-%m-%d'))) 
	        . "&amp;width=' + ((navigator.appName=='Netscape'" 
	        . "?window.innerWidth:document.body.offsetWidth)*0.95) + '" 
	        . '&amp;showLabels=' . $showLabels . '&amp;showWork=' . $showWork 
	        . '&amp;sortByName=' . $sortByName . '&amp;showTaskNameOnly=' . $showTaskNameOnly 
			. '&amp;showhgrid=' . $showhgrid . '&amp;showPinned=' . $showPinned 
	        . '&amp;showArcProjs=' . $showArcProjs . '&amp;showHoldProjs=' . $showHoldProjs 
	        . '&amp;showDynTasks=' . $showDynTasks . '&amp;showLowTasks=' . $showLowTasks 
	        . '&amp;caller=' . $a . '&amp;user_id=' . $user_id
		. '&amp;printpdf=' . $printpdf . '&amp;showNoMilestones=' . $showNoMilestones
		. '&amp;addLinksToGantt=' . $addLinksToGantt . '&amp;ganttTaskFilter=' . $ganttTaskFilter
		. '&amp;monospacefont=' . $monospacefont . '&amp;showWork_days=' . $showWork_days
		. '&amp;showfinancial=' . $showfinancial);

	if ($addLinksToGantt == 1) {
		?>
		<iframe width="980px" height="500px" align="middle" src="<?php echo DP_BASE_URL . '/index.php' . $src; ?>" title="Please wait while the Gantt Chart is generated. (this might take up to a couple of minutes to complete)"><?php echo $AppUI->_('Your current browser does not support frames. As a result this feature is not available.'); ?></iframe>
		<?php
	} else {
		?>
		<script language="javascript" type="text/javascript"> document.write('<img alt="<?php echo $AppUI->_('Please wait while the Gantt chart is generated... (this might take a minute or two)'); ?>" src="<?php echo $src; ?>" />') </script>
		<?php
	}

	//If we have a problem displaying this we need to display a warning.
	//Put it at the bottom just in case
	if (! dPcheckMem(32*1024*1024)) {
		echo "</td>\n</tr>\n<tr>\n<td>";
		echo '<span style="color: red; font-weight: bold;">' . $AppUI->_('invalid memory config') . '</span>';
		echo "\n";
	}
} else {
	echo $AppUI->_('No tasks to display');
}
}
?>
	</td>
</tr>
<?php
if ( $printpdf == 1 || $printpdfhr == 1) {
	include 'gantt_pdf.php';
}
?>
</table>
<br />
<br />
<table cellspacing="0" cellpadding="2" border="1" align="center" bgcolor="white">
<tr><th colspan="11" > <?php echo $AppUI->_('Gantt chart key'); ?>: </th></tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Dynamic Task')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_dynamic.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Task (planned)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_planned.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Task (in proggress)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_in_progress.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Task (completed)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_completed.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Task (financial)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_financial.png" alt=""/></td>
</tr>
<?php
if ($showNoMilestones != 1) {
?>
<tr>
	<td align="right"><?php echo $AppUI->_('Milestone (planned)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_planned.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Milestone (completed)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_completed.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Milestone (in progress)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_in_progress.png" alt=""/></td>
	<td align="right">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Milestone (overdue)')?>&nbsp;</td>
	<td align="center"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_overdue.png" alt=""/></td>
	<td align="right"></td>
	<td align="center"></td>
</tr>
<?php
}
?>
</table>
<br />
