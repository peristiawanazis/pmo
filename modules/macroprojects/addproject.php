<?php /* MACRO_PROJECTS addproject.php, v 0.1.0 2012/05/30 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description: page for add or del project in a macroproject.
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

GLOBAL $AppUI, $company_id, $mpstatus, $project_types, $currentTabId, $currentTabName;
GLOBAL $priority;

$df = $AppUI->getPref('SHDATEFORMAT');//pour avoir un format de date qui nous convient

$show_all_projects = false;
if ($currentTabId == 500) {
	$show_all_projects = true;
}
$macroproject_id = intval(dPgetParam($_GET, 'macroproject_id', 0));
$company_id = intval(dPgetParam($_GET, 'company_id', 0));
$company_internal_id = intval(dPgetParam($_GET, 'company_internal_id', 0));
$contact_id = intval(dPgetParam($_GET, 'contact_id', 0));
$macroproject_id = intval(dPgetParam($_REQUEST, 'macroproject_id', 0));
$addShowOptions = dPgetParam($_REQUEST, 'addShowOptions', 0);
$ownerFilter = dPgetParam($_REQUEST, 'ownerFilter', 0);
$companyFilter = dPgetParam($_REQUEST, 'companyFilter', '0');

// check permissions for this record
$canEdit = getPermission($m, 'edit', $macroproject_id);
$canAuthor = getPermission($m, 'add', $macroproject_id);
if (!(($canEdit && $macroproject_id) || ($canAuthor && !($macroproject_id)))) {
	$AppUI->redirect('m=public&a=access_denied');
}
// setup the title block
$ttl = $macroproject_id > 0 ? "Edit MacroProject" : "Add Project";
$titleBlock = new CTitleBlock($ttl, 'applet3-48.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=macroprojects", "macroprojects list");
if ($macroproject_id != 0)
$titleBlock->addCrumb("?m=macroprojects&amp;a=view&amp;macroproject_id=$macroproject_id", "view this macroproject");
$titleBlock->show();
$q = new DBQuery;

//prepare owner filter
$q->addTable('users', 'u');
$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact');
$q->addQuery('user_id');
$q->addQuery("CONCAT(contact_last_name, ', ', contact_first_name, ' (', user_username, ')')" 
             . ' AS label');
$q->addOrder('contact_last_name, contact_first_name, user_username');
$bufferUser = $q->loadList();

//get list of all departments, filtered by the list of permitted companies.
$q->clear();
$q->addTable('companies', 'c');
//$q->addTable('departments', 'dep');
$q->addQuery('c.company_id, c.company_name, dep.*');
$q->addJoin('departments', 'dep', 'c.company_id = dep.dept_company');
//$q->addOrder('c.company_name');
$q->addOrder('c.company_name, dep.dept_parent, dep.dept_name');
$rows = $q->loadList();
?>
<script language="javascript" type="text/javascript">
function submitIt() {
	document.editFrm.submit();
}
function doMenu(item) {
	obj=document.getElementById(item); 
	col=document.getElementById("x" + item); 
	if (obj.style.display=="none") {
		obj.style.display="block";
		col.innerHTML="<?php echo $AppUI->_('Hide Show Options'); ?>";
	} else {
		obj.style.display="none"; 
		col.innerHTML="<?php echo $AppUI->_('Show Options'); ?>"; 
	}
} 
</script>
<form name="editFrm" method="post" action="?<?php 
		echo 'm=macroprojects&amp;a=addproject&amp;macroproject_id=' . $macroproject_id . '&amp;addShowOptions=' . $addShowOptions . '&amp;ownerFilter=' . $ownerFilter . '&amp;companyFilter=' . $companyFilter; ?>">
		<br />
		<em><?php echo $AppUI->_('Choose Show mode'); ?>:</em>
		<select name="addShowOptions" id="addShowOptions" class="text" onchange="javascript:submitIt()" size="1">
					<?php 						
						echo '<option value="0"'
								.(($addShowOptions ==  0) ? ' selected="selected">' : '>')
								. $AppUI->_('Projects and MacroProjects') . '</option>';
						echo "\n";
						echo '<option value="1"'
								.(($addShowOptions ==  1) ? ' selected="selected">' : '>')
								. $AppUI->_('Only Projects') . '</option>';
						echo "\n";
						echo '<option value="2"'
								.(($addShowOptions ==  2) ? ' selected="selected">' : '>')
								. $AppUI->_('Only MacroProjects') . '</option>';
						echo "\n";
					?>
		</select>
		<em><?php echo $AppUI->_('Owner'); ?>:</em>
		<select name="ownerFilter" id="ownerFilter" class="text" onchange="javascript:submitIt()" size="1">
					<?php
						echo '<option value=0'
									.(($ownerFilter ==  0) ? ' selected="selected">' : '>')
									. $AppUI->_('All users') . '</option>';	
						foreach($bufferUser as $user) {
							echo '<option value='.$user['user_id']
									.(($ownerFilter ==  $user['user_id']) ? ' selected="selected">' : '>')
									. $user['label'] . '</option>';			
						}
					?>
		</select>
		<em><?php echo $AppUI->_('Company') . '/' . $AppUI->_('Division'); ?>:</em>
		<?php
					$obj_company 	= new CCompany();
					$companies 		= $obj_company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
					if (count($companies) == 0) { 
						$companies = array(0);
					}
					
					// get the list of permitted companies
					$companies = arrayMerge(array('0' => $AppUI->_('All')), $companies);
					
					//get list of all departments, filtered by the list of permitted companies.
					$q = new DBQuery();
					$q->addTable('companies', 'c');
					$q->addQuery('c.company_id, c.company_name, dep.*');
					$q->addJoin('departments', 'dep', 'c.company_id = dep.dept_company');
					$q->addOrder('c.company_name, dep.dept_parent, dep.dept_name');
					$obj_company->setAllowedSQL($AppUI->user_id, $q);
					$rows = $q->loadList();
					
					//display the select list
					$cBuffer = '<select name="companyFilter" id ="companyFilter" class="text" onchange="javascript:submitIt()" size="1">';
					$cBuffer .= ('<option value="0" style="font-weight:bold;">' . $AppUI->_('All') 
								 . '</option>'."\n");
					$comp = '';
					foreach ($rows as $row) {
						if ($row['dept_parent'] == 0) {
							if ($comp != $row['company_id']) {
								$cBuffer .= ('<option value="' . $AppUI->___('c' . $row['company_id']) 
											 . '" style="font-weight:bold;"' 
											 . (($companyFilter.'' == $AppUI->___('c' . $row['company_id'])) ? 'selected="selected"' : '') 
											 . '>' . $AppUI->___($row['company_name']) . '</option>' . "\n");
								$comp = $row['company_id'];
							}
							
							if ($row['dept_parent'] != null) {
							global $department ;
							$department = $companyFilter;
								showchilddept($row);
								findchilddept($rows, $row['dept_id']);
							}
						}
					}
					$cBuffer .= '</select>';
					echo $cBuffer; ?>
			
		<br />
</form>
<?php if($addShowOptions == 0 || $addShowOptions == 1) {

if (isset($_GET['project']) and isset($_GET['add']))//si on vient d'ajouter un projet
{
	$q->clear();
	if($_GET['add'] == 'true')
	{	
		$q->addTable('macroproject_project');
		$q->addQuery('macroproject_id');
		$q->addWhere('project_id = ' .$_GET['project']);
		$q->addWhere('macroproject_id = ' .$macroproject_id);
		$existing_macroproject = $q->loadList();
		if(!(count($existing_macroproject) != 0))//pour empecher d'ajouter plusieurs fois le même project
		{
			$q->clear();
			$q->addTable('macroproject_project');
			$q->addInsert('macroproject_id', $macroproject_id);
			$q->addInsert('project_id', $_GET['project']);
		}
	}
	else
	{
		$q->setDelete('macroproject_project');
		$q->addWhere('project_id = ' .$_GET['project']);
		$q->addWhere('macroproject_id = ' .$macroproject_id);
	}
	$q->exec();
	updateMacroProjectPercentComplete($macroproject_id);
?>
<META http-EQUIV="Refresh" CONTENT="Temps; ?m=macroprojects&a=addproject&macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>">
<?php
}
// select all projects
$q->clear();
$q->addTable('projects', 'p');
//$q->addQuery('p.project_id, p.project_color_identifier, p.project_percent_complete, p.project_company, p.project_name, p.project_start_date, p.project_end_date, project_actual_end_date, u.user_username, c.company_name');
$q->addQuery('p.project_id, p.project_color_identifier, p.project_percent_complete, p.project_company, p.project_name, p.project_start_date, p.project_end_date, u.user_username, c.company_name');
$q->addJoin('companies', 'c', 'c.company_id = p.project_company');
$q->addJoin('users', 'u', 'u.user_id = p.project_owner');
if($ownerFilter!=0){
	$q->addWhere('p.project_owner = '.$ownerFilter);
}
if($companyFilter != '0' && $companyFilter != '')
{
	if(strpos($companyFilter, 'c') !== false){
		$q->addWhere('p.project_company = '.trim($companyFilter, 'c'));
	}
	else{
		$q->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
		$q->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
		$q->addWhere('pd.department_id = '.$companyFilter.' OR d.dept_parent = '.$companyFilter);
	}
}
$projects = $q->loadList();
?>
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<td colspan="<?php echo ($base_table_cols); ?>" nowrap="nowrap">
		<?php echo $AppUI->_('sort by');?>:
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
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=project_color_identifier&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Color');?>
		</a>
		(<a href="?m=macroprojects&amp;a=addproject&amp;orderby=project_percent_complete&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">%</a>)
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=company_name&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Company');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=project_name&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Project Name');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=project_start_date&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Start');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=project_end_date&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('End');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=user_username&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Owner');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=total_tasks&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr"><?php echo $AppUI->_('Tasks');?></a>
		<a href="?m=macroprojects&amp;a=addproject&amp;orderby=my_tasks&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">(<?php echo $AppUI->_('My');?>)</a>
	</th>
	<th nowrap="nowrap">
		<?php echo $AppUI->_('Add').'/'.$AppUI->_('Delete').' '.$AppUI->_('project');?>
	</th>
</tr>

<?php 
/*
$CR = "\n";
$CT = "\n\t";
$none = true;

//Tabbed view
$project_status_filter = $currentTabId;
*/
foreach ($projects as $row) {
	//if (! getPermission('projects', 'view', $row['project_id'])) {
		//continue;
	//}
	if ($show_all_projects || $row['project_status'] == $project_status_filter) {
		$none = false;
		$start_date = ((@intval($row['project_start_date'])) 
		               ? new CDate($row['project_start_date']) : null);
		$end_date = ((intval(@$row['project_end_date'])) 
		             ? new CDate($row['project_end_date']) : null);
		$actual_end_date = ((intval(@$row['project_actual_end_date'])) 
		                    ? new CDate($row['project_actual_end_date']) : null);
		$style = ((($actual_end_date > $end_date) && !empty($end_date)) 
		          ? 'style="color:red; font-weight:bold"' : '');
?>
<tr>
	<td width="65" align="center" style="border: outset #eeeeee 2px;background-color:#<?php 
echo ($row['project_color_identifier']); ?>">
		<span style="color:<?php echo (bestColor($row['project_color_identifier'])); ?>">
		<?php echo(sprintf('%.1f%%', $row['project_percent_complete'])); ?>
		</span>
	</td>
	<td width="30%">
<?php 
		$allowedProjComp = getPermission('companies', 'access', $row['project_company']);
		if ($allowedProjComp) {
?>
		<a href="?m=companies&amp;a=view&amp;company_id=<?php 
echo htmlspecialchars($row['project_company']); ?>" title="<?php 
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
		<a href="?m=projects&amp;a=view&amp;project_id=<?php 
echo htmlspecialchars($row['project_id']); ?>" <?php 
if (!empty($row['project_description'])) { ?>onmouseover="return overlib('<?php 
echo(htmlspecialchars(('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', 
                                                addslashes($row['project_description'])) 
                       . '</p></div>'), ENT_QUOTES)); ?>', CAPTION, '<?php 
echo($AppUI->_('Description')); ?>', CENTER);" onmouseout="nd();"<?php } ?>>
		<?php echo (htmlspecialchars($row['project_name'], ENT_QUOTES)); ?>
		</a>
	</td>
	<td align="center">
		<?php echo (htmlspecialchars($start_date ? $start_date->format($df) : '-')); ?>
	</td>
	<td align="center" nowrap="nowrap" style="background-color:<?php 
echo ($priority[$row['project_priority']]['color']); ?>">
		<?php echo (htmlspecialchars($end_date ? $end_date->format($df) : '-')); ?>
	</td>
	<td nowrap="nowrap">
		<?php echo (htmlspecialchars($row['user_username'], ENT_QUOTES)); ?>
	</td>
	<td align="center" nowrap="nowrap">
		<?php 
echo (htmlspecialchars($row['total_tasks'] . ($row['my_tasks'] ? ' ('.$row['my_tasks'].')' : '')));
?>
	</td>
	<td align="center" nowrap="nowrap">
		<!-- Link for add or del project -->
		<?php 
		$q->clear();
		$q->addTable('macroproject_project');
		$q->addQuery('macroproject_id');
		$q->addWhere('project_id = ' .$row['project_id']);
		$q->addWhere('macroproject_id = ' .$macroproject_id);
		$macroproject_list = $q->loadList();
		if(count($macroproject_list) == 0){
		?>
		<a href="?m=macroprojects&amp;a=addproject&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;project=<?php echo $row['project_id'];?>&amp;add=true&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<img border="0" alt=<?php echo $AppUI->_('Add');?> src="./modules/macroprojects/images/plus.png" />
		<?php
		}
		else{
		?>
		<a href="?m=macroprojects&amp;a=addproject&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;project=<?php echo $row['project_id'];?>&amp;add=false&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<img border="0" alt=<?php echo $AppUI->_('Del');?> src="./modules/macroprojects/images/minus.png" />
		<?php
		}
		?>
		</a>
	</td>
</tr>
<?php 
	}
}

if ($none) {
?>
<tr>
	<td colspan="<?php echo ($table_cols); ?>"><?php 
echo $AppUI->_('No projects available'); ?></td>
</tr>
<?php 
} 
?>
</table>
<!--</form>-->
<?php 
} 
?>
<?php if($addShowOptions == 0 || $addShowOptions == 2) {

if (isset($_GET['macroproject_son']) and isset($_GET['add']))//si on vient d'ajouter un projet
{
	$q->clear();
	if($_GET['add'] == 'true')
	{	
		$q->addTable('macroproject_macroproject');
		$q->addQuery('macroproject_father');
		$q->addWhere('macroproject_son = ' .$_GET['macroproject_son']);
		$q->addWhere('macroproject_father = ' .$macroproject_id);
		$existing_macroproject = $q->loadList();
		if(!(count($existing_macroproject) != 0))//pour empecher d'ajouter plusieurs fois le même macroproject
		{
			$q->clear();
			$q->addTable('macroproject_macroproject');
			$q->addInsert('macroproject_father', $macroproject_id);
			$q->addInsert('macroproject_son', $_GET['macroproject_son']);
		}
	}
	else
	{
		$q->setDelete('macroproject_macroproject');
		$q->addWhere('macroproject_son = ' .$_GET['macroproject_son']);
		$q->addWhere('macroproject_father = ' .$macroproject_id);
	}
	$q->exec();
	updateMacroProjectPercentComplete($macroproject_id);
?>
<META http-EQUIV="Refresh" CONTENT="Temps; ?m=macroprojects&a=addproject&macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>">
<?php
}
// select all macroprojects
$q->clear();
$q->addTable('macroprojects', 'mp');
$q->addQuery('mp.macroproject_id, mp.macroproject_color_identifier, mp.macroproject_percent_complete, mp.macroproject_company, mp.macroproject_name, mp.macroproject_start_date, mp.macroproject_end_date, macroproject_actual_end_date, u.user_username, c.company_name');
$q->addJoin('companies', 'c', 'c.company_id = mp.macroproject_company');
$q->addJoin('users', 'u', 'u.user_id = mp.macroproject_owner');
if($ownerFilter!=0){
	$q->addWhere('mp.macroproject_owner = '.$ownerFilter);
}
if($companyFilter != '0' && $companyFilter != '')
{
	if(strpos($companyFilter, 'c') !== false){
		$q->addWhere('mp.macroproject_company = '.trim($companyFilter, 'c'));
	}
	else{
	
		$q->addJoin('macroproject_departments', 'mpd', 'mpd.macroproject_id = mp.macroproject_id');
		$q->addJoin('departments', 'd', 'mpd.department_id = d.dept_id');
		$q->addWhere('mpd.department_id = '.$companyFilter.' OR d.dept_parent = '.$companyFilter);
	}
}
$q->addWhere('mp.macroproject_id !='.$macroproject_id.recoverFatherMacroProjects($macroproject_id, 'mp.macroproject_id !='));
$macroprojects = $q->loadList();
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
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=macroproject_color_identifier&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Color');?>
		</a>
		(<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=macroproject_percent_complete&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">%</a>)
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=company_name&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Company');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=macroproject_name&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('MacroProject Name');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=macroproject_start_date&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Start');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=macroproject_end_date&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('End');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=user_username&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<?php echo $AppUI->_('Owner');?>
		</a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=total_tasks&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr"><?php echo $AppUI->_('Tasks');?></a>
		<a href="?m=macroprojects&amp;a=addproject&amp;macroorderby=my_tasks&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">(<?php echo $AppUI->_('My');?>)</a>
	</th>
	<th nowrap="nowrap">
		<?php echo $AppUI->_('Add').'/'.$AppUI->_('Delete').' '.$AppUI->_('macroproject');?>
	</th>
</tr>

<?php /*
$CR = "\n";
$CT = "\n\t";
$none = true;

//Tabbed view
$project_status_filter = $currentTabId;
*/
foreach ($macroprojects as $row) {
	//if (! getPermission('projects', 'view', $row['project_id'])) {
		//continue;
	//}
	if ($show_all_macroprojects || $row['macroproject_status'] == $macroproject_status_filter) {
		$none = false;
		$start_date = ((@intval($row['macroproject_start_date'])) 
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
	<td nowrap="nowrap">
		<?php echo (htmlspecialchars($row['user_username'], ENT_QUOTES)); ?>
	</td>
	<td align="center" nowrap="nowrap">
		<?php 
echo (htmlspecialchars($row['total_tasks'] . ($row['my_tasks'] ? ' ('.$row['my_tasks'].')' : '')));
?>
	</td>
	<td align="center" nowrap="nowrap">
		<!-- Link for add or del macroproject -->
		<?php 
		$q->clear();
		$q->addTable('macroproject_macroproject');
		$q->addQuery('macroproject_father');
		$q->addWhere('macroproject_son = ' .$row['macroproject_id']);
		$q->addWhere('macroproject_father = ' .$macroproject_id);
		$macroproject_list = $q->loadList();
		if(count($macroproject_list) == 0){
		?>
		<a href="?m=macroprojects&amp;a=addproject&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;macroproject_son=<?php echo $row['macroproject_id'];?>&amp;add=true&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<img border="0" alt=<?php echo $AppUI->_('Add');?> src="./modules/macroprojects/images/plus.png" />
		<?php
		}
		else{
		?>
		<a href="?m=macroprojects&amp;a=addproject&amp;macroproject_id=<?php echo $macroproject_id;?>&amp;macroproject_son=<?php echo $row['macroproject_id'];?>&amp;add=false&amp;addShowOptions=<?php echo $addShowOptions?>&amp;ownerFilter=<?php echo $ownerFilter?>&amp;companyFilter=<?php echo $companyFilter?>" class="hdr">
		<img border="0" alt=<?php echo $AppUI->_('Del');?> src="./modules/macroprojects/images/minus.png" />
		<?php
		}
		?>
		</a>
	</td>
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
} 
?>
</table>
</form>
<?php 
} 
?>