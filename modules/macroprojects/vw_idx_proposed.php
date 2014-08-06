<?php /* MACRO_PROJECTS vw_idx_proposed.php, v 0.1.0 2012/05/30 */
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

GLOBAL $AppUI, $macroprojects, $company_id, $mpstatus, $macroproject_types, $currentTabId, $currentTabName;
GLOBAL $priority;

$show_all_macroprojects = false;
if ($currentTabId == 500) {
	$show_all_macroprojects = true;
}

$df = $AppUI->getPref('SHDATEFORMAT');

$editMacroProjectsAllowed = getPermission('macroprojects', 'edit');
foreach ($macroprojects as $row) {
	$editMacroProjectsAllowed = (($editMacroProjectsAllowed) 
	                        || getPermission('macroprojects', 'edit', $row['macroproject_id']));
}

$base_table_cols = 9;
$base_table_cols += (($show_all_macroprojects) ? 1 : 0);
$table_cols = $base_table_cols + (($editMacroProjectsAllowed) ? 1 : 0);
$added_cols = $table_cols - $base_table_cols;
?>

<form action='./index.php' method='get'>
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<td colspan="<?php echo ($base_table_cols); ?>" nowrap="nowrap">
		<?php echo $AppUI->_('sort by'); ?>:
	</td>
<?php 
if ($added_cols) {
?>
	<td colspan="<?php echo ($added_cols); ?>" nowrap="nowrap">&nbsp;</td>
<?php 
}
?>
</tr>
<tr>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_color_identifier" class="hdr">
		<?php echo $AppUI->_('Color');?>
		</a>
		(<a href="?m=macroprojects&amp;macroorderby=macroproject_percent_complete" class="hdr">%</a>)
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=company_name" class="hdr">
		<?php echo $AppUI->_('Company');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_name" class="hdr">
		<?php echo $AppUI->_('MacroProject Name');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_start_date" class="hdr">
		<?php echo $AppUI->_('Start');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_end_date" class="hdr">
		<?php echo $AppUI->_('End');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_actual_end_date" class="hdr">
		<?php echo $AppUI->_('Actual');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=task_log_problem%20DESC,macroproject_priority" class="hdr">
		<?php echo $AppUI->_('P');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=user_username" class="hdr">
		<?php echo $AppUI->_('Owner');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=total_tasks" class="hdr"><?php echo $AppUI->_('Tasks');?></a>
		<a href="?m=macroprojects&amp;macroorderby=my_tasks" class="hdr">(<?php echo $AppUI->_('My');?>)</a>
	</th>
<?php 
if ($show_all_macroprojects) {
?>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=total_tasks" class="hdr"><?php echo $AppUI->_('Status');?></a>
	</th>
<?php 
}
?>
<?php 
if ($editMacroProjectsAllowed) {
?>
	<th nowrap="nowrap">
		<?php echo $AppUI->_('Selection'); ?>
	</th>
<?php 
}
?>
</tr>

<?php 
$CR = "\n";
$CT = "\n\t";
$none = true;

//Tabbed view
$macroproject_status_filter = $currentTabId;

foreach ($macroprojects as $row) {
	if (! getPermission('macroprojects', 'view', $row['macroproject_id'])) {
		continue;
	}
	if ($show_all_macroprojects || $row['macroproject_status'] == $macroproject_status_filter) {
		$none = false;
		$start_date = ((intval(@$row['macroproject_start_date'])) 
		               ? new CDate($row['macroproject_start_date']) : null);
		$end_date = ((intval(@$row['macroproject_end_date'])) 
		             ? new CDate($row['macroproject_end_date']) : null);
		$actual_end_date = ((intval(@$row['macroproject_actual_end_date'])) 
		                    ? new CDate($row['macroproject_actual_end_date']) : null);
		$style = ((($actual_end_date > $end_date) && !empty($end_date)) 
		          ? 'style="color:red; font-weight:bold"' : '');
?>
<tr>
	<td width="65" align="center" style="border: outset #eeeeee 2px;background-color:#<?php 
echo ($row['macroproject_color_identifier']); ?>">
		<span style="color:<?php echo (bestColor($row['macroproject_color_identifier'])); ?>">
		<?php echo(sprintf('%.1f%%', $row['macroproject_percent_complete'])); ?>
		</span>
	</td>
	<td width="30%">
<?php 
		$allowedProjComp = getPermission('companies', 'access', $row['macroproject_company']);
		if ($allowedProjComp) {
?>
		<a href="?m=companies&amp;a=view&amp;company_id=<?php 
echo htmlspecialchars($row['macroproject_company']); ?>" title="<?php 
echo htmlspecialchars($row['company_description'], ENT_QUOTES); ?>">
<?php 
		}
		echo (htmlspecialchars($row['company_name'], ENT_QUOTES));
		if ($allowedProjComp) {
?>
		</a>
<?php 
		}
?>
	</td>
	<td width="100%">
		<a href="?m=macroprojects&amp;a=view&amp;macroproject_id=<?php 
echo htmlspecialchars($row['macroproject_id']); ?>" <?php 
if (!empty($row['macroproject_description'])) { ?>onmouseover="return overlib('<?php 
echo(htmlspecialchars(('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', 
                                                addslashes($row['macroproject_description'])) 
                       . '</p></div>'), ENT_QUOTES)); ?>', CAPTION, '<?php 
echo($AppUI->_('Description')); ?>', CENTER);" onmouseout="nd();"<?php } ?>>
		<?php echo (htmlspecialchars($row['macroproject_name'], ENT_QUOTES)); ?>
		</a>
	</td>
	<td align="center">
		<?php echo (htmlspecialchars($start_date ? $start_date->format($df) : '-')); ?>
	</td>
	<td align="center" nowrap="nowrap" style="background-color:<?php 
echo ($priority[$row['macroproject_priority']]['color']); ?>">
		<?php echo (htmlspecialchars($end_date ? $end_date->format($df) : '-')); ?>
	</td>
	<td align="center">
		
<?php 
		if ($actual_end_date) {
?>
		<a href="?m=tasks&amp;a=view&amp;task_id=<?php echo ($row['critical_task']); ?>">
		<span<?php echo ($style); ?>> 
		<?php echo (htmlspecialchars($actual_end_date->format($df))); ?>
		</span>
		</a>
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
		<?php 
echo (dPshowImage('./images/icons/dialog-warning5.png', 16, 16, 'Problem', 'Problem!')); ?>
		</a>
<?php 
		} else if ($row['macroproject_priority'] != 0) {
			echo (dPshowImage(('./images/icons/priority' 
			                   . (($row['macroproject_priority'] > 0) ? '+' : '-') 
			                   . abs($row['macroproject_priority']) . '.gif'), 13, 16, '', ''));
		} else {
?>
		&nbsp;
<?php 
		}
?>
	</td>
	<td nowrap="nowrap">
		<?php echo (htmlspecialchars($row['user_username'], ENT_QUOTES)); ?>
	</td>
	<td align="center" nowrap="nowrap">
		<?php 
echo (htmlspecialchars($row['total_tasks'] . ($row['my_tasks'] ? ' ('.$row['my_tasks'].')' : '')));
?>
	</td>
<?php 
		if ($show_all_macroprojects) {
?>
	<td align="center" nowrap="nowrap">
		<?php 
echo (($row['macroproject_status'] == 0) 
      ? $AppUI->_('Not Defined') : $AppUI->_($macroproject_types[$row['macroproject_status']]));
?>
	</td>
<?php 
		}
		if ($editMacroProjectsAllowed) {
?>
	<td align="center">
<?php 
			if (getPermission('macroprojects', 'edit', $row['macroproject_id'])) {
?>
		<input type="checkbox" name="macroproject_id[]" value="<?php echo ($row['macroproject_id']); ?>" />
<?php 
			} else {
?>
		&nbsp;
<?php 
			} 
?>
	</td>
<?php 
		}
?>
</tr>
<?php 
	}
}

if ($none) {
?>
<tr>
	<td colspan="<?php echo ($table_cols); ?>"><?php 
echo $AppUI->_('No macroprojects available'); ?></td>
</tr>
<?php 
} else {
?>
<tr>
	<td colspan="<?php echo ($table_cols);?>" align="right">
		<input type="submit" class="button" value="<?php 
echo $AppUI->_('Update macroprojects status'); ?>" />
		<input type="hidden" name="update_macroproject_status" value="1" />
		<input type="hidden" name="m" value="macroprojects" />
<?php 
	echo arraySelect($mpstatus, 'macroproject_status', 'size="1" class="text"', 2, true);
?>
	</td>
</tr>
<?php 
}
?>
</table>
</form>
