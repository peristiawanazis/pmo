<?php /* MACRO_PROJECTS calendar_tab.macroprojects.php, v 0.1.0 2012/05/30 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Macroproject view for calendar.
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
global $AppUI, $company_id, $mpstatus, $dPconfig, $macroproject_types, $priority, $company_id;

$df = $AppUI->getPref('SHDATEFORMAT');
$macroproject_types = dPgetSysVal('MacroProjectStatus');

require_once($AppUI->getModuleClass('macroprojects'));
require(DP_BASE_DIR.'/functions/macroprojects_func.php');

// get any records denied from viewing
$obj = new CMacroProject();

// Task sum table
// by Pablo Roca (pabloroca@mvps.org)
// 16 August 2003

$q = new DBQuery();
$q->dropTemp('tasks_sum');
$q->exec();
$q->clear();

$q->addTable('macroprojects', 'mp');
$q->addQuery('mp.macroproject_id, mp.macroproject_name, mp.macroproject_status, mp.macroproject_color_identifier,
	mp.macroproject_start_date, mp.macroproject_end_date, mp.macroproject_priority, mp.macroproject_description, '
	.'mp.macroproject_percent_complete,'.
	'con.contact_first_name, con.contact_last_name, u.user_username');
$q->addJoin('users', 'u', 'u.user_id = mp.macroproject_owner');
$q->addJoin('contacts', 'con', 'u.user_contact = con.contact_id');
$q->addWhere('mp.macroproject_status <> 7');
$q->addWhere('mp.macroproject_status <> 5');
$allowed_where = $obj->getAllowedSQL($AppUI->user_id);
if ($allowed_where) {
	$q->addWhere(implode(' AND ', $allowed_where));
}
if ($company_id) {
	$q->addWhere('mp.macroproject_company = ' . $company_id);
}
$q->addOrder('macroproject_end_date');

$macroprojects = $q->loadList();
?>

<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<td align="right" width="65" nowrap="nowrap">&nbsp;<?php echo $AppUI->_('sort by');?>:&nbsp;</td>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_name" class="hdr">
		<?php echo $AppUI->_('Name');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=macroproject_end_date" class="hdr">
		<?php echo $AppUI->_('End');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=user_username" class="hdr">
		<?php echo $AppUI->_('Owner');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;macroorderby=my_tasks" class="hdr">
		<?php echo $AppUI->_('My Tasks');?>
		</a>
		<a href="?m=macroprojects&amp;macroorderby=total_tasks" class="hdr">
		(<?php echo $AppUI->_('All');?>)
		</a>
	</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Status'); ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Selection'); ?></th>
</tr>

<?php
$CR = "\n";
$CT = "\n\t";
$none = true;

foreach ($macroprojects as $row) {
	$none = false;
	$end_date = (intval(@$row['macroproject_end_date']) ? new CDate($row['macroproject_end_date']) : null);
?>	
<tr>
	<td width="65" align="center" style="border: outset #eeeeee 2px;background-color:#<?php
	echo $row['macroproject_color_identifier']; ?>">
		<span style="font-color: <?php echo bestColor($row['macroproject_color_identifier']); ?>">
		<?php echo sprintf("%.1f%%", $row['macroproject_percent_complete']); ?>
		</span>
	</td>
	<td width="100%">
		<a href="?m=macroprojects&amp;a=view&amp;macroproject_id=<?php 
echo $row['macroproject_id']; ?>" title="<?php 
	echo htmlspecialchars($row['macroproject_description'], ENT_QUOTES); ?>"><?php 
	echo htmlspecialchars($row['macroproject_name'], ENT_QUOTES); ?>
		</a>
	</td>
	<td align="center"nowrap="nowrap" style="background-color:<?php echo ($priority[$row['macroproject_priority']]['color']); ?>"><?php 
echo htmlspecialchars(($end_date ? $end_date->format($df) : '-')); ?></td>
	<td nowrap="nowrap"><?php echo htmlspecialchars($row['user_username'], ENT_QUOTES); ?></td>
	<td align="center" nowrap="nowrap">
		<?php echo ($row['my_tasks'] . ' ('.$row['total_tasks'] . ')'); ?>
	</td>
	<td align="center" nowrap="nowrap">
		<?php 
	echo $AppUI->_((($row['macroproject_status']) 
	                ? $macroproject_types[$row['macroproject_status']] : 'Not Defined')); ?>
	</td>
	<td align="center">
		<input type="checkbox" name="macroproject_id[]" value="<?php echo $row['macroproject_id']; ?>" />
	</td>
</tr>
<?php
}
if ($none) {
	echo ('<tr><td colspan="6">' . $AppUI->_('No macroprojects available') . '</td></tr>');
}
?>
</table>
