<?php /* MACRO_PROJECTS vw_forums.php, v 0.1.0 2012/05/30 */
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

GLOBAL $AppUI, $macroproject_id;
// Forums mini-table in macroproject view action
$q  = new DBQuery;
$q->addTable('forums');
$q->addQuery("forum_id, forum_macroproject, forum_description, forum_owner, forum_name, forum_message_count,
	DATE_FORMAT(forum_last_date, '%d-%b-%Y %H:%i') forum_last_date,
	macroproject_name, macroproject_color_identifier, macroproject_id");
$q->addJoin('macroprojects', 'mp', 'macroproject_id = forum_macroproject');
$q->addWhere("forum_macroproject = $macroproject_id");
$q->addOrder('forum_macroproject, forum_name');
$rc = $q->exec();
?>

<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th nowrap>&nbsp;</th>
	<th nowrap width="100%"><?php echo $AppUI->_('Forum Name');?></th>
	<th nowrap><?php echo $AppUI->_('Messages');?></th>
	<th nowrap><?php echo $AppUI->_('Last Post');?></th>
</tr>
<?php
while ($row = db_fetch_assoc($rc)) { ?>
<tr>
	<td nowrap align=center>
<?php
	if ($row["forum_owner"] == $AppUI->user_id) { ?>
		<A href="./index.php?m=forums&a=addedit&forum_id=<?php echo $row["forum_id"];?>"><img src="./images/icons/pencil.gif" alt="expand forum" border="0" width=12 height=12></a>
<?php } ?>
	</td>
	<td nowrap><A href="./index.php?m=forums&a=viewer&forum_id=<?php echo $row["forum_id"];?>"><?php echo $row["forum_name"];?></a></td>
	<td nowrap><?php echo $row["forum_message_count"];?></td>
	<td nowrap>
		<?php echo (intval($row["forum_last_date"]) > 0) ? $row["forum_last_date"] : 'n/a'; ?>
	</td>
</tr>
<tr>
	<td></td>
	<td colspan=3><?php echo $row["forum_description"];?></td>
</tr>
<?php }
$q->clear();
?>
</table>
<form method="get" action="./index.php">
<input type="hidden" name="m" value="forums" />
<input type="hidden" name="a" value="addedit" />
<input type="hidden" name="forum_macroproject" value="<?php echo $macroproject_id; ?>" />
<input type="submit" value="<?php echo $AppUI->_('new forum'); ?>" class="button" />
</form>
