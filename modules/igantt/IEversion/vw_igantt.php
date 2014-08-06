<?php /* IGANTT vw_igantt.php v0.1.1 2009/01/25  */
/*
Copyright (c) 2008 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	iGantt viewing page. It initializes the XAJAX functions and creates the HTML containers for displaying the Gantt chart
*			This work includes the use of the XAJAX library that is distributed under the BSD licence
*
* Author:		Pierre-Yves SIMONOT, Euxenis SAS, <pierre-yves.simonot@euxenis.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.0.1
*	Creation
* version 0.1.1
* 	Use right click (ctrlKey + left click for Opera)  to display/update tasks and include Javascript to avoid context menu display
*	New taskDetails overlay to allow for task update
*
*/
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly.');
}

global $m,$a, $locale_char_set, $AppUI;
$project_id = intval(dPgetParam( $_GET, 'project_id', 0));
$width = intval(dPgetParam( $_GET, 'width', 800));

// Include Ajax library
require_once( "lib/xajax/xajax_core/xajax.inc.php");

// Retrieve Ajax PHP functions
require_once ( "igantt.ajax.php" );

// Initialize Ajax object
$xajax = new xajax();
$xajax->configure("javascript URI", "lib/xajax/");
$xajax->setCharEncoding($locale_char_set);
$xajax->setFlag('debug', true); 
$xajax->register(XAJAX_FUNCTION, 'drawGraph');
$xajax->register(XAJAX_FUNCTION, 'displayTask');
$xajax->register(XAJAX_FUNCTION, 'updateTask');
$xajax->processRequest();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta name="Description" content="interactive Gantt chart" />
	<meta name="Version" content="<?php echo @$AppUI->getVersion();?>" />
	<meta http-equiv="Content-Type" content="text/html;charset=<?php echo isset( $locale_char_set ) ? $locale_char_set : 'UTF-8';?>" />
	<base href="<?php	$base = DP_BASE_URL;
						if ( substr($base, -1) != '/')
							$base .= '/';
						echo $base; ?>" >
	<title><?php echo $AppUI->_( 'Interactive Gantt chart' );?></title>
	<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle;?>/main.css" media="all" />
	<style type="text/css" media="all">@import "./style/<?php echo $uistyle;?>/main.css";</style>
	<link rel="stylesheet" type="text/css" href="./modules/igantt/css/igantt.css" media="all" />
	<style type="text/css" media="all">@import "./modules/igantt/css/igantt.css";</style>
	<link rel="shortcut icon" href="./style/<?php echo $uistyle;?>/images/favicon.ico" type="image/ico" />
	<script type="text/javascript" src="./lib/overlib/overlib.js"></script>
	<script type="text/javascript" src="./modules/igantt/js/igantt.js"></script>
	<script type="text/javascript" src="./modules/igantt/js/wz_jsgraphics.js"></script>
<script language="javascript">
// Initialize global JS  variables for dragging
var overObject;		// element ID of the bar that mouse is over
var dragObject;  	// element ID of the bar to be changed
var mouseOffset; 	// mouse offset position relative to bar element
var dragType;		// 0 = no dragging; 1 = drag start date only; 2 = drag end date only; 3 = drag both ends; 4 = drag as block (milestone)
var startmove;		// Flag : start date to be changed depending on mouse moves
var endmove;		// Flag : end date to be changed according to mouse moves
var mousePos;		// Initial/previous mouse coordinates
var dragType;		// global draggable object type
var barNum;			// Number of bargraphs in the graph
var barArray;		// Bargraph object array
var barIndex;		// Array of bar names
var Xmax;			// Gantt graph left boundary
var cellNum;		// Number of cells in date scale
var cellArr;		// Array of cell limits
var hourInc;		// hour increment in minutes
var dayStart;		// Day start hour
var dayEnd;			// Day end hour
var dateFormat;		// Date format 
var hourFormat;		// Hour format
var colWidth		// Width of the label table
var undoStack;		// Stack of moved bar
var depArray;		// list of dependencies ( required task ID, dependent task ID)
var depNum;			// Number of dependencies
var depObject;		// required task ID dependency creation

// Initialize global variable for date popups
var calendarField = "";

</script>
	<?php $xajax->printJavascript(); /* Affiche le Javascript */?>
</head>
<!-- Include oncontextmenu directive to avoid context menu to show when right clicking a task bar with FF (v.0.1.1) -->
<body id="graphBody" onload="this.focus();" oncontextmenu="return false" ondragstart="return false" onselectstart="return false" >
<?php
// Retrieve project ID
$project_id = dPgetParam( $_GET, 'project_id', 0 );
//	Create Title
$titleBlock = new CTitleBlock('Interactive Gantt chart', 'applet3-48.png', $m, "$m.$a");
$titleBlock->show();
// Create Graph option table
?>
<br />
<form id="optionFrm" name="optionFrm" method="post" action="#" >
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="tbl">
<input type="hidden" id="project_id" name="project_id" value="<?php echo $project_id; ?>" />
<input type="hidden" id="width" name="width" value="800" />
<tr>
	<td align="left" valign="top" width="20">
		<a href="javascript:scrollPrev()">
			<img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_('previous');?>" border="0">
		</a>
	</td>
	<td width="50%">&nbsp;</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('From');?>:</td>
	<td align="left" nowrap="nowrap">
		<input type="hidden" id="sdate" name="sdate" value="" />
		<input type="text" class="text" id="show_sdate" name="show_sdate" value="" size="12" disabled="disabled" />
		<a href="javascript:popCalendar(document.optionFrm.sdate)">
			<img src="./images/calendar.gif" width="24" height="12" alt="" border="0">
		</a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To');?>:</td>
	<td align="left" nowrap="nowrap">
		<input type="hidden" id="edate" name="edate" value="" />
		<input type="text" class="text" id="show_edate" name="show_edate" value="" size="12" disabled="disabled" />
		<a href="javascript:popCalendar(document.optionFrm.edate)">
		<img src="./images/calendar.gif" width="24" height="12" alt="" border="0">
		</a>
	</td>
	<td nowrap="nowrap">
		<?php echo $AppUI->_("Show only working days"); ?>
		<input type="checkbox" class="text" name="workingDays" id="workingDays" />
	<td nowrap="nowrap">
		<input type="button" class="button" value="<?php echo $AppUI->_('Submit'); ?>" onClick="displayLoading();xajax_drawGraph(xajax.getFormValues(this.form));" />
	</td>
	<td width="50%">&nbsp;</td>
	<td align="right" valign="top" width="20">
		<a href="javascript:scrollNext()">
	  	<img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_('next');?>" border="0">
		</a>
	</td>
</tr>
<tr>
	<td align="center" valign="bottom" colspan="10">
		<a href='javascript:showThisMonth()'><?php echo $AppUI->_('show this month'); ?></a> : 
		<a href='javascript:showFullProject()'><?php echo $AppUI->_('show full project'); ?></a>
	</td>
</tr>
</table>
</form>
<div id="error"></div>
<div id="loadingMessage" style="margin-left:10px;visibility:hidden;color:red;font-weight:bold;"><span><?php echo $AppUI->_("Loading");?></span><span id="movingDots"></span></div>
<br />
<div id="overDates" style="position:absolute;top:0px;width:240px;z-index:1000;">
	<span id="startSpan" style="float:left;width:120px;border:solid 1px black;display:none;" ><div style="font-size:10px;text-align:center;background-color:#99EEFF;border-bottom:solid 1px black;" >Start date</div><div id="barStart" style="font-size:10px;text-align:center;background-color:#FFFFFF">&nbsp;</div></span>
	<span id="endSpan" style="float:right;width:120px;border:solid 1px black;display:none;" ><div style="font-size:10px;text-align:center;background-color:#99EEFF;border-bottom:solid 1px black;">End date</div><div id="barEnd" style="font-size:10px;text-align:center;background-color:#FFFFFF">&nbsp;</div></span>
</div>
<div id="taskDetails" style="position:absolute;top:0px;width:650px;z-index:1000;display:none;">
	<form id="taskFrm" name="taskFrm" action="#" method="post" >
	<input type="hidden" name="task_id" id="task_id" value="0" />
	<table border="0" cellpadding="2" cellspacing="0" class="std" width="100%" >
	<tr>
		<td colspan="4">
		<table border="0" cellpadding="2" cellspacing="0" class="tbl" width="100%" >
			<tr>
			<td width="100%" align="center"><?php echo $AppUI->_("Task details"); ?></td>
			<td onClick="document.getElementById('taskDetails').style.display='none';"><img src="./modules/igantt/images/cancel.gif" width="14" height="14" border="0"></td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Task name"); ?>:</td>
		<td class="hilite" colspan="3" align="left">
			<input type="text" class="text" id="task_name" value="" size="80" maxlength="255" />
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Parent task"); ?>:</td>
		<td class="hilite" id="selectParentTask" align="left" colspan="3" nowrap>
			<select name="task_parent" id="task_parent" class="text" style="width:250px;" >
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Owner"); ?>:</td>
		<td class="hilite" align="left" valign="top" nowrap>
			<?php 	/* PYS Aramis specific user list
					$user_list = $perms->getPermittedUsers('projects', $project_id );
					foreach ( $user_list as $uid => $udata )
						$users[$udata['company_name']][$uid] = $udata['contact_name'];
					arraySelectWithOptGroup( $users, 'task_owner', 'class="text" id="task_owner"', 0, false );
					// PYS Aramis end mod	*/
					//Standard dP user list
					$users = $perms->getPermittedUsers('tasks');
					echo arraySelect( $users, 'task_owner', 'class="text" id="task_owner"', 0, false );
					// End standard dP user list	*/
			?>
		</td>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Start date"); ?>:</td>
		<td class="hilite" align="left" valign="top" nowrap>
			<input type="hidden" name="task_start_date" id="task_start_date" value="" />
			<input type="text" name="show_task_start_date" id="show_task_start_date" size="12" value="" class="text" disabled="disabled" />
			<img id="startDateBtn" src="./images/calendar.gif" border="0" width="24" height="12" onClick="popCalendar(document.taskFrm.task_start_date)" alt="<?php echo $AppUI->_('Calendar');?>">
			<?php
			//Time arrays for selects
			$start = intval(dPgetConfig('cal_day_start', 8));
			$end   = intval(dPgetConfig('cal_day_end', 17));
			$inc   = intval(dPgetConfig('cal_day_increment', 15));
			$hours = array();
			for ($current = $start; $current <= $end; $current++) {
				if (stristr($AppUI->getPref('TIMEFORMAT'), '%p')){	//User time format in 12hr
					$hours[$current] = (($current > 12) ? $current-12 : $current);
				} else {											//User time format in 24hr
					$hours[$current] = (($current < 10) ? '0' : '') . $current;
				}
			}
			$minutes = array();
			for ($current = 0; $current < 60; $current += $inc) {
				$minutes[$current] = (($current < 10) ? '0' : '') . $current;
			}
			echo "&nbsp;" . $AppUI->_("Time") . ":&nbsp;" . arraySelect($hours, "start_hour",'id="start_hour" size="1" onchange="setAMPM(this)" class="text"', 0 );
			echo "&nbsp;" . arraySelect($minutes, "start_minute",'id="start_minute" size="1" class="text"', 0 );
			$style = stristr($AppUI->getPref('TIMEFORMAT'), "%p") ? "style=\"display:inline;\"" : "style=\"display:none;\"" ;
			echo "&nbsp;<input type=\"text\" class=\"text\" name=\"start_hour_ampm\" id=\"start_hour_ampm\" $style value=\"am\" disabled=\"disabled\" size=\"2\" />";
	?>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Priority"); ?>:</td>
		<td class="hilite" align="left" valign="top" nowrap>
			<?php
			echo arraySelect( dPgetSysVal('TaskPriority') , 'task_priority', 'id="task_priority" size="1" class="text"', 0, true );
			?>
		</td>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("End date"); ?>:</td>
		<td class="hilite" align="left" valign="top" nowrap>
			<input type="hidden" name="task_end_date" id="task_end_date" value="" />
			<input type="text" name="show_task_end_date" id="show_task_end_date" size="12" value="" class="text" disabled="disabled" />
			<img id="endDateBtn" src="./images/calendar.gif" border="0" width="24" height="12" onClick="popCalendar(document.taskFrm.task_end_date)" alt="<?php echo $AppUI->_('Calendar');?>" >
			<?php
			echo "&nbsp;" . $AppUI->_("Time") . ":&nbsp;" . arraySelect($hours, "end_hour",'id="end_hour" size="1" onchange="setAMPM(this)" class="text"', 0 );
			echo "&nbsp;" . arraySelect($minutes, "end_minute",'id="end_minute" size="1" class="text"', 0 );
			$style = stristr($AppUI->getPref('TIMEFORMAT'), "%p") ? "style=\"display:inline;\"" : "style=\"display:none;\"" ;
			echo "&nbsp;<input type=\"text\" class=\"text\" name=\"end_hour_ampm\" id=\"end_hour_ampm\" $style value=\"am\" disabled=\"disabled\" size=\"2\" />";
	?>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Access"); ?>:</td>
		<td class="hilite" align="left" nowrap>
			<?php
			// user based access
			$task_access = array(
					'0'=>'Public',
					'4'=>'Privileged',
					'2'=>'Participant',
					'1'=>'Protected',
					'3'=>'Private'
					);
			echo arraySelect( $task_access , 'task_access', 'id="task_access" size="1" class="text"', 0, true );
			?>
		</td>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Duration"); ?>:</td>
		<td class="hilite" align="left" nowrap>
			<input type="text" class="text" id="task_duration" name="task_duration" maxlength="8" size="6" value="0" />
			<?php
			echo arraySelect( dPgetSysVal('TaskDurationType'), 'task_duration_type', 'id="task_duration_type" class="text"', 24, true );
			?>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Type"); ?>:</td>
		<td class="hilite" align="left" nowrap>
			<?php
			echo arraySelect( dPgetSysVal('TaskType') , 'task_type', 'id="task_type" size="1" class="text"', 0, true );
			?>
		</td>
		<td align="right" class="hilite" nowrap><?php echo $AppUI->_("Progress"); ?>:</td>
		<td class="hilite" align="left" nowrap>
			<?php
			$percent = array();
			$i=0;
			while ( $i<= 100 ) {
				$percent[$i]=$i;
				$i += 5;
				}
			echo arraySelect( $percent, 'task_percent_complete', 'id="task_percent_complete" class="text"', 0, false );
			?>%
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Assign"); ?>:</td>
		<td class="hilite" id="assign" align="left" nowrap>
			<?php
				$users = array_merge( array( 0 => "(".$AppUI->_("Assign user").")" ), $users);
				echo arraySelect( $users, 'task_assign', 'class="text" id="task_assign" style="width:130px;"', 0, false ); ?>
			<select name="task_assign_perc" id="task_assign_perc" class="text">
      			<?php 
      				for ($i = 5; $i <= 100; $i+=5) {
      					echo "<option ".(($i==100)? "selected=\"selected\"" : "" )." value=\"".$i."\">".$i."%</option>";
      				}
      			?>
			</select>
		</td>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Dependency"); ?>:</td>
		<td class="hilite" id="dependency" align="left" nowrap>
			<select name="task_dependency" id="task_dependency" class="text" style="width:280px;overflow:hidden;" >
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Unassign"); ?>:</td>
		<td class="hilite" id="unassign" align="left" nowrap>
			<select name="task_unassign" id="task_unassign" class="text" style="width:130px;" >
			</select>
		</td>
		<td colspan="2" class="hilite">&nbsp;</td>
	</tr>
	<tr>
		<td align="right" class="hilite" valign="top" nowrap><?php echo $AppUI->_("Description"); ?>:</td>
		<td class="hilite" colspan="3">
		<textarea name="task_description" id="task_description" class="text" cols="80" rows="3" wrap="virtual"></textarea>
		</td>
	</tr>
	<tr id="editTaskBtn" >
		<td colspan="4" align="right">
			<input type="button" class="button" value="<?php echo $AppUI->_('Submit'); ?>" onClick="javascript:xajax_updateTask( xajax.getFormValues('taskFrm'), xajax.getFormValues('optionFrm'));" />
		</td>
	</tr>
	</table>
</form>
</div>
<br />
<div id="graph">
	<table id="mainTable" border="0" cellpadding="0" cellspacing="0" class="gtbl" >
	</table>
</div>
<div id="warning"></div>
<br />
<div id="actionDiv" style="margin-left:5px;margin-right:20px;">
	<span style="float:left;" >
		<input type="button" class="button" value="<?php echo $AppUI->_('Cancel'); ?>" onClick="javascript:window.close();" />
		&nbsp;<input type="button" class="button" id="undoBtn" value="<?php echo $AppUI->_('Undo'); ?>" style="visibility:hidden;" onClick="javascript:undoAction();" />
	</span>
	<span style="float:right;" >
		<input type="button" class="button" id="submitBtn" value="<?php echo $AppUI->_('Submit'); ?>" style="visibility:hidden;" onClick="javascript:updateTasks();" />
	</span>
</div>
<script language="javascript" >
document.optionFrm.width.value = navigator.appName=='Netscape'?window.innerWidth:document.body.offsetWidth;
displayLoading();
xajax_drawGraph( xajax.getFormValues("optionFrm") );
</script>