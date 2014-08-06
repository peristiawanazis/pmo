<?php /* IGANTT  igantt.php, v0.1.1 2009/01/24 */
/*
Copyright (c) 2008-2009 Euxenis SAS Pierre-Yves SIMONOT
*
* Description:	PHP Class for the generation of interactive Gantt graph using HTML and CSS clauses
*			 This class is defined as an extension of XAJAX response class
*			This work includes the use of the XAJAX library that is distributed under the BSD licence
*
* Author:		Pierre-Yves SIMONOT, Euxenis SAS, http://www.euxenis.com
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
* version 0.1.1
*	Correct bug that appears only with PHP5 when using workingDaysInSpan method (workingDaysInSpan updates the calling object)
*	Correct bug with dp v2.1.2 : dateDiff returns a signed number of days (instead ofabsolute value in previous version)
*	Allow onMouseDownItem  event handler for all task bars (not only not completed not dynamic tasks) to display task detailed information
*
*/
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

// Week number marker
DEFINE("WEEK_MARKER", "s");
DEFINE("GANTT_DATE_FORMAT", "%Y-%m-%d" );

// Expand/collapse options (not implemented)
DEFINE("STATIC_EXPAND", 0);			//  load and display all tasks at init
DEFINE("STATIC_COLLAPSE", 1);		// Load all tasks but display only level 0 tasks at init
DEFINE("DYNAMIC_EXPAND", 2);		// load tasks on demand and display all tasks at init
DEFINE("DYNAMIC_COLLAPSE", 3);		// load tasks on demand and display only level 0 tasks at init

// Scale header types
DEFINE("GANTT_HYEAR", 1);
DEFINE("GANTT_HMONTH", 2);
DEFINE("GANTT_HWEEK", 4);
DEFINE("GANTT_HDAY", 8);
DEFINE("GANTT_HHOUR", 16);

// Scale header height (depends on CSS)
DEFINE("HEIGHT_HYEAR", 25);
DEFINE("HEIGHT_HMONTH", 25);
DEFINE("HEIGHT_HWEEK", 20);
DEFINE("HEIGHT_HDAY", 20);
DEFINE("HEIGHT_HHOUR", 20);

// Scale header classes (depends on CSS)
DEFINE("CLASS_HYEAR", "cyear");
DEFINE("CLASS_HMONTH", "cmonth");
DEFINE("CLASS_HWEEK", "cweek");
DEFINE("CLASS_HDAY", "cday");
DEFINE("CLASS_HHOUR", "chour");

// Gantt bar types
DEFINE("GANTT_MILESTONE", 1);
DEFINE("GANTT_DYNAMIC", 2);
DEFINE("GANTT_ACTIVITY", 3);

// Constraints type
DEFINE("CONSTRAIN_ENDSTART", 1);

// Error/Warning messages
DEFINE("ERROR_101", "Invalid graph start/end date combination");
DEFINE("ERROR_111", "Invalid start working hour");
DEFINE("ERROR_112", "Invalid end working hour");
DEFINE("ERROR_121", "Invalid label column title text");
DEFINE("ERROR_122", "Invalid label column width");
DEFINE("ERROR_123", "Invalid label column title/width combination");
DEFINE("ERROR_125", "Invalid column title for expand/collapse - Clause dropped");
DEFINE("ERROR_201", "Invalid activity type");
DEFINE("ERROR_202", "Invalid activity start/end date combination");
DEFINE("ERROR_203", "Invalid percent complete value (set to 100%)");
DEFINE("ERROR_204", "Label array does not match number of label columns");
DEFINE("ERROR_501", "Undefined parent task");
DEFINE("ERROR_510", "Undefined required task");
DEFINE("ERROR_511", "Undefined dependent task");

class iGanttResponse extends xajaxResponse {

/******************************************* CLASS VARIABLES **************************************/
/*
*	Browser window width in pixels
*	Default set to 800
*/
var $_width;
/*
*	Graph table title
*/
var	$_tableTitle;
/*
*	Number of activity description columns
*/
var $_numCol;
/*
*	Activity description column titles
*/
var $_colTitle	= array();
/*
*	Activity description column widths
*/
var $_colWidth	= array();
/*
*	Parameters for expand/collapse display and management (not implementd)
*/
var $_expColIndex	= 0;				// label array index
var $_expColOption	= STATIC_EXPAND;	// display option
/*
*	Display only working days if true
*/
var $_workingDays;
/*
*	Graph scale start /end date in unixtime
*/
var $_startScaleDate;
var $_endScaleDate;
/*
*	Working day start/end time and hour increment in minutes
*/
var $_startHour	= 9;
var $_endHour	= 18;
var $_hourInc	= 30;
/*
*	Scale description header definition
*/
var $_showYear	= 1;
var $_showMonth	= 1;
var $_showWeek	= 0;
var $_showDay	= 0;
var $_showHour	= 0;
var $_enhancedScale = false;
/*
*	The current description of Gantt bars
*/
var $_barDesc	= array();
/**
* 	The current number of bars in the Gantt chart (size of barDesc array)
*/
var $_numBar	= 0 ;
/*
*	The current description of dependancies
*/
var $_barDepend	= array();
/**
* 	The current number of dependancies
*/
var $_numDepend	= 0 ;
/**
*	Width in pixel of the Gantt bar container
*/
var $_ctrWidth;
/*
*	Lowest cell period
*/
var $_cellPeriod	= GANTT_HMONTH;
/*
*	Number of the scale cells
*/
var $_numCell;
/*
*	Width in pixel of the scale cells
*/
var $_cellWidth;
/*
*	Date format (MYSQL by default)
*/
var $_dateFormat = '%Y%m%d';
var $_timeFormat = '%H%M%S';
/**
*	The left and right datetime limits of each scale cell
*/
var $_cellLimit = array();
/**
*	The HTML string of the bar container (to be used for each activity bar)
*/
var $_barContainer = '';
/**
*	Error and warning messages generated during display (stroke method)
*/
var $_errorMsg = array();
var $_warningMsg = array();
/**
*	User is allowed to change start/end dates (not used)
*/
var $_canMove = true;

/******************************************* CLASS PUBLIC METHODS **************************************/

function iGanttResponse( $start, $end, $width = 800, $working_days = true ) {
/**
* 	Class constructor
*		@param	=> scale start date
*		@param	=> scale end date
*		@param	=> browser window width
*		@param	=> display only week days
*
*/
	$sdate = new CDate( $start );
	$edate = new CDate( $end );
	if ( !$sdate->before($edate) )
		$this->setError(ERROR_101);
	$this->_startScaleDate = $sdate->format(FMT_DATETIME_MYSQL);
	$this->_endScaleDate = $edate->format(FMT_DATETIME_MYSQL);
	$this->_workingDays = $working_days ? true : false;
	$this->_width = round(0.98*$width);				// This is to account for rounding scale cells width
	return;
	}

function setDateFormat ( $fdate, $fhour = '%H:%M' ) {
/*
*	Set Date/Time format ( %S is not used)
*		@param	=> date format
*		@param	=> time format
*/
	$this->_dateFormat = $fdate ;
	$this->_hourFormat = $fhour ;
	return;
	}

function setWorkingHours( $start, $end, $inc=30 ) {
/*
*	set start working hour and end working hours for working days
*		@param	=> start working hour
*		@param	=> end working hour
*/
	$start = (int) $start;
	if ( $start < 0 || $start > 23 )
		$this->setError(ERROR_111);
	$end = (int) $end;
	if ( $end < 0 || $end > 23 || $end <= $start )
		$this->setError(ERROR_112);
	$this->_startHour = $start;
	$this->_endHour = $end;
	$this->_hourInc = $inc;
	return;
	}

function setTableTitle( $title ) {
/*
*	Set the graph table title
*		@param	=> title
*/
	$this->_tableTitle = $title;
	return;
	}

function setColTitle( $colTitle, $colWidth) {
/*
*	Set the label column titles
*		@param	=> column titles
*		@param	=> column widths
*/
	if ( ! $colTitle )
		$this->setError(ERROR_121);
	if ( ! $colWidth )
		$this->setError(ERROR_122);
	if ( !is_array($colTitle) )
		$colTitle = array($colTitle);
	if ( !is_array($colWidth) )
		$colWidth = array($colWidth);
	if ( count($colTitle) != count($colWidth) )
		$this->setError(ERROR_123) ;
	$this->_colTitle= $colTitle;
	foreach ( $colWidth as $w )
		$this->_colWidth[]= (int) $w;
	$this->_numCol = count($colTitle);
	return;
	}

function setExpandCollapse( $column, $option=STATIC_EXPAND ) { // NOT IMPLEMENTED
/*
*	Set the column where the expand/collapse and parent relationship icons should be inserted
*		@param	label column title
*		@param	display option
*/
	$this->_expColIndex = null;
	for ( $i=0; $i<count($this->_colTitle); $i++ ) {
		if ( $this->_colTitle[$i] == $column ) {
			$this->_expColIndex = $i;
			$this->_expColOption = $option;
			break;
		}
	}
	if ( !isset($this->_expColIndex) ) {
		$this->_expColIndex = 0;
		$this->_expColOption = STATIC_EXPAND;
		$this->warningMsg(ERROR_125);
	}
}

function setScaleHeader( $sFlag ) {
/*
*	Define Create scale header flag
*		@param	=> integer
*/
	$this->_showYear = 0;
	$this->_showMonth = 0;
	$this->_showWeek = 0;
	$this->_showDay = 0;
	$this->_showHour = 0;
	$this->_cellPeriod = 0;

	if ( $sFlag & GANTT_HYEAR ) {
		$this->_showYear = 1;
		$this->_cellPeriod = GANTT_HYEAR;
		}
	if ( $sFlag & GANTT_HMONTH ) {
		$this->_showMonth = 1;
		$this->_cellPeriod = GANTT_HMONTH;
		}
	if ( $sFlag & GANTT_HWEEK ) {
		$this->_showWeek = 1;
		$this->_cellPeriod = GANTT_HWEEK;
		}
	if ( $sFlag & GANTT_HDAY ) {
		$this->_showDay = 1;
		$this->_cellPeriod = GANTT_HDAY;
		}
	if ( $sFlag & GANTT_HHOUR ) {
		$this->_showHour = 1;
		$this->_cellPeriod = GANTT_HHOUR;
		}
	if ( $this->_cellPeriod == 0 )
		$this->setError(ERROR_131);
	return;
	}

/*
*	Set flag for enhanced scale
*		@param	true if scale enhanced, i.e. grey horizontal lines between bars and shadowed every second columns
*/
function setScaleEnhanced( $enhanced ) {
	$this->_enhancedScale = $enhanced ? true : false;
}

function newBar( $type, $level, $bar_id, $bar_parent, $start, $end, $complete, $label, $canMove=true ) {
/*
*	Create a new Gantt bar in the chart
*		@param	type of bar (Milestone, Dynamic, Activity)
*		@param	level in task hierarchy
*		@param	bar task ID
*		@param	parent task ID
*		@param	bar start date
*		@param	bar end date (not used if milestone)
*		@param	array of column labels
*		@param	user can move the bar on the chart if set
*/
	$desc = array();
// Check bar type
	if ( $type != GANTT_MILESTONE && $type != GANTT_DYNAMIC && $type != GANTT_ACTIVITY )
		$this->setWarning(ERROR_201." ".$bar_id) ;
	$desc['type'] = $type;
	$desc['level'] = $level;
	$desc['bar_id'] = $bar_id ;
	$desc['bar_parent'] = $bar_parent != $bar_id ? $bar_parent : 0;
	$sdate = new CDate( $start );
	$edate = $end ? new CDate( $end ) : new CDate( $start ) ;
// Check task start/end dates compatibility
	if ( $type != GANTT_MILESTONE && $sdate->after( $edate ) ) {
		$this->setWarning(ERROR_202." ".$bar_id) ;
		$edate = $sdate;
		}
	$desc['start'] = $sdate->format(FMT_DATETIME_MYSQL);
	$desc['end'] = $edate->format(FMT_DATETIME_MYSQL);
// Check task complete value
	$complete = (int) $complete;
	if ( $complete < 0 || $complete > 100 ) {
		$this->setWarning(ERROR_203." ".$bar_id);
		$complete = 100;
		}
	$desc['complete'] = $complete;
// Check label array
	if ( !is_array($label) )
		$label = array( $label );
	if ( count($label) != $this->_numCol ) {
		$this->setWarning(ERROR_204." ".$bar_id) ;
		if ( count($label) > $this->_numCol ) {					// too much labels => drop useless labels
			$label = array_slice( $label, 0, $this->_numCol);
		} else {												// too few labels => add blank labels as required
			$label = array_pad( $label, $this->_numCol, "");
		}
	}
	$desc['label'] = $label ;
	$desc['canMove'] = $canMove ? true : false ;
// Store new bar description
	$this->_barDesc[$this->_numBar] = $desc;
	$this->_numBar++;
	return false;
	}

function setConstrain( $req_id, $dep_id, $type ) {
/*
*	Add a dependancy in dependancy array
*	@param		=> requested activity ID
*	@param		=> dependant activity ID
*
*/
	$desc = array();
	$desc['req'] = (int) $req_id;
	$desc['dep'] = (int) $dep_id;
	$desc['type'] = $type;
	$this->_barDepend[$this->_numDepend] = $desc;
	$this->_numDepend++;
	return false;
	}

function setError( $msg ) {
/*
*	Set an error message for the user (this will prevent graph display)
*/
	$this->_errorMsg[count($this->_errorMsg)] = $msg;
	return;
}

function setWarning( $msg ) {
/*
*	Set an warning message for the user (this will not prevent graph display)
*/
	$this->_warningMsg[count($this->_warningMsg)] = $msg;
	return;
}

function stroke( $canEdit = true ) {
/*
*	create the Gantt  chart Ajax commands
*/
global $AppUI;
// Initialize display

	$this->clear( "error", "innerHTML");
//	$this->clear( "mainTable", "innerHTML");		// Test pour IE
	$this->clear( "warning", "innerHTML");
// Display general dP error messages
	$this->assign( "error", "innerHTML", $AppUI->getMsg() );

// Check dependencies and initialize HTML
	if ( count($this->_errorMsg) == 0 )
		$this->_graphCheck();
	foreach ( $this->_errorMsg as $msg )
		$this->append( "error", "innerHTML", "<h4>Error generating graph = $msg</h4>");
	foreach ( $this->_warningMsg as $msg )
		$this->append( "warning", "innerHTML", "<b>Warning:</b> $msg<br />");

// Do not start generating graph if an error has been detected
	if ( count($this->_errorMsg) > 0 )
		return;
// Initialize JS global variables
	$inc = intval(dPgetConfig('cal_day_increment')) ? intval(dPgetConfig('cal_day_increment')) : "30";
	$this->script("initGraph(\"{$this->_ctrWidth}\", \"{$this->_hourInc}\", \"{$this->_startHour}\", \"{$this->_endHour}\", \"{$this->_dateFormat}\", \"{$this->_timeFormat}\", \"".array_sum( $this->_colWidth )."\");");
	$this->addHandler("graphBody", "onmousemove", "mouseMove");
	$this->addHandler("graphBody", "onmouseup", "mouseUp");

// Initialize dates and working days checkbox
	$date = new CDate($this->_startScaleDate);
	$this->script("document.getElementById(\"show_sdate\").value=\"".$date->format($this->_dateFormat)."\";");
	$this->script("document.getElementById(\"sdate\").value=\"".$date->format(FMT_TIMESTAMP_DATE)."\";");
	$date = new CDate($this->_endScaleDate);
	$this->script("document.getElementById(\"show_edate\").value=\"".$date->format($this->_dateFormat)."\";");
	$this->script("document.getElementById(\"edate\").value=\"".$date->format(FMT_TIMESTAMP_DATE)."\";");
	if ( $this->_workingDays )
		$this->script("document.getElementById(\"workingDays\").checked=\"1\";");

// Create header HTML and bar container HTML
	$this->create( "mainTable", "tr", "headerLine");
/*	$this->assign( "headerLine", "innerHTML",*/ $this->_createHeader()/* )*/; 		// IE cannot use inneHTML
return;
// Create cell limit table in JS
	$leftStart = 0;
	for ( $i=0; $i<count($this->_cellLimit); $i++ ) {
		$rightEnd = $leftStart + $this->_cellWidth;
		$this->script( "setLimit(\"{$leftStart}\", \"{$this->_cellLimit[$i][0]}\", \"{$rightEnd}\", \"{$this->_cellLimit[$i][1]}\");");
		$ldate = new CDate($this->_cellLimit[$i][0]);
		$rdate = new CDate($this->_cellLimit[$i][1]);
		$leftStart += $this->_cellWidth;
	}

// Create Gantt bar HTML
	$trId = "headerLine";
	$row = 0;
	foreach ( $this->_barDesc as $bar ) {
		$this->insertAfter( $trId, "tr", "tr_".$bar['bar_id']);
		$trId = "tr_".$bar['bar_id'];
		$this->_createActivityBar( $trId, $row, $bar);
		$row++;
		}

// Draw dependancies
	foreach ( $this->_barDepend as $bar )
		$this->_createDepend( $bar['req'], $bar['dep'] );

// Create submit button and complete HTML
	$this->_closeHTML( $canEdit );

	return;
	}

/****************************************** CLASS PRIVATE METHODS ******************************************/

function _graphCheck() {
/*
*	Check graph definition and initialize HTML generation
*/
	global $AppUI;
	$continue = true;

// Check that all parent tasks are defined
	foreach ( $this->_barDesc as $bar ) {
		if ( $parent = $bar['bar_parent'] ) {
			$i = 0;
			while ( $i<$this->_numBar && $this->_barDesc[$i]['bar_id'] != $parent )
				$i++;
			if ( $i >= $this->_numBar )
				$this->setError(ERROR_501. " $parent for task {$bar['label']}");
		}
	}

// Check that all dependencies are defined and delete incomplete dependecies
	if ( $this->_numDepend > 0 ) {
		$idList = array();
		$error = true;
		foreach ( $this->_barDesc as $bar )
			$idList[] = $bar['bar_id'];
		$delete = array();
		for ( $i=0; $i<$this->_numDepend; $i++ ) {
			$bar = $this->_barDepend[$i];
			if ( !in_array( $bar['req'], $idList) ) {
				$this->setWarning(ERROR_510." {$bar['req']}");
				$delete[] = $i;
			} else if ( !in_array( $bar['dep'], $idList) ) {
				$this->setWarning(ERROR_511. " {$bar['dep']}");
				$delete[] = $i;
				}
		}
		$delete[]=count($this->_barDepend);
		if ( count($delete) > 0 ) {
			$j = 0;
			$k = 0;
			for ( $i=0; $i<$this->_numDepend; $i++ )
				if ( $i == $delete[$j] ) {
					$j++;
				} else {
					$this->_barDepend[$k] = $this->_barDepend[$i];
					$k++;
				}
		}
	}

// Adjust start/end date to start/end cells
	$sdate = new Cdate( $this->_startScaleDate );
	$edate = new Cdate( $this->_endScaleDate );
	$working_days = explode(',', dPgetConfig('cal_working_days'));
	$fdow = isset($working_days[0]) ? $working_days[0] : 1;
	switch ( $this->_cellPeriod ) {
		case GANTT_HHOUR :
			$this->_showDay = true;	// This is added to get a readable header
		case GANTT_HDAY :
			break;
		case GANTT_HWEEK :			// Adjust to week start day and week end day
			$dd = $sdate->getDay();
			$mm = $sdate->getMonth();
			$yy = $sdate->getYear();
			$sdate = new CDate(Date_Calc::beginOfWeek($dd, $mm, $yy, GANTT_DATE_FORMAT, $fdow) );
			$dd = $edate->getDay();
			$mm = $edate->getMonth();
			$yy = $edate->getYear();
			$edate = new CDate(Date_Calc::endOfWeek($dd, $mm, $yy, GANTT_DATE_FORMAT, $fdow) );
			break;
		case GANTT_HMONTH :			// Adjust to month start day and month end day
			$mm = $sdate->getMonth();
			$yy = $sdate->getYear();
			$sdate = new CDate(Date_calc::beginOfMonth($mm, $yy, GANTT_DATE_FORMAT) );
			$mm = $edate->getMonth()+1;
			$yy = $edate->getYear();
			if ( $mm == 13 ) {
				$mm = 1;
				$yy++;
			}
			$edate = new CDate(Date_calc::endOfPrevMonth( "15", $mm, $yy, GANTT_DATE_FORMAT) );
			break;
		case GANTT_HYEAR :			// Adjust to month start/end day
			$yy = $sdate->getYear();
			$sdate = new CDate(Date_calc::beginOfMonth(1, $yy, GANTT_DATE_FORMAT) );
			$yy = $edate->getYear();
			$sdate = new CDate(Date_calc::endOfMonth(12, $yy, GANTT_DATE_FORMAT) );
			break;
		}

// Adjust to working days if only week days are displayed
	if ( $this->_workingDays ) {
		$sdate = $sdate->isWorkingDay() ? $sdate : $sdate->next_working_day();	
		$edate = $edate->isWorkingDay() ? $edate : $edate->prev_working_day();
		}

// Set time to start working hour and end working hour and format dates
	$sdate->setTime( $this->_startHour, 0, 0 );
	$this->_startScaleDate = $sdate->format(FMT_DATETIME_MYSQL);
	$edate->setTime( $this->_endHour, 0, 0 );
	$this->_endScaleDate = $edate->format(FMT_DATETIME_MYSQL);

// Calculate container and cell widths
// Bar container width = graph width - label table width
	$this->_ctrWidth = $this->_width - array_sum( $this->_colWidth );
	switch ( $this->_cellPeriod ) {
		case GANTT_HHOUR :
			$ndays = $this->_workingDays ? $sdate->workingDaysInSpan( $edate ) : $edate->dateDiff( $sdate )+1 ; // we need to account for both start and end days in date diff
			$this->_numCell = $ndays * ($this->_endHour - $this->_startHour);	
			break;
		case GANTT_HDAY :
			$this->_numCell = $this->_workingDays ? $sdate->workingDaysInSpan( $edate ) : $edate->dateDiff( $sdate )+1 ;
			break;
		case GANTT_HWEEK :
			$this->_numCell = 0;
			$date = new CDate( $sdate );
			while ( !$date->after($edate) ) {
				$this->_numCell++;
				$date->addDays(7);
				}
			break;
		case GANTT_HMONTH :
			$this->_numCell = 0;
			$date = new CDate( $sdate );
			while ( !$date->after($edate) ) {
				$this->_numCell++;
				$date->addMonths(1);
				}
			break;
		case GANTT_HYEAR :
			$this->_numCell = $edate->getYear() - $sdate->getYear() + 1;
			break;
		}
	$this->_cellWidth = round($this->_ctrWidth/$this->_numCell) ;
	$this->_ctrWidth = $this->_numCell * $this->_cellWidth; // to account for rounding and border
	return $continue ;
	}

	function _createHeader() {
/*
*	create HTML string for table and scale headers
*/

// Generate scale header content and column width
// Each header line will be generated as a separate table
// Generate left and right time limit for each cell as an array
	$cellDef = array();
	$colwidth = NULL;
	$sdate = new CDate( $this->_startScaleDate );
	$edate = new CDate( $this->_endScaleDate );
	$working_days = explode(',', dPgetConfig('cal_working_days'));
	$fdow = $working_days[0];
	switch ( $this->_cellPeriod ) {
		case GANTT_HHOUR :
			$cellDef[GANTT_HHOUR] = array();
			$ldate = new CDate( $sdate );
			$rdate = new CDate( $sdate);
			$mod = $this->_endHour - $this->_startHour;
			for ( $i=0; $i<$this->_numCell; $i++ ) {
				$hourinc = $i%$mod;
				$cellDef[GANTT_HHOUR][] = array( 'value' => $this->_startHour+$hourinc, 'class' => 'chour', 'colwidth' => $this->_cellWidth-1 );
				if ( $hourinc == 0 && $i != 0 ) {
					$ldate = $this->_moveDays( $ldate, 1);
					$ldate->setTime( $this->_startHour, 0, 0);
					$rdate = $ldate;
					$rdate->setTime( $this->_startHour+1, 0, 0);
				} else {
					$ldate->setTime( $this->_startHour+$hourinc, 0, 0);
					$rdate->setTime( $this->_startHour+$hourinc+1, 0, 0);
				}
				$this->_cellLimit[] = array( $ldate->getDate(DATE_FORMAT_UNIXTIME), $rdate->getDate(DATE_FORMAT_UNIXTIME) );
			}
		case GANTT_HDAY :
			$cellDef[GANTT_HDAY] = array();
			$date = new CDate( $sdate );
			$daywidth = $this->_cellWidth * ( $cellDef[GANTT_HHOUR] ? ($this->_endHour - $this->_startHour) : 1 );
			while ( !$date->after($edate) )	{
				$cellContent = $this->_cellPeriod == GANTT_HDAY ? substr($date->format("%a"), 0, 1) : $date->format($this->_dateFormat) ;
				$cellDef[GANTT_HDAY][] = array( 'value' => $cellContent, 'class' => 'cday', 'colwidth' => $daywidth-1 ); // -1 accounts for border width
				if ( $this->_cellPeriod == GANTT_HDAY ) {
					$ldate = new CDate( $date );
					$ldate->setTime( $this->_startHour, 0, 0 );
					$rdate = new CDate( $date );
					$rdate->setTime( $this->_endHour, 0, 0 );
					$this->_cellLimit[] = array( $ldate->getDate(DATE_FORMAT_UNIXTIME), $rdate->getDate(DATE_FORMAT_UNIXTIME) );
				}
				$date = $this->_moveDays( $date, 1);
			}
		case GANTT_HWEEK :
			$cellDef[GANTT_HWEEK] = array();
			$date = new CDate( $sdate );
			$bdate = new CDate( $sdate );
			$cdate = new CDate( $sdate );
			$cwinc = $cellDef[GANTT_HDAY] ? $daywidth : $this->_ctrWidth / (( $this->_workingDays ? $cdate->workingDaysInSpan( $edate ) : $sdate->dateDiff( $edate ))+1) ;
			$weekwidth	= $cellDef[GANTT_HDAY] ? ( $this->_workingDays ? count($working_days) : 7 )*$cwinc : $this->_cellWidth ;
			$swidth = 0;
			while ( !$date->after( $edate ) ) {
				$dd = $bdate->getDay();
				$mm = $bdate->getMonth();
				$yy = $bdate->getYear();
				$ww = Date_Calc::weekOfYear( $dd, $mm, $yy );
				$date = new CDate(Date_Calc::beginOfNextWeek( $dd, $mm, $yy, GANTT_DATE_FORMAT, $fdow ));
				$cdate = $this->_moveDays( $date, -1);
				$cdate->setTime( $this->_endHour, 0, 0 );
				if ( !$cdate->before( $edate ) ) {	// End of scale : get the remaining width as cell width
					$cwidth = $this->_ctrWidth - $swidth;
				} else if ( $swidth == 0 ) {		// First week cell : account for partial week if the lowest scale is hours or days
					$cwidth = round($cwinc * ( $this->_workingDays ? $bdate->workingDaysInSpan( $cdate ) : $cdate->dateDiff($bdate)+1 ));
				} else {							// Full week : width = standard cell width
					$cwidth = $weekwidth;
				}
				$swidth += $cwidth;
				$cellDef[GANTT_HWEEK][] = array( 'value' => WEEK_MARKER.$ww, 'class' => 'cweek', 'colwidth' => $cwidth-1 ); // -1 accounts for border width
				if ( $this->_cellPeriod == GANTT_HWEEK ) {
					$bdate->setTime( $this->_startHour, 0, 0 );
					$this->_cellLimit[] = array( $bdate->getDate(DATE_FORMAT_UNIXTIME), $cdate->getDate(DATE_FORMAT_UNIXTIME) );
					}
				$bdate = $date;
			}
		case GANTT_HMONTH :
		$cellDef[GANTT_HMONTH] = array();
			$date = new CDate( $sdate );
			$bdate = new CDate( $sdate );
			$cdate = new CDate( $sdate );
			$cwinc = $cellDef[GANTT_HDAY] ? $daywidth : $this->_ctrWidth / (( $this->_workingDays ? $cdate->workingDaysInSpan( $edate ) : $edate->dateDiff( $sdate ) )+1) ;
			$swidth = 0;
			while ( !$date->after( $edate ) ) {
				$dd = $date->getDay();
				$mm = $date->getMonth();
				$yy = $date->getYear();
				$date = new CDate(Date_Calc::beginOfNextMonth( $dd, $mm, $yy, GANTT_DATE_FORMAT));
				$cdate = $this->_moveDays( $date, -1);
				$cdate->setTime( $this->_endHour, 0, 0 );
				if ( $cellDef[GANTT_HWEEK] ) {
					if ( !$cdate->before( $edate ) ) {
						$cdate = new CDate( $edate );
						$cwidth = $this->_ctrWidth - $swidth ;
					} else {
						$cwidth = round($cwinc * (( $this->_workingDays ? $bdate->workingDaysInSpan( $cdate ): $cdate->dateDiff( $bdate )+1)));
					}
				} else {
					$cwidth = $this->_cellWidth;
				}
				$swidth += $cwidth ;
				$cellDef[GANTT_HMONTH][] = array( 'value' => convText($bdate->format("%b")), 'class' => 'cmonth', 'colwidth' => $cwidth-1 );	// -1 accounts for border width
				if ( $this->_cellPeriod == GANTT_HMONTH ) {
					$bdate->setTime( $this->_startHour, 0, 0 );
					$this->_cellLimit[] = array( $bdate->getDate(DATE_FORMAT_UNIXTIME), $cdate->getDate(DATE_FORMAT_UNIXTIME) );
					}
				if ( $this->_workingDays )
					$date = $date->next_working_day();
				$bdate = $date ;
			}
		case GANTT_HYEAR :
			$cellDef[GANTT_HYEAR] = array();
			$date = new CDate( $sdate );
			$bdate = new CDate( $sdate );
			$cwinc = $this->_ctrWidth / (( $this->_workingDays ? $sdate->workingDaysInSpan( $edate ) : $edate->dateDiff( $sdate ) ) +1) ;
			$swidth = 0;
			while ( !$date->after( $edate ) ) {
				$yy = $bdate->getYear();
				$date = new CDate( Date_Calc::dateFormat( 1, 1, $yy+1, GANTT_DATE_FORMAT ));
				$cdate = $this->_moveDays( $date, -1);
				$cdate->setTime( $this->_endHour, 0, 0 );
				if ( $cdate->after( $edate ) ) {
					$cdate = new CDate( $edate );
					$cwidth = $this->_ctrWidth - $swidth ;
				} else {
					$cwidth = $cwinc * (( $this->_workingDays ? $bdate->workingDaysInSpan( $cdate ): $cdate->dateDiff( $bdate ))+1);
				}
				$swidth += $cwidth ;
				$cellDef[GANTT_HYEAR][] = array( 'value' => $bdate->format("%Y"), 'class' => 'cyear', 'colwidth' => round($cwidth)-1 );	// -1 accounts for border width
				if ( $this->_cellPeriod == GANTT_HYEAR )
					$this->_cellLimit[] = array( $bdate->getDate(DATE_FORMAT_UNIXTIME), $cdate->getDate(DATE_FORMAT_UNIXTIME) );
				if ( $this->_workingDays )
					$date = $date->next_working_day();
				$bdate = $date ;
			}
	}

// Create  Gantt bar container
	$this->_barContainer = "\t<td>\n\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"table-layout:fixed;\">\n";
	$this->_barContainer .= "\t<tr>\n";
	foreach ( $cellDef[$this->_cellPeriod] as $cell )
		$this->_barContainer .= "\t\t<td class=\"cellc\" width=\"".$cell['colwidth']."\" align=\"center\" valign=\"top\" >&nbsp;</td>\n";
	$this->_barContainer .= "\t<tr>\n\t</table>\n\t</td>\n";

// Generate scale header table
	$scaleHeader = array();
	if ( $this->_showYear )
		$scaleHeader[] = array( 'content' => $cellDef[GANTT_HYEAR] , 'height' => HEIGHT_HYEAR, 'class' => CLASS_HYEAR );
	if ( $this->_showMonth )
		$scaleHeader[] = array( 'content' => $cellDef[GANTT_HMONTH], 'height' => HEIGHT_HMONTH, 'class' => CLASS_HMONTH );
	if ( $this->_showWeek )
		$scaleHeader[] = array( 'content' => $cellDef[GANTT_HWEEK], 'height' => HEIGHT_HWEEK, 'class' => CLASS_HWEEK );
	if ( $this->_showDay )
		$scaleHeader[] = array( 'content' => $cellDef[GANTT_HDAY], 'height' => HEIGHT_HDAY, 'class' => CLASS_HDAY );
	if ( $this->_showHour )
		$scaleHeader[] = array( 'content' => $cellDef[GANTT_HHOUR], 'height' => HEIGHT_HHOUR, 'class' => CLASS_HHOUR );

// Determine table and scale header line heigths
	$height = $this->_showYear*HEIGHT_HYEAR + $this->_showMonth*HEIGHT_HMONTH + $this->_showWeek*HEIGHT_HWEEK + $this->_showDay*HEIGHT_HDAY + $this->_showHour*HEIGHT_HHOUR;
	if ( $height >= 20 ) {
		$titleHeight = round(0.6*$height);
		$colHeight = $height-$titleHeight;
	} else {
		$nl = count($scaleHeader); // $nl value is 1 or 2
		$hheight = 20/$nl;
		for ( $i=0; $i<$nl; $i++ )
			$scaleHeader[$i]['height'] = $hheight;
			$titleHeight = 12;
			$colHeight = 8;
	}

// Generate HTML 
//	$s = "";
//	$s .= "\t<td width=\"".array_sum( $this->_colWidth )."\" >\n";
	$this->create("headerLine", "td", "labelTd");
	$this->assign("labelTd", "width", array_sum( $this->_colWidth ));
//	$s .= "\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"table-layout:fixed;\">\n";
	$this->create("labelTd", "table", "labelTable");
	$this->assign("labelTable", "border", "0");
	$this->assign("labelTable", "cellpadding", "0");
	$this->assign("labelTable", "cellspacing", "0");
	$this->assign("labelTable", "width", "100%");
	$this->_setStyle("labelTable", "table-layout:fixed;");
//	$s = "\t<colgroup>\n";
	$this->create("labelTable", "colgroup", "labelColGroup");
   foreach ( $this->_colWidth as $cw ) {
//		$s .= "<col width=\"$cw\" >\n";
		$this->create("labelColGroup", "col", "col".$cw );
		$this->assign("col".$cw, "width", $cw );
	}
//    $s .= "\t</colgroup>\n";
//	$s = "\t\t<tr height=\"$titleHeight\" >\n";
	$this->create("labelTable", "tr", "trTitle");
	$this->assign("trTitle", "height", $titleHeight);
//	$s .= "\t\t\t<td class=\"ctitle\" colspan=\"{$this->_numCol}\" valign=\"middle\" >{$this->_tableTitle}</td>\n";
	$this->create("trTitle", "td", "titleTd");
	$this->_setClass("titleTd", "ctitle");
	$this->assign("titleTd", "colspan", $this->_numCol );
	$this->assign("titleTd", "width", "100%");
	$this->assign("titleTd", "valign", "middle");
	$this->assign("titleTd", "innerHTML", $this->_tableTitle);
//	$s .= "\t\t</tr>\n";
//	$s .= "\t\t<tr height=\"$colHeight\" >\n";
	$this->create("labelTable", "tr", "trLabelTitle");
	$this->assign("trLabelTitle", "height", $colHeight);
//	$style = "style=\"border-left:none;\"";
	$style = "border-left:none;";
	for ( $i=0; $i<$this->_numCol; $i++ ) {
//		$s.= "\t\t\t<td class=\"ccol\" align=\"center\" valign=\"middle\" $style >{$this->_colTitle[$i]}</td>\n";
		$this->create("trLabelTitle", "td", "tdLabelTitle".$i);
		$this->_setClass("tdLabelTitle".$i, "ccol" );
		$this->assign("tdLabelTitle".$i, "align", "center");
		$this->assign("tdLabelTitle".$i, "valign", "middle");
		$this->_setStyle("tdLabelTitle".$i, $style);
		$this->assign("tdLabelTitle".$i, "innerHTML", $this->_colTitle[$i] );
		$style = "";
		}
//	$s .= "\t\t</tr>\n";
//	$this->append("labelTable", "innerHTML", $s);
//	$s .= "\t</table>\n";
//	$s .= "\t</td>\n";
//	$s .= "\t<td width=\"{$this->_ctrWidth}\" >\n";
	$this->create("headerLine", "td", "scaleTd");
	$this->assign("scaleTd", "width", $this->_ctrWidth);
//	$s .= "\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout:fixed;\" >\n";
	$this->create("scaleTd", "table", "scaleHeaderTable");
	$this->assign("scaleHeaderTable", "border", "0");
	$this->assign("scaleHeaderTable", "cellpadding", "0");
	$this->assign("scaleHeaderTable", "cellspacing", "0");
	$this->assign("scaleHeaderTable", "width", "100%");
	$this->_setStyle("scaleHeaderTable", "table-layout:fixed;");

	$countTr = 0;
	foreach ( $scaleHeader as $sh ) {
//		$s.= "\t\t<tr>\n";
		$this->create("scaleHeaderTable", "tr", "trScaleHeaderTable".$countTr );
		$this->create("trScaleHeaderTable".$countTr, "td", "tdTrScaleHeaderTable".$countTr );
//		$s .= "\t\t\t<td width=\"100%\" >\n";
		$this->assign("tdTrScaleHeaderTable".$countTr, "width", "100%");
//		$s .= "\t\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"table-layout:fixed;\" >\n";
		$this->create("tdTrScaleHeaderTable".$countTr, "table", "tableTdTrScaleHeaderTable".$countTr);
		$this->assign("tableTdTrScaleHeaderTable".$countTr, "border", "0");
		$this->assign("tableTdTrScaleHeaderTable".$countTr, "cellpadding", "0");
		$this->assign("tableTdTrScaleHeaderTable".$countTr, "cellspacing", "0");
		$this->assign("tableTdTrScaleHeaderTable".$countTr, "width", "100%");
		$this->_setStyle("tableTdTrScaleHeaderTable".$countTr, "table-layout:fixed;");
//		$s .= "\t\t\t<tr height=\"".$sh['height']."\" >\n";
		$this->create("tableTdTrScaleHeaderTable".$countTr, "tr", "trTableTdTrScaleHeaderTable".$countTr);
		$this->assign("trTableTdTrScaleHeaderTable".$countTr, "height", $sh['height']);
		$countTd = 0;
		foreach ( $sh['content'] as $cell ) {
//			$s .= "\t\t\t<td class=\"".$cell['class']."\" width=\"".$cell['colwidth']."\" align=\"center\" valign=\"middle\" >".$cell['value']."</td>\n";
			$this->create("trTableTdTrScaleHeaderTable".$countTr, "td", "tdTrTableTdTrScaleHeaderTable".$countTr.$countTd );
			$this->_setClass("tdTrTableTdTrScaleHeaderTable".$countTr.$countTd, $cell['class']);
			$this->assign("tdTrTableTdTrScaleHeaderTable".$countTr.$countTd, "width", $cell['colwidth']);
			$this->assign("tdTrTableTdTrScaleHeaderTable".$countTr.$countTd, "align", "center");
			$this->assign("tdTrTableTdTrScaleHeaderTable".$countTr.$countTd, "valign", "middle");
			$this->assign("tdTrTableTdTrScaleHeaderTable".$countTr.$countTd, "innerHTML", $cell['value']);
			$countTd++;
		}
//		$s .= "\t\t\t</tr>\n";
//		$s .= "\t\t\t</table>\n";
//		$s .= "\t\t</td>\n";
//		$s .= "\t\t</tr>\n";
		$countTr++;
	}
//	$s .= "\t\t</table>\n";
//	$s .= "\t</td>\n";
//	$s .= "\t</tr>\n";
	return /* $s */;
	}

function _createActivityBar( $trId, $row, $bar ) {
/*
*	Display a task bar in the main Table
*		@param	HTML object container
*		@param	bar row number
*		@param	bar description array
*/

// First column contains a single row table that contains the bar labels
	$s ="\t<td>\n";
	$s .= "\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"table-layout:fixed;\" >\n";
	$s .= "\t<colgroup>\n";
    foreach ( $this->_colWidth as $cw )
		$s .= "<col width=\"$cw\" >\n";
    $s .= "\t</colgroup>\n";
	$s .= "\t\t<tr>\n";
	$id = $bar['bar_id'];
	$type = $bar['type'];
	$level = $bar['level'];
	if ( $type == GANTT_DYNAMIC ) {
		$bar['label'][0] = "<img id=\"collapse_$id\" src=\"./images/icons/collapse.gif\" height=\"11\" width=\"11\" style=\"display:inline;\" onClick=\"collapse({$bar['bar_id']});\">&nbsp;" . $bar['label'][0];
		$bar['label'][0] = "<img id=\"expand_$id\" src=\"./images/icons/expand.gif\" height=\"11\" width=\"11\" style=\"display:none;\" onClick=\"expand({$bar['bar_id']});\">" . $bar['label'][0];
	}
	if ( $bar['level'] > 0 ) {
		$bar['label'][0] = "<img src=\"./images/corner-dots.gif\" height=\"12\" width=\"16\">" . $bar['label'][0];
		if ( $level > 1 ) {
			$width = ($level-1)*11;
			$bar['label'][0] = "<img src=\"./modules/igantt/images/clear.gif\" height=\"11\" width=\"$width\">" . $bar['label'][0];
			}
	}
	$style = "style=\"border-left:none;\"";
	for ( $i=0; $i<$this->_numCol; $i++ ) {
		$s .= "\t\t\t<td class=\"labelc\" align=\"center\" valign=\"middle\" $style >".$bar['label'][$i]."</td>\n";
		$style = "";
		}
	$s .= "\t\t</tr>\n";
	$s .= "\t\t</table>\n";
	$s .= "\t</td>\n";
	$this->assign( $trId, "innerHTML", $s);

// Second column contains a table the contains the bar container and div's that display the Gantt bar
	$this->_createBarGraph( $trId, $row, $level, $type, $id, $bar['bar_parent'], $bar['start'], $bar['end'], $bar['complete'], $bar['canMove'] );
	return;
	}

function _locateBarEnd( $val ) {
/*
*	Determine the abscisse of a date in the graph
*		@param	date in unix time to be located
*/
	$imin = 0;
	$imax = count($this->_cellLimit)-1;

// Bar end abscisse is the limit of the graph when the bar end is outside the graph
	if ( $val < $this->_cellLimit[$imin][0] )	// The activity starts before the left end side of the graph
		return 0;
	if ( $val > $this->_cellLimit[$imax][1] )	// The activity ends after the right end side of the graph
		return $this->_ctrWidth;

// Search the corresponding index in cellLimit array by dichotomy
	while ( $imin < $imax ) {
		$i = $imin + round(($imax-$imin)/2);
		if ( $this->_cellLimit[$i][1] >= $val ) {
			$imax = $i ;
			if ( $this->_cellLimit[$i][0] <= $val ) {
				$imin = $i ;
			} else {
				$imax--;
			}
		} else {
			$imin = $i;
		}
	}
	$i = $imin ;

// Determine the abscisse value by interpolation
	$x = (float)0;
	for ( $j=0; $j<$i ; $j++ )
		$x += $this->_cellWidth;
	if ( $val > $this->_cellLimit[$i][1] ) {
		$x += $this->_cellWidth;
	} else if ( $val >= $limit[$i][0] ) {
		$x += $this->_cellWidth*(($val-$this->_cellLimit[$i][0])/($this->_cellLimit[$i][1]-$this->_cellLimit[$i][0])) ;
	}
// Return rounded abscisse
	return round($x) ;
}

function _createBarGraph( $trId, $row, $level, $type, $id, $parent, $start, $end, $complete, $canMove=true ) {
/*
*	Generate the HTML code for displaying the bar container and the bar graph
*		@param	Id of the tr element to be populated
*		@param	row number in the graph
*		@param	level of bar in task tree
*		@param	type of bar : milestone, dynamic, activity
*		@param	ID  of the bar
*		@param	parent ID or 0
*		@param	start date in Unix time
*		@param	end date in Unix time
*		@param	task percent complete
*		@param	if true user can modifiy task end/start dates
*/

	$sdate = new CDate($start);
	$edate = new CDate($end);
	$xstart = $sdate->getDate(DATE_FORMAT_UNIXTIME);
	$xend = $edate->getDate(DATE_FORMAT_UNIXTIME);
	$minDate = $this->_cellLimit[0][0];
	$maxDate = $this->_cellLimit[count($this->_cellLimit)-1][1];
	$canMove = $canMove ? 3 : 0;

//	Create table for scale
	$s .= "\t<td valign=\"top\" >\n";
	$s .= "\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"clear:both;\" >\n";
	$s .= "\t<tr>\n";

//	Display barContainer
	$s .= $this->_barContainer;
	$s .= "\t</tr >\n";
	$s .= "\t</table>\n";

// 	Determine position of bar ends
	$x = $this->_locateBarEnd( $xstart );
	$y = $type != GANTT_MILESTONE ? $this->_locateBarEnd( $xend ) : min($x+5, $this->_ctrWidth);

//	Create DIV container for the activity bar
	$s .= "\t<div style=\"width:100%;\" >\n";
	$s .= "\t<div class=\"activity\" id=\"bar_$id\" style=\"margin-left:".$x."px;width:".($y-$x)."px;\">\n";
	
	if ( $xstart > $maxDate || $xend < $minDate ) {
	//	If the bar is outside the graph display only the DIV container
		$drag = -1;
	} else {
	//	Create div's for bar display depending on bar type
		switch ( $type ) {
			case GANTT_MILESTONE :
				if ( $complete >= 100 ) {
					$img = "./modules/igantt/images/milestonecomp.gif";
					$drag = 4;
				} else {
					$img = "./modules/igantt/images/milestoneinc.gif" ;
					$drag = $canMove ? 7 : 4;
				}
				$s .= "\t<img src=\"$img\" width=\"11px\" height=\"11px\" style=\"margin-left:-5px;\" >\n";
				$s .= "\t</div\n";
				break;
			case GANTT_DYNAMIC :
				$s .= "\t<div class=\"ad\" ></div>\n";
				if ( $xstart >= $minDate )		// Show bar limit only if the task start date is within the limit of the graph scale
					$s .= "\t<div class=dl><img src=\"./modules/igantt/images/dynleft.gif\"></div>\n";
				if ( $xend <= $maxDate )		// Show bar limit only if the task end date is within the limit of the graph scale
					$s .= "\t<div class=dr><img src=\"./modules/igantt/images/dynright.gif\"></div>\n";
				$s .= "\t</div>\n";
				$drag = 8;
				break;
			case GANTT_ACTIVITY :
				if ( $complete >= 100 ) {
					$s .= "\t<div class=\"at\" ></div>\n";
					$drag = 0;
				} else {
					$s .= "\t<div class=\"ai\" ></div>\n";
					$drag = $canMove ? (($xstart >= $minDate ? 1 : 0 ) + ( $xend <= $maxDate ? 2 : 0 )) : 0;
					if ( $complete > 0 ) {
						$s .= "\t<div class=\"ac\" style=\"width:{$complete}%;\" ></div>\n";
						$drag = $drag & 2;
					}
				}
				$s .= "\t</div>\n";
				break;
			}
	}
	$s .= "\t</div>\n";
	$s .= "\t</td>\n";
	$this->append( $trId, "innerHTML", $s);
	$this->script("createBar(document.getElementById(\"bar_$id\"), \"$level\", \"$row\", \"$drag\", \"$id\", \"$parent\");");
	$this->addHandler( "bar_$id", "onmousedown", "onMouseDownItem");
	if ( $drag > 0 && $drag & 3 ) {
		$this->addHandler( "bar_$id", "onmouseover", "onMouseOverItem");
		$this->addHandler( "bar_$id", "onmouseout", "onMouseOutItem");
	}
	return;
	}

function _createDepend( $req, $dep ) {
/*
*	Display a dependency graph between two tasks
*/
	$this->script("setDependent(\"$req\", \"$dep\", 0);");
	return false;
	}
	
function _closeHTML( $flag ) {
/*
*	Create footer
*		@param	if true display task edit button
*/
	global $AppUI;
	if ( $flag ) {
		$this->script( "document.getElementById(\"submitBtn\").style.visibility=\"visible\";");
		}
	$this->script("document.getElementById(\"taskDetails\").style.display=\"none\";");
	$this->script("hideLoading();");
	return;
	}

function _moveDays( $date, $dir ) {
/*
*	Utilitary function to calculate next day forward/backward taking into account working days or not according to workingDays flag
*/
	$d = new CDate($date);
	$d->addDays( $dir );
	if ( $this->_workingDays ) {
		while ( ! $d->isWorkingDay() )
			$d->addDays( $dir ) ;
	}
	return $d ;
}

function _setStyle( $item, $style ) {
	$this->script(
		"if (document.all) {
			document.getElementById(\"$item\").style.setAttribute(\"cssText\", \"$style\");
		} else {
			document.getElementById(\"$item\").setAttribute(\"style\", \"$style\");
		}");
	return ;
}

function _setClass( $item, $class ) {
	$this->script(
		"if (document.all) {
			document.getElementById(\"$item\").setAttribute(\"className\", \"$class\");
		} else {
			document.getElementById(\"$item\").setAttribute(\"class\", \"$class\");
		}");
	return ;
}
}
?>