<?php /* MACRO_PROJECTS departments.viewuser.macroprojects.php, v 0.1.0 2012/05/30 */
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

global $a, $addPwOiD, $addPwT, $AppUI, $cBuffer, $company_id, $department, $min_view, $m, $priority;
global $macroprojects, $tab, $user_id, $orderdir, $macroorderby, $dept_ids;

$department = isset($_GET['dept_id']) ? $_GET['dept_id'] : (isset($department) ? $department : 0);

$df = $AppUI->getPref('SHDATEFORMAT');

$mpstatus =  dPgetSysVal('MacroProjectStatus');

if (isset($_POST['macroproFilter'])) {
	$AppUI->setState('DeptMacroProjectIdxFilter',  $_POST['macroproFilter']);
}
$macroproFilter = (($AppUI->getState('DeptMacroProjectIdxFilter') !== NULL) 
              ? $AppUI->getState('DeptMacroProjectIdxFilter') : '-3');

$macroprojFilter = arrayMerge(array('-1' => 'All MacroProjects'), $mpstatus);
$macroprojFilter = arrayMerge(array('-2' => 'All w/o in progress'), $macroprojFilter);
$macroprojFilter = arrayMerge(array('-3' => 'All w/o archived'), $macroprojFilter);
natsort($macroprojFilter);

// load the companies class to retrieved denied companies
require_once($AppUI->getModuleClass('companies'));

// retrieve any state parameters
if (isset($_GET['tab'])) {
	$AppUI->setState('DeptMacroProjIdxTab', $_GET['tab']);
}

$valid_ordering = array(
	'macroproject_color_identifier',
	'company_name',
	'macroproject_name',
	'macroproject_start_date',
	'macroproject_duration',
	'macroproject_end_date',
	'macroproject_actual_end_date',
	'task_log_problem',
	'user_username',
	'total_tasks',
	'my_tasks',
	'macroproject_status',
);
if (isset($_GET['macroorderby']) && in_array($_GET['macroorderby'], $valid_ordering)) {
    $orderdir = $AppUI->getState('DeptMacroProjIdxOrderDir') ? ($AppUI->getState('DeptMacroProjIdxOrderDir')== 'asc' ? 'desc' : 'asc') : 'desc';    
    $AppUI->setState('DeptMacroProjIdxOrderBy', $_GET['macroorderby']);
    $AppUI->setState('DeptMacroProjIdxOrderDir', $orderdir);
}
$macroorderby  = $AppUI->getState('DeptMacroProjIdxOrderBy') ? $AppUI->getState('DeptMacroProjIdxOrderBy') : 'macroproject_end_date';
$orderdir = $AppUI->getState('DeptMacroProjIdxOrderDir') ? $AppUI->getState('DeptMacroProjIdxOrderDir') : 'asc';

if (isset($_POST['show_form'])) {
	$AppUI->setState('addProjWithTasks',  dPgetParam($_POST, 'add_pwt', 0));
	$AppUI->setState('addProjWithOwnerInDep',  dPgetParam($_POST, 'add_pwoid', 0));
}
$addPwT = $AppUI->getState('addProjWithTasks', 0);
$addPwOiD = $AppUI->getState('addProjWithOwnerInDep', 0);

$extraGet = '&amp;user_id='.$user_id;

require(DP_BASE_DIR.'/functions/macroprojects_func.php');
require_once($AppUI->getModuleClass('macroprojects'));

// collect the full macroprojects list data via function in macroprojects.class.php
macroprojects_list_data($user_id);
?>

<form action="?m=departments<?php echo (isset($a) ? '&amp;a='.$a : ''); ?>&amp;tab=<?php 
echo $tab; ?>" method="post" name="form_cb">
<input type="hidden" name="show_form" value="1" />
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<td align="right" width="65" nowrap="nowrap">
		<?php echo $AppUI->_('sort by');?>:
	</td>
	<td align="center" width="100%" nowrap="nowrap">&nbsp;</td>
	<td align="right" nowrap="nowrap">
		<input type="checkbox" name="add_pwoid" id="add_pwoid" onclick="document.form_cb.submit()" <?php 
echo ($addPwOiD ? 'checked="checked"' : ''); ?> />
		<label for="add_pwoid"><?php 
echo $AppUI->_('Show MacroProjects whose Owner is Member of the Dep.'); ?></label>
	</td>
	<td align="right" nowrap="nowrap">
		<input type="checkbox" name="add_pwt" id="add_pwt" onclick="document.form_cb.submit()" <?php 
echo $addPwT ? 'checked="checked"' : ''; ?> />
		<label for="add_pwt"><?php echo $AppUI->_('Show MacroProjects with assigned Tasks'); ?></label>
	</td>
	<td align="right" nowrap="nowrap">
		<?php 
echo arraySelect($macroprojFilter, 'macroproFilter', 
                 'size=1 class=text onChange="document.form_cb.submit()"', $macroproFilter, true); 
?>
	</td>
</tr>
</table>
</form>
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=macroproject_color_identifier" class="hdr"><?php 
echo $AppUI->_('Color'); ?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=company_name" class="hdr"><?php 
echo $AppUI->_('Company'); ?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=macroproject_name" class="hdr"><?php 
echo $AppUI->_('MacroProject Name'); ?></a>
	</th>
          <th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=macroproject_start_date" class="hdr"><?php 
echo $AppUI->_('Start'); ?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=macroproject_duration" class="hdr"><?php 
echo $AppUI->_('Duration'); ?></a>
	</th>
        <th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=macroproject_end_date" class="hdr"><?php 
echo $AppUI->_('Due Date'); ?></a>
	</th>
        <th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; 
?>&amp;macroorderby=macroproject_actual_end_date" class="hdr"><?php 
echo $AppUI->_('Actual'); ?></a>
	</th>
        <th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=task_log_problem" class="hdr"><?php 
echo $AppUI->_('P'); ?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=user_username" class="hdr"><?php 
echo $AppUI->_('Owner'); ?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=total_tasks" class="hdr"><?php 
echo $AppUI->_('Tasks'); ?></a>
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=my_tasks" class="hdr">(<?php 
echo $AppUI->_('My'); ?>)</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=<?php echo $m; ?><?php echo (isset($a) ? '&amp;a='.$a : '') ; ?><?php 
echo (isset($extraGet) ? $extraGet : '') ; ?>&amp;macroorderby=macroproject_status" class="hdr"><?php 
echo $AppUI->_('Status'); ?></a>
	</th>
</tr>

<?php
$CR = "\n";
$CT = "\n\t";
$none = true;
foreach ($macroprojects as $row) {
	if (!(getPermission('macroprojects', 'view', $row['macroproject_id']))) {
		continue;
	}
	// We dont check the percent_completed == 100 because some macroprojects
	// were being categorized as completed because not all the tasks
	// have been created (for new macroprojects)
	if ($macroproFilter == -1 || $row['macroproject_status'] == $macroproFilter 
	    || ($macroproFilter == -2 && $row['macroproject_status'] != 3) 
	    || ($macroproFilter == -3 && $row['macroproject_status'] != 7)) {
		$none = false;
		$start_date = ((intval(@$row['macroproject_start_date'])) 
		               ? new CDate($row['macroproject_start_date']) : null);
		$end_date = ((intval(@$row['macroproject_end_date'])) 
		             ? new CDate($row['macroproject_end_date']) : null);
		$actual_end_date = ((intval(@$row['macroproject_actual_end_date'])) 
		                    ? new CDate($row['macroproject_actual_end_date']) : null);
		$style = (($actual_end_date > $end_date && !(empty($end_date))) 
		          ? 'style="color:red; font-weight:bold"' : '');
?>
<tr>
	<td width="65" align="center" style="border: outset #eeeeee 2px;background-color:#<?php 
echo ($row['macroproject_color_identifier']); ?>">
		<span style="color:<?php echo bestColor($row['macroproject_color_identifier']); ?>">
			<?php echo sprintf("%.1f%%", $row['macroproject_percent_complete']); ?>
		</span>
	</td>
	<td width="30%">
<?php 
		if (getPermission('companies', 'view', $row['macroproject_company'])) {
?>
		<a href="?m=companies&amp;a=view&amp;company_id=<?php 
echo $row['macroproject_company']; ?>" title="<?php echo htmlspecialchars($row['company_description'], ENT_QUOTES); ?> ">
<?php 
		}
		
		echo htmlspecialchars($row['company_name'], ENT_QUOTES);
		
		if (getPermission('companies', 'view', $row['macroproject_company'])) {
?>
		</a>
<?php 
		}
?>
	</td>
	<td width="100%">
		<a href="?m=macroprojects&amp;a=view&amp;macroproject_id=<?php 
echo ($row['macroproject_id']); ?>" onmouseover="return overlib('<?php 
echo htmlspecialchars(('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', 
                                                addslashes($row['macroproject_description'])) 
                       . '</p></div>'), ENT_QUOTES); 
?>', CAPTION, '<?php echo $AppUI->_('Description'); ?>', CENTER);" onmouseout="nd();">
		<?php echo htmlspecialchars($row['macroproject_name'], ENT_QUOTES); ?>
		</a>
	</td>
	<td align="center"><?php 
echo htmlspecialchars(($start_date ? $start_date->format($df) : '-')); ?></td>
	<td align="center"><?php 
echo htmlspecialchars((($row['macroproject_duration'] > 0) 
                       ? ($row['macroproject_duration'] . $AppUI->_('h')) : '-')); ?></td>
	<td align="center"nowrap="nowrap" style="background-color:<?php echo ($priority[$row['macroproject_priority']]['color']); ?>"><?php 
echo htmlspecialchars(($end_date ? $end_date->format($df) : '-')); ?></td>
	<td align="center">
<?php 
		if (($actual_end_date)) {
?>
		<a href="?m=tasks&amp;a=view&amp;task_id=<?php echo ($row['critical_task']); ?>" <?php 
echo ($style); ?>><?php echo htmlspecialchars($actual_end_date->format($df)); ?></a>
<?php 
		} else {
?>
		-
<?php 
		}
?>
	</td>
	<td align="center">
<?php 
		if ($row['task_log_problem']) {
?>
		<a href="?m=tasks&amp;a=index&amp;f=all&amp;macroproject_id=<?php echo ($row['macroproject_id']); ?>">
		<?php dPshowImage('./images/icons/dialog-warning5.png', 16, 16, 'Problem', 'Problem!'); ?>
		</a>
<?php 
		} else if ($row['macroproject_priority'] != 0) {
			echo dPshowImage(('./images/icons/priority' 
			                  . (($row['macroproject_priority'] > 0) ? '+' : '-') 
			                  . abs($row['macroproject_priority']) . '.gif'), 13, 16, '', '');
		} else {
?>
		&nbsp;
<?php 
	}
?>
	</td>
	<td align="center" nowrap="nowrap"><?php 
echo htmlspecialchars($row['user_username'], ENT_QUOTES); ?>
	</td>
	<td align="center" nowrap="nowrap"><?php 
echo htmlspecialchars($row['total_tasks'] . ($row['my_tasks'] ? ' ('.$row['my_tasks'].')' : '')); ?>
	</td>
	<td align="center" nowrap="nowrap"><?php 
echo $AppUI->_($mpstatus[$row['macroproject_status']]); ?>
	</td>
</tr>
<?php 
	}
}
if ($none) {
?>
<tr><td colspan="11"><?php echo $AppUI->_('No macroprojects available'); ?> </td></tr>
<?php 
}
?>
<tr>
	<td colspan="11">&nbsp;</td>
</tr>
</table>
