<?php /* MACRO_PROJECTS reports.php, v 0.1.0 2012/05/30 */
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

$macroproject_id = intval(dPgetParam($_REQUEST, 'macroproject_id', 0));
$report_type = dPgetParam($_REQUEST, 'report_type', '');

// check permissions for this record
$canRead = getPermission($m, 'view', $macroproject_id);
if (!($canRead)) {
	$AppUI->redirect('m=public&a=access_denied');
}

$macroproject_list=array('0'=> $AppUI->_('All', UI_OUTPUT_RAW));

$obj = new CMacroProject();
$ptrc = $obj->getAllowedMacroProjectsInRows($AppUI->user_id);

$nums=db_num_rows($ptrc);

echo db_error();
for ($x=0; $x < $nums; $x++) {
	$row = db_fetch_assoc($ptrc);
	if ($row['macroproject_id'] == $macroproject_id) {
		$display_macroproject_name='('.$row['macroproject_short_name'].') '.$row['macroproject_name'];
	}
	$macroproject_list[$row['macroproject_id']] = '('.$row['macroproject_short_name'].') '.$row['macroproject_name'];
}

if (! $suppressHeaders) {
?>
<script language="javascript">
                                                                                
function changeIt() {
        var f=document.changeMe;
        f.submit();
}
</script>

<?php
}
// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

$reports = $AppUI->readFiles(DP_BASE_DIR.'/modules/macroprojects/reports', "\.php$");

// setup the title block
if (! $suppressHeaders) {
	$titleBlock = new CTitleBlock('MacroProject Reports', 'applet3-48.png', $m, "$m.$a");
	$titleBlock->addCrumb('?m=macroprojects', 'macroprojects list');
	$titleBlock->addCrumb('?m=macroprojects&a=view&macroproject_id=' . $macroproject_id, 'view this macroproject');
	if ($report_type) {
		$titleBlock->addCrumb('?m=macroprojects&a=reports&macroproject_id=' . $macroproject_id, 'reports index');
	}
	$titleBlock->show();
}

$report_type_var = dPgetParam($_GET, 'report_type', '');
if (!empty($report_type_var))
	$report_type_var = '&report_type=' . $report_type;

if (!($suppressHeaders)) {
	if (!isset($display_macroproject_name)) {
		$display_macroproject_name = $AppUI->_('All');
	}
	echo $AppUI->_('Selected MacroProject') . ': <b>' . $display_macroproject_name . '</b>'; 
?>
<form name="changeMe" action="./index.php?m=macroprojects&a=reports<?php echo $report_type_var; ?>" method="post">
<?php echo $AppUI->_('MacroProjects') . ':';?>
<?php echo arraySelect($macroproject_list, 'macroproject_id', 'size="1" class="text" onchange="changeIt();"', $macroproject_id, false);?>
</form>

<?php
}
if ($report_type) {
	$report_type = $AppUI->checkFileName($report_type);
	$report_type = str_replace(' ', '_', $report_type);
	require DP_BASE_DIR.'/modules/macroprojects/reports/'.$report_type.'.php';
} else {
	echo ('<table>'. "\n");
	echo ('<tr><td><h2>' . $AppUI->_('Reports Available') . '</h2></td></tr>'. "\n");
	foreach ($reports as $v) {
		$type = str_replace('.php', '', $v);
		$desc_file = $type . '.' . $AppUI->user_locale . '.txt';
		
		// Load the description file for the user locale, default to 'en'
		if (file_exists(DP_BASE_DIR . '/modules/macroprojects/reports/' . $desc_file)) {
			$desc = file(DP_BASE_DIR . '/modules/macroprojects/reports/' . $desc_file);
			
		} else {
			$desc_file_en = $type . '.en.txt';
			//FIXME : need to handle description file non existence
			$desc = file(DP_BASE_DIR.'/modules/macroprojects/reports/'.$desc_file_en);
		}
		
		echo ("<tr>\n");
		echo ('<td><a href="index.php?m=macroprojects&a=reports&macroproject_id=' . $macroproject_id 
		      . '&report_type=' . $type . ((isset($desc[2])) ? ('&' . $desc[2]) : '') . '">');
		echo (($desc[0]) ? $desc[0] : $v);
		echo ('</a></td>' . "\n");
		echo '<td>' . (@$desc[1] ? '- ' . $desc[1] : '') . "</td>\n";
		echo "</tr>\n";
	}
	echo '</table>';
}
?>
