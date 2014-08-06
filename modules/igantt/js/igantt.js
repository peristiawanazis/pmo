/* IGANTT igantt.js v0.1.2 2009/02/04 */
/*
Copyright (c) 2008 -2009 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	iGantt JavaScript library - Contains all JavaScript routines used to display a Gantt chart and manage user interaction
*			This work makes use of the XAJAX library that is distributed under the BSD licence
*			This work makes use of the ws_jsgraphics library that is distributed under the GNU/LGPL licence
*
* Author:		Pierre-Yves SIMONOT, Euxenis, <pierre-yves.simonot@euxenis.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.1
* 	Creation
* version 0.1.2
*	Use right click to display/update tasks
*	Allow task detailed information display for completed and dynamic tasks
*	New function setSelectOptionIndex for task detailed information overlay display
*	New function setAMPM for task detailed information overlay display (copied from tasks/addedit.js)
*	Correct bug in prevDate and nextDate function  (accounting for Feb month and bissextile years)
*
*/
/*************************** INITIALIZATION ************************************************/
function initGraph(max, inc, start, end, Dformat, Hformat, width) {
/*
*	Initialize global variables for this graph
*		@param	max abscisse value in the graph
*		@param	minimum time increment in minutes
*		@param	day start working hour
*		@param	day end working hour
*		@param	date format (PHP like)
*		@param	hour format (PHP like)
*		@param	width of label table
*/
overObject	= null;			// element ID of the bar that mouse is over
dragObject  = null;			// element ID of the bar to be changed
mouseOffset = null;			// mouse offset position relative to bar element
dragType	= null;			// 0 = no dragging; 1 = drag start date only; 2 = drag end date only; 3 = drag both ends; 4 = not draggable milestone; 7=draggable milestone
startmove 	= null;			// Flag : start date to be changed depending on mouse moves
endmove		= null;			// Flag : end date to be changed according to mouse moves
mousePos	= null;			// Initial/previous mouse coordinates
dragType	= null;			// global draggable object type
barNum		= 0;			// Number of bargraphs in the graph
barArray	= new Array;	// Bargraph object array
barIndex	= new Array;	// Array of bar names
Xmax		= max;			// Max x value (right hand side of the graph)
cellNum		= 0;			// Number of cells in date scale
cellArr		= new Array;	// Array of cell limits
hourInc		= inc;			// hour increment in minutes
dayStart	= start;		// Day start hour
dayEnd		= end;			// Day end hour
dateFormat	= Dformat;		// Date format
hourFormat	= Hformat;		// Hour format
colWidth	= width			// Width of the label table
undoStack	= new Array;	// Stack of moved bar
depArray	= new Array;	// list of dependencies ( required task ID, dependent task ID)
depNum		= 0;			// Number of dependencies
depObject	= null;			// required task ID dependency creation

return true;
}

/**************************** EVENT HANDLERS **********************************************************/

function mouseMove(ev){
/*
*	Process mouse moves
*		@param	mouse event
*		@return	true
*/
	ev	= ev || window.event;
// If an object has been selected, then move this object
	if (dragObject) {
		overObject = null;
		var mouseNewPos = mouseCoords(ev);
		var xmove = parseInt(mouseNewPos.x - mousePos.x);
		var x = parseInt(dragObject.style.marginLeft);
		var w = parseInt(dragObject.style.width);
		if ( startmove == true ) {	// The start date can be moved
			x	+= xmove;			// Change marginLeft according to mouse move
			w	-= xmove;			// Keep the bar end position constant
			if ( x<=0 ) { x=0 }		// don't drag outside the graph left boundary
			if ( x>= Xmax )			// don't drag outside the graph right boundary
				{ x = Xmax-11 ;} 
		}
		if ( endmove == true ) {	// The end date can be moved
			w	+= xmove;			// Move the bar end according to mouse move
			var cend = parseInt(x)+parseInt(w);
			if ( cend >= Xmax ) {	// Don't move outside the graph left boundary
				if ( startmove == true ) {
					x = x-cend+parseInt(Xmax);
				}
				w = parseInt(Xmax)-x ;
				}
			if ( w<=11 && dragType < "4" ) { w=11 }		// Keep a minimum bar width otherwise one cannot drag it again
		}
		// Update moved object marginLeft and width
		dragObject.style.marginLeft	= x+"px";
		dragObject.style.width		= w+"px";
		// Update start/end dates display
		displayOverDates( x, w );
		// Update color of dependency limits
		var line = null;
		if ( line = document.getElementById("leftLimit")) {
			if ( parseInt(line.style.marginLeft)>x ) { // moved object does not fit with required task end
				var color = "red"; 
			} else {
				var color = "black"; }
			line.style.backgroundColor = color;
			}
		if ( line = document.getElementById("rightLimit")) {
			var posx = x;
			if ( dragType < "4" ) { posx += w; }
			if ( parseInt(line.style.marginLeft)<(posx) ) {	// moved objecy does notfit with dependent task start 
				var color = "red"; 
			} else {
				var color = "black"; }
			line.style.backgroundColor = color;
			}
		// Keep track of last mouse position to calculate future moves
		mousePos = mouseNewPos;
		return true;
	}

// If the pointer is over a draggable object, then adapt the cursor display and initialize dragging
	if (overObject) {
		mouseOffset = getMouseOffset(overObject, ev);
		width = parseInt(overObject.style.width);
		if ( dragType == "7" ) {											// For milestones : move the whole div
			overObject.style.cursor = "move";
			startmove = true;
			endmove = true;
		} else {
			startmove	= false;
			endmove		= false;
			if ( mouseOffset.x < 5 && (dragType == "1" || dragType == "3")) {	// If bar left end is hovered, move the start date if allowed (not started tasks)
				overObject.style.cursor = "w-resize";
				startmove = true;
				endmove = false;
			} else if ( mouseOffset.x > width-5 && (dragType == "2" || dragType == "3") ) { // If bar right end is hovered, move task end date if allowed (task not finishing after graph end limit)
				overObject.style.cursor = "e-resize";
				startmove = false;
				endmove = true;
			} else if ( dragType == "3" ) { 									// task not started : both start date and end dates can be moved
				overObject.style.cursor = "move";
				startmove = true;
				endmove = true;
				}

			}
		return true;
		}
}

function mouseUp(){
/*
*	Process end of move (mouse Up)
*/
	overObject = null;
	if ( dragObject ) {
		var obj = dragObject;
		// release object from mouse move
		dragObject = null;
		// Restore cursor shape to default
		obj.style.cursor = "auto";
		// Hide start/end dates display
		document.getElementById("startSpan").style.display="none";
		document.getElementById("endSpan").style.display="none";
		// Indicate that the bar has been moved
		barArray[obj.id].moved = true;
		// Update all parent bar positions if required
		updateDeepParent( obj );
		// Delete dependency limit lines
		if ( document.getElementById("depLimit") ) {
			obj.parentNode.removeChild(document.getElementById("depLimit"));
			}
		// Update dependency drawings
		updateDepDrawing(obj.id);
		}
}

function onMouseDownItem(ev) {
/*
*	Event handler for draggable object
*/
	ev	= ev || window.event;
	mousePos 	= mouseCoords(ev);

	// If the Alt key is press => create a new dependency
	if ( ev.altKey && overObject ) {
		createDepend(this);
		return;
	} else if ( depObject ) {		// if an object has been selected for new dependency => unselect this object
		depObject.style.backgroundColor="transparent";
		depObject = null;
	}

	// If right click (ctrlKey + left click for Opera)  => display task for update using taskDetails overlay
	if ( ( navigator.appName!='Opera' && ev.which == 3 ) || ( navigator.appName=='Opera' && ev.ctrlKey ) ) {
		var div = document.getElementById("taskDetails");
		// Position the overlay right under the task bar
		var xPos = parseInt(this.style.marginLeft);				// start bar position relative to bar container
		var end = xPos+parseInt(div.style.width);				// end bar position relative to bar container
		if ( end>parseInt(Xmax) ) {
			xPos = parseInt(Xmax)-parseInt(div.style.width);
			}
		xPos = xPos+parseInt(colWidth);							// add offset for label columns
		div.style.left=xPos+"px";
		var yPos = mousePos.y-getMouseOffset(this, ev).y+19;	// line under the current bar
		div.style.top=yPos+"px";
		// Display loading message and query the server
		displayLoading();
		xajax_displayTask(barArray[this.id].id);
		return false; }

	if ( !startmove && !endmove ) {				// the mouse is not located in position for dragging the current object
		return;
	}
	overObject	= null;
	depObject	= null;
	dragObject  = this;

	// position and make visible the generated overlay
	var iMargin	= dragObject.style.marginLeft;
	var iWidth	= dragObject.style.width;
	var option = 0;
	if ( startmove ) { option = 1; }
	if ( endmove && dragType < "4" ) { option += 2; }
	var yPos = mousePos.y-getMouseOffset(dragObject, ev).y+19;
	displayOverDates( iMargin, iWidth, yPos, option );

	// Populate undo stack
	undoStack.push( new Array( "moveBar", new Array( dragObject.id, iMargin, iWidth, barArray[dragObject.id].moved )));
	document.getElementById("undoBtn").style.visibility="visible";
	
	// Delete dependency graphics and calculate dependency limit abscisses
	var maxReq = -1;	
	for ( var i=0; i<barArray[dragObject.id].req.length ; i++ ) {	// Browse the required task array
		var req = depArray[barArray[dragObject.id].req[i]];
		req.obj.clear();											// Delete dependency drawing
		var reqBar = document.getElementById("bar_"+req.req);
		var x = parseInt(reqBar.style.marginLeft);					// Determine bar end abscisse
		if ( barArray["bar_"+req.req].dragType < "4" ) {			// Special case for milestones
			x += parseInt(reqBar.style.width);
			}
		if  ( x > maxReq ) { maxReq = x; }							// Evaluate required task end highest abscisse
	}
	var minDep = parseInt(Xmax)+1;
	for ( var i=0; i<barArray[dragObject.id].dep.length ; i++ ) {	// Browse the dependent task array
		var dep = depArray[barArray[dragObject.id].dep[i]];
		dep.obj.clear();											// Delete dependency drawing
		var x = parseInt(document.getElementById("bar_"+dep.dep).style.marginLeft);
		if ( x < minDep ) { minDep = x; }							// evaluate dependent task start lowest abscisse
	}
	// Now display max required and min dependent limits
	displayConstrain( dragObject, maxReq, minDep); 
	return false;
}

function onMouseOverItem(ev) {
	if ( !dragObject ) {
		overObject	= this;
		dragType	= barArray[this.id].dragType;
		}
	return;
}

function onMouseOutItem(ev) {
	if ( !dragObject ) {
		overObject	= null;
		dragType	= null;
		this.style.cursor = "auto";
		}
	return;
}

/**************************** MOUSE AND CURSOR POSITION DETERMINATION **********************************/

function mouseCoords(ev){
/*
*	Get mouse coordinates
*		@param event
*		@return (x,y)
*/
	if(ev.pageX || ev.pageY){
		return {x:ev.pageX, y:ev.pageY};
	}
	return {
		x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
		y:ev.clientY + document.body.scrollTop  - document.body.clientTop
	};
}

function getMouseOffset(target, ev){
/*
*	Get offset between target bar position and mouse position
*		@param	bar object ID
*		@param	event
*		@return	(offsetX, offsetY)
*/
	ev = ev || window.event;
	var docPos    = getPosition(target);
	mousePos  = mouseCoords(ev);
	return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
}

function getPosition(element){
/*
*	Get position of a bar object relative to parent
*		@param	bar object ID
*		@return	(positionX, positionY)
*/
	var left = 0;
	var top  = 0;
	while (element.offsetParent){
		left	+= element.offsetLeft;
		top		+= element.offsetTop;
		element	= element.offsetParent;
	}
	left += element.offsetLeft;
	top  += element.offsetTop;
	return {x:left, y:top};
}

/**************************** GANTT BAR PROCESSING **********************************/

function CbarGraph( id, row, parent, dragType, level, collapse ) {
/*
*	bar graph object constructor
*		@param	bar ID (in the form "bar_"+task_ID)
*		@param	row number in the graph
*		@param	parent task ID or 0 if no parent
*		@param	-1 no bar displayed; 0 = no drag; 1= drag start only; 2=drag end only;3=drag both start and end;4=milestone (drag as block)
*		@param	level in the task tree
*		@param	0 = not a dynamic task; 1 = dynamic task expanded; 2 = dynamic task collapsed
*/
	this.id			= id;
	this.row		= row;
	this.parent		= parent;
	this.dragType	= dragType;
	this.level		= level;
	this.collapse	= collapse;
	this.display	= "table-row";		// Current style display option
	this.req		= new Array;		// Array of required task
	this.dep		= new Array;		// Arry of dependent tasks
	this.moved		= false;			// flag set if moved
}

function createBar(item, level, row, type, id, parent) {
/*
*	Create a new bar graph
*		@param	bar Graph JS object ID from getElementById()
*		@param	level in task hierarchy
*		@param	row number in the graph
*		@param	bar graph type (milestone, parent, activity)
*		@param	bar task ID
*		@param	bar graph parent ID (task parent ID)
*/
	if(!item) return;
	if ( type == "8" ) {
		var collapse = 1;
		type = 0;
	} else {
		var collapse = 0;
	}
	barArray[item.id] = new CbarGraph( id, row, parent, type, level, collapse);
	barIndex[parseInt(barNum)] = item.id;
	barNum = parseInt(barNum)+1;
	return;
}

function updateDeepParent( bar ) {
/*
*	Update all parent hierarchy start/end dates
*		@param	child bar HTML object
*/
	var loop = true;
	while ( loop ) {
		var parent=updateParent(bar);
		if ( parent ) {
			if ( barArray[parent.id].dragType >= 0 ) {
				barArray[parent.id].moved = true;
				}
			bar = parent;
		} else {
			loop = false;
		}
	}
}

function updateParent(item) {
/*
*	Update parent bar start/end date after bar move
*		@param	child bar HTML object
*		@return	false if no parent, parent HTML object otherwise
*/
// Retrieve parent ID
	var pid = barArray[item.id].parent;
	if ( pid == 0 ) {		// No parent : nothing to update
	return false;
		}
// Retrieve current margin and width for parent and initialize loop on all children
	var parent		= document.getElementById("bar_"+pid);
	var pmarginLeft	= parseInt(parent.style.marginLeft);
	var pwidth		= parseInt(parent.style.width);
	var pend		= pmarginLeft+pwidth;
	var itemend		= parseInt(item.style.marginLeft)+parseInt(item.style.width);
	var newmargin	= itemend;
	var newend		= 0;
// Loop on all children
	for ( var i=0; i<barNum; i++ ) {
		var row = barArray[barIndex[i]];
		if ( row.parent == pid ) {
			var bar = document.getElementById(barIndex[i]);
			cmargin = parseInt(bar.style.marginLeft);
			if ( cmargin < newmargin ) {	// The child bar starts before previous child bars => update margin
				newmargin = cmargin ;
			}
			cend = cmargin;
			var type = parseInt(row.dragType);
			if ( type >= 0 && type < 4 ) {
				cend += parseInt(bar.style.width);
			}
			if ( cend > newend ) {			// The child bar ends after previous child bars => update bar end
				newend = cend ;
			}
		}
	}
// Update parent maginLeft and width 
	var newwidth = newend - newmargin;
	parent.style.marginLeft = newmargin+"px";
	parent.style.width = newwidth+"px";
	return parent;
}

/**************************** EXPAND/COLLAPSE PROCESSING **********************************/

function expand( id ) {
/*
*	Process the expand icon for a given task
*		@param	parent task ID
*/
	var i = 0;
	var iMax = parseInt(barNum);
	while ( i<iMax && barArray[barIndex[i]].parent != id ) {
		i++;
	}
	if ( i < iMax ) {
		displayDeepChildren(i, id, "table-row");
		barArray["bar_"+id].collapse = 1;
		} else { alert(id+" not found"); }
	document.getElementById("expand_"+id).style.display="none";
	document.getElementById("collapse_"+id).style.display="inline";
}

function collapse( id ) {
/*
*	Process the collapse icon for a given task
*		@param	parent task ID
*/
	var i = 0;
	var iMax = parseInt(barNum);
	while ( i<iMax && barArray[barIndex[i]].parent != id ) {
		i++;
	}
	if ( i < iMax ) {
		displayDeepChildren(i, id, "none");
		barArray["bar_"+id].collapse = 2;
	}
	document.getElementById("expand_"+id).style.display="inline";
	document.getElementById("collapse_"+id).style.display="none";
}

function displayDeepChildren( index, parent, option ) {
/*
*	Change display option for all children of a given task
*		@param	index of the task in barArray
*		@param	task ID of the parent task
*		@param	display option to be applied
*/
	var parentStack = new Array;
	var childArray = new Array;
	parentStack.push(parent);
	var j=index;
	while ( parentStack.length > 0 && j<barNum ) {
		parent = parentStack[parentStack.length-1];
		var barName = barIndex[j];
		if ( barArray[barName].parent == parent ) {
			// Show/hide the child bar
			document.getElementById("tr_"+barArray[barName].id).style.display=option;
			barArray[barName].display=option;
			childArray.push(barName);
			// if the child is a parent and if this parent is expanded then add to the parent stack
			if ( barArray[barName].collapse > 0 && option == "table-row" ) {	// option is show and the child bar is a parent
				if ( barArray[barName].collapse == 1 ) {						// if the children are displayed then hide
					parentStack.push(barArray[barName].id);
					j++;
				} else {														// skip all its children (they have been previously hidden)
					var level = barArray[barName].level;
					j++;
					while ( j<barNum && barArray[barIndex[j]].level > level ) {
						j++;
					}
				}
			} else {															// Normal case
				parentStack.push(barArray[barName].id);
				j++;
			}
		} else {
			parentStack.pop();
		}
	}
// Now update all dependency drawings according to current bar display
	for ( var i=0; i<depNum; i++ ) {
		// Display dependency drawing if both required and dependent tasks are displayed
		if ( depArray[i].div && barArray["bar_"+depArray[i].req].display != "none" && barArray["bar_"+depArray[i].dep].display != "none" ) {
			var item = drawDependency( depArray[i] );
		} else {	// Hide (delete) the dependency drawing if exists
			if ( depArray[i].obj ) {
				depArray[i].obj.clear();
				}
		}
	}
	return;
}


/**************************** DEPENDENCY PROCESSING **********************************/

function CdepGraph( req, dep ) {
/*
*	Dependency object constructor
*		@param	Required task ID
*		@param	Dependent task ID
*/
	this.req = req;
	this.dep = dep;
	this.div = null;		// Drawing container DIV name
	this.obj = null;		// Jsgraphics object
	this.added = false;		// flag = true if new dependency
	this.line = null;		// x, y arrays generated by drawDependency
}

function setDependent( req, dep, added ) {
/*
*	Create a new dependency
*		@param	Required task ID
*		@param	Dependent task ID
*		@param	User defined dependency if set
*/
	depArray[depNum] = new CdepGraph( req, dep);
	var div = drawDependency( depArray[depNum] );
	if ( div ) {
		depArray[depNum].div = div;
		depArray[depNum].added = added;
		barArray["bar_"+req].dep.push(depNum);
		barArray["bar_"+dep].req.push(depNum);
		var added = false;
//		div.onClick = clearDependent;			// NE FONCTIONNE PAS
		}
	depNum = parseInt(depNum)+1;
}

function clearDependent(ev) {		// NE FONCTIONNE PAS
	// If the Alt key is press => delete the dependency
	ev	= ev || window.event;
	if ( !ev.altKey ) {
		return;
		}

	if ( confirm("<?php echo $AppUI->_('doDelete'); ?>") ) {
		var i=0;
		var iMax = parseInt(depNum);
		while ( i<iMax && depArray[i].div != this.id  ) {
			i++;
		}
		if ( i >= depNum ) {
			alert("Error");
			return;
			}
	}
}
function updateDepDrawing( barName ) {
/*
*	Update dependency drawing after Gantt bar move
*		@param	Name  of the moved bar
*/
	// Update required task dependencies
	for ( var i=0; i<barArray[barName].req.length; i++ ) {
		// update dependency drawing if the required bar are displayed
		var depend = depArray[barArray[barName].req[i]];
		if ( depend.div && barArray["bar_"+depend.req].display != "none" ) {
			var item = drawDependency( depend );
			}
	}
	// Update dependent task dependencies
	for ( var i=0; i<barArray[barName].dep.length; i++ ) {
		// Update dependency drawing if the dependent bar are displayed
		var depend = depArray[barArray[barName].dep[i]];
		if ( depend.div && barArray["bar_"+depend.dep].display != "none" ) {
			var item = drawDependency( depend );
		}
	}
}

function drawDependency( depObj ) {
/*
*	Draw a dependency line
*		@param	dependency object to be displayed
*/
	var satisfied = true;
	var reqName = "bar_"+depObj.req;
	if ( barArray[reqName].dragType < 0 ) { return false ; }
	var depName = "bar_"+depObj.dep;
	if ( barArray[depName].dragType < 0 ) { return false ; }
	var divName = "dep_"+depObj.req+"_"+depObj.dep;
// calculate start point in abscisse 
	var startBar = document.getElementById(reqName);
	var xstart = parseInt(startBar.style.marginLeft)+parseInt(startBar.style.width)+1; // +1 stands for the border width
	if ( xstart >= Xmax ) {		// the bar ends after the right limit of the graph
		return false;
		}
// Calculate end point in abscisse
	var endBar = document.getElementById(depName);
	var xend = parseInt(endBar.style.marginLeft);
	if ( xend <= 0 ) {			// the bar starts before the left limit or ends after the right limit of the graph
		return false;
		}
	if ( depObj.obj ) {
	// Retrieve dependency container
		var depGraph = depObj.obj;
		depGraph.clear();
		var div = document.getElementById(divName);
	} else {
	// Create dependency graph container
		var div = document.createElement("div");
		div.name = divName;
		div.id = divName;
		div.style.position = "relative";
		div.style.marginTop = "-5px";
		div.style.zIndex = 50;
		document.getElementById(reqName).parentNode.appendChild(div);
		var depGraph = new jsGraphics(div.id);
		depObj.obj = depGraph;
		}

// Calculate heigth between req and dep bars (taking into account hidden activities)
	var rowDiff = 0 ;
	if ( parseInt(barArray[depName].row) > parseInt(barArray[reqName].row) ) {
		var sign = 1;
		var indMin = parseInt(barArray[reqName].row);
		var indMax = parseInt(barArray[depName].row);
	} else {
		var sign = -1;
		var indMin = parseInt(barArray[depName].row);
		var indMax = parseInt(barArray[reqName].row);
	}
	for ( var i=indMin ; i<indMax; i++ ) {					// Count the number of visible rows between the two activities
		if ( barArray[barIndex[i]].display != "none" ) {
			rowDiff++;
		}
	}
	var height = sign*parseInt(rowDiff)*21;					// height of the dependency drawing

// calculate length of horizontale line
	var width = xstart+5;
	width = xend-10-width; // 2 times a 5 px horizontal line
	if ( barArray[depName].dragType >= "4" ) {
		width = width-5;
	}
// Check dependency  constraint
	var barstart = xstart-1;
	if  ( barArray[reqName].dragType >= "4" ) {
		barstart = barstart-5;
		}
	if ( barstart > xend ) {
		satisfied = false;
		}
// Calculate polyline coordinates
	var x = new Array;
	var y = new Array;
	var deltay = 0;
// horizontal line width 5px from end of req bar
	var posx = xstart; var posy = 0;
	x.push(posx); y.push(posy);
	if ( width > 0 ) {
	// Draw the horizontal leg from req bar end
		posx = posx+width+5;
		x.push(posx); y.push(posy);
	} else {
	// Draw a 5px horizontal line from req bar end
		posx = posx+5;
		x.push(posx); y.push(posy);
	//Draw a vertical bar half the height
		deltay = Math.floor((parseInt(rowDiff))/2)*21+11;
		posy = posy+deltay;
		x.push(posx); y.push(posy);
	// Draw a full  horizontal line 
		posx=posx+width;
		x.push(posx); y.push(posy);
		}
// draw a vertical line to the middle of dep bar height
	posy = posy+height-deltay;
	x.push(posx); y.push(posy);
// draw an horizontal line to dep bar start
	posx = posx+6;
	x.push(posx); y.push(posy);
// Draw graphics
	if ( satisfied ) {
		depGraph.setColor("black");
		var image = "black_arrow.gif";
	} else {
		div.style.zIndex = 75;
		depGraph.setColor("red");
		var image = "red_arrow.gif";
		}	
	depGraph.drawPolyline(x, y);
	depGraph.drawImage("./modules/igantt/images/"+image, posx, posy-2, 4, 5);
	depGraph.paint();
//	depGraph.onClick= "javascript:clearDependent();";		//NE FONCTIONNE PAS
	document.getElementById(divName).style.display = "block";
	return divName;
}

function createDepend( obj ) {
/*
*	Create a new dependency between two tasks
*		First call : set the required task
*		Second call in a row : set the dependent task and create dependency
*/
	if ( depObject && depObject != obj ) {					// Second call in a row
		setDependent( barArray[depObject.id].id, barArray[obj.id].id, 1 );
		undoStack.push( new Array( "addDep", new Array( barArray[depObject.id].id, barArray[obj.id].id )));
		document.getElementById("undoBtn").style.visibility="visible";
		depObject.style.backgroundColor="transparent";
		depObject = null;
	} else {												// First call
		obj.style.backgroundColor="red";
		depObject = obj;
	}
}
/**************************** UTILITY FUNCTIONS FOR BAR DRAGGING **********************************/

function setLimit( leftStart, startDate, rightEnd, endDate) {
/*
*	Create scale cell limit array
*		@param	abscisse of cell left limit
*		@param	unixtime of cell left limit
*		@param	abscisse of cell right limit
*		@param	unixtime of cell right limit
*/
	cellArr[cellNum]			= new Array;
	cellArr[cellNum]["sdate"]	= startDate;
	cellArr[cellNum]["leftx"]	= leftStart;
	cellArr[cellNum]["edate"]	= endDate;
	cellArr[cellNum]["rightx"]	= rightEnd;
	cellNum = parseInt(cellNum)+1;
	return;
}

function getBarDate( posX, format ) {
/*
*	Convert an abscisse into date
*		@param	abscisse to be converted to date
*		@param	date format of result
*/
	var i=0;
	var imin = 0;
	var imax = cellArr.length-1;
	var x = parseInt(posX);
// Search the corresponding index in limit array by dichotomy
	while ( imin < imax ) {
		i = imin + Math.round((imax-imin)/2);
		if ( parseInt(cellArr[i]["rightx"]) >= x ) {
			imax = i ;
			if ( parseInt(cellArr[i]["leftx"]) <= x ) {
				imin = i ;
			} else {
				imax--;
			}
		} else {
			imin = i;
		}
	}
	i = imin ;
// Use interpolation to generate the unixtime
	var sdate = parseInt(cellArr[i]["sdate"]);
	var edate = parseInt(cellArr[i]["edate"]);
	var leftx = parseInt(cellArr[i]["leftx"]);
	var rightx = parseInt(cellArr[i]["rightx"]);
	var unixTime = Math.round(1000*(sdate+((edate-sdate)*(x-leftx)/(rightx-leftx))));
	var barDate = new Date(unixTime);
// Round to day start and day end hours
	var hours = barDate.getHours();
	if ( parseInt(hours) >= parseInt(dayEnd) ) {
		barDate.setHours(dayEnd);
		barDate.setMinutes("0");
	} else if ( parseInt(hours) < parseInt(dayStart) ) {
		barDate.setHours(dayStart);
		barDate.setMinutes("0");
	} else {
// Round minutes to calendar increment
		var minutes = barDate.getMinutes();
		var nbInc = Math.round(parseInt(minutes)/parseInt(hourInc));
		minutes = parseInt(nbInc)*parseInt(hourInc);
		if ( parseInt(minutes) >= 60 ) {
			hours = parseInt(hours)+1;
			minutes = 0;
		}
		barDate.setHours(hours);
		barDate.setMinutes(minutes);
		}
// Return formatted date
	return dateToFormat( barDate, format ) ;
}

function displayOverDates( marginL, width, yPos, showLR ) {
/*
*	Display overDates div to show current task start/end dates when dragging
*		@param	current drag object margin left
*		@param	current drag object width
*		@param	vertical absolute position
*		@param	1 = show start date; 2 = show end date; 3 = show both start and end dates
*/
	var spanLength = 120;
	var showLeftSpan = false;
	var showRightSpan = false;
	var overDates = document.getElementById("overDates");
	var startSpan = document.getElementById("startSpan");
	var endSpan = document.getElementById("endSpan")

// Position vertically and display spans if arguments ar present
	if ( displayOverDates.arguments.length > 2 ) {
		overDates.style.top = yPos+"px";
		if ( showLR == 1 || showLR == 3 ) {
			startSpan.style.display="inline";
			showLeftSpan = true;
			}
		if ( showLR >= 2 ) {
			endSpan.style.display="inline";
			showRightSpan = true;
			}
	// Determine which dates are to be displayed
	} else {
		if ( startSpan.style.display == "inline" ) {
			showLeftSpan = true;
			}
		if ( endSpan.style.display == "inline" ) {
			showRightSpan = true;
			}
	}

	//  Determine standard position
	var colW = parseInt(colWidth);
	var max = parseInt(Xmax);
	var m = parseInt(marginL);
	var w = parseInt(width);
	if ( showLeftSpan && showRightSpan ) {	// Both start and end dates are displayed => display spans at both ends of the moved bar
		var ODmargin = m-spanLength;		// overDates margin left
		var ODend = m+w+spanLength+4;		// overDates end (including border width)
		var interSpan = w;					// distance between spans
	} else {								// Only one span is displayed => center the display
		if ( showLeftSpan ) {
			var ODmargin = m;
		} else {
			var ODmargin = m+w;
		}
		ODend = ODmargin+Math.round(spanLength/2);
		var ODmargin = ODend-spanLength;
		var interSpan = 0;
	}
	
	// Adapt standard position to take into account graph limits
	if ( ODend > max ) {					// if overDates ends over graph right limit
		if ( interSpan > 0 ) {				// Reduce distance between spans
			interSpan = interSpan+max-ODend;
			if ( interSpan < 11 ) {			// but keep a minimum distance
				interSpan = 11 ;
				}
			ODend = m+interSpan+spanLength;
		}
		ODmargin = ODmargin-(ODend-max);	// move all overDates to the right if required
		ODend = max;
	}
	if ( ODmargin < 0 ) {					// overDates starts before graph left limit
		if ( interSpan > 0 ) {				// Reduce distance between spans
			interSpan = interSpan+ODmargin;
			if ( interSpan < 11 ) {			// but keep a minimum distance
				interSpan = 11;
				}
			ODend = interSpan+2*spanLength;
		} else {
			ODend = spanLength;
		}
		ODmargin = 0;
	}
// Set overlay margin left and width (taking into account label table width because overlay position is absolute)
	overDates.style.marginLeft=(colW+ODmargin)+"px";
	overDates.style.width=(ODend-ODmargin)+"px";

// Update displayed dates 
	if ( showLeftSpan ) {
		document.getElementById("barStart").innerHTML=getBarDate(m, dateFormat+" "+hourFormat);
		}
	if ( showRightSpan ) {
		document.getElementById("barEnd").innerHTML=getBarDate(m+w, dateFormat+" "+hourFormat);
		}
}

function displayConstrain( obj, left, right ) {
/*
*	Display a horizontal line to show dependency limits
*		@param	object id of the container
*		@param	"left" or "right"
*		@param	width of the limit
*		@param	select red color if set otherwise black
*/
// Create a DIV as container
	left = parseInt(left);
	right = parseInt(right);
	var div = document.createElement("div");
	div.id					= "depLimit";
	div.style.marginTop		= "-21px";
	div.style.height		= "21px";
	div.style.width			= Xmax+"px";
	div.style.position		= "relative";
	obj.parentNode.insertBefore(div, obj);
// Draw graphics
	draw = new jsGraphics(div.id);
	draw.setColor("#C0C0C0");
	draw.setStroke(3);
	if ( parseInt(left) >= 0 ) {
		draw.drawLine( 0, 0, left-1, 0);
		draw.setStroke(0);
		draw.drawImage("./modules/igantt/images/left_limit.gif", left-1, 3, 3, 3);
		}
	if ( right <= parseInt(Xmax) ) {
		draw.setStroke(3);
		draw.drawLine(right, 18, Xmax, 18);
		draw.setStroke(0);
		draw.drawImage("./modules/igantt/images/right_limit.gif", right, 15, 3, 3);
	}
	draw.paint();
}

function undoAction() {
	var elt = undoStack.pop();
	var action = elt[0];
	var data = elt[1];
	switch (action) {
		case "moveBar" :
			var item = document.getElementById(data[0]);
			if ( !item ) return false;
			// restore task bar position
			item.style.marginLeft = data[1];
			item.style.width = data[2];
			barArray[data[0]].moved = data[3];
			var bar = item;
			// Update parent bar position
			updateDeepParent( item );
			// Update dependency drawings
			var div = updateDepDrawing(item.id);
			break;
		case "addDep" :
			var i = depNum-1;
			// Retrieve the dependendy to delete in the dependency array
			while ( i>=0 && depArray[i].req != data[0] && depArray[i].dep != data[1] ) {
				i--;
			}
			if ( i<0 ) { break; }		// Not found : do nothing
			var req = depArray[i].req;
			var dep = depArray[i].dep;
			var item = depArray[i].div;
			// delete the dependency div container
			document.getElementById(item).parentNode.removeChild(document.getElementById(item));
			// Now delete the entry in depArray (only zero the entries because we need to maintain the array indexes)
			depArray[i].div = null;
			depArray[i].req = 0;
			depArray[i].dep = 0;
			// Now update required and dependent bar description
			var barName = "bar_"+req;							// Process the required task description
			var j = barArray[barName].req.length - 1;
			while ( j>=0 && barArray[barName].req[j] != i ) {	// Retrieve the dependency in the list of required tasks
				j--;
			}
			if ( j>= 0 ) {										// If found, delete the entry and pack the array
				for ( var k=0; k<barArray[barName].req.length-1; k++ ) {
					barArray[barName].req[k] = barArray[barName].req[k+1];
				}
				barArray[barName].req.pop();
			}
			var barName = "bar_"+dep;							// Process the dep array (same processing as req - could be a function)
			var j = barArray[barName].dep.length - 1;
			while ( j>=0 && barArray[barName].dep[j] != i ) {
				j--;
			}
			if ( j>= 0 ) {
				for ( var k=0; k<barArray[barName].dep.length-1; k++ ) {
					barArray[barName].dep[k] = barArray[barName].dep[k+1];
				}
				barArray[barName].dep.pop();
			}
			break;
		default : break;
		}
	if ( undoStack.length == 0 ) { document.getElementById("undoBtn").style.visibility="hidden"; }
	return true;
}

/**************************** TASK START/END DATE UPDATE **********************************/

function updateTasks() {
/*
*	Update the updateTasks form in the opener window to prepare task updates
*		bulk_task_id		comma separated list of moved task ID's
*		bulk_task_start_date	comma separated list of updated task start dates or "zero" if not moved
*		bulk_task_end_date	comma separated list of task end dates or "zero" if not moved
*		bulk_dependencies	semicolon separated list of comma separated task ID's( required task, dependent task)
*/
	var id_list = new Array;
	var start_list = new Array;
	var end_list = new Array;
	var depend_list = new Array;
//	var j=0;
//  Prepare task end/start date update array
	for ( var i=0; i<barNum; i++ ) {
		barName = barIndex[i];
		if ( barArray[barName].moved && barArray[barName].dragType >= 0 ) {
			taskId = barArray[barName].id;
			bar = document.getElementById("bar_"+taskId);
			var type = barArray[barName].dragType;
			start = parseInt(bar.style.marginLeft);
			if ( type == "1" || type == "3" || type == "7" ) {
				sdate = getBarDate( start, "%Y-%m-%d %H:%M:%S" );
			} else {
				sdate = "0000-00-00 00:00:00";
			}
			if ( type == "2" || type == "3" ) {
				end   = start+parseInt(bar.style.width);
				edate = getBarDate( end, "%Y-%m-%d %H:%M:%S" );
			} else if ( type >= "4" ) {
				edate = sdate;
			} else {
				edate = "0000-00-00 00:00:00";
			}
			id_list.push(taskId);
			start_list.push(sdate);
			end_list.push(edate);
			}
		}
	window.opener.document.updateTasks.bulk_task_id.value = id_list.join(",");
	window.opener.document.updateTasks.bulk_task_start_date.value = start_list.join(",");
	window.opener.document.updateTasks.bulk_task_end_date.value = end_list.join(",");
// Now process new dependencies
	for ( var i=0; i<depNum; i++ ) {
		if ( depArray[i].div && depArray[i].added == "1" ) {
			depend_list.push( depArray[i].req+","+depArray[i].dep );
		}
	}
// Format the resulting arrays
	window.opener.document.updateTasks.bulk_dependencies.value = depend_list.join(";");
	window.opener.document.updateTasks.submit();
	window.close();
}

/**************************** UTILITY FUNCTIONS FOR DISPLAY **********************************/

var char_index = 0;
function displayLoading() {
/*
*	Display the loading message
*		Called on timeout while loading to refresh display (moving dots)
*/
	if ( !char_index ) { char_index=0; }
	var alert_dot='......';
	if (char_index>6) char_index=0;
	var txt=alert_dot.substring(0,char_index)+" "+alert_dot.substring(char_index);
	document.getElementById("movingDots").innerHTML=txt;
	document.getElementById("loadingMessage").style.visibility="visible";
	char_index++;
	loadingTimeout=window.setTimeout("displayLoading()", 200);
}

function hideLoading() {
/*
*	Hide loading message and stop message refresh
*/
	window.clearTimeout(loadingTimeout);
	document.getElementById("loadingMessage").style.visibility="hidden";
}

function setSelectOptionIndex( sel, val, disabled ) {
/*
*	set the SelectedIndex in select sel to the option valued val
*		@param	select element
*		@param	option value
*		@param	disabled value
*/
	sel.selectedIndex = 0;
	for ( var i=0; i<sel.options.length; i++ ) {
		if ( sel.options[i].value == val ) {
			sel.selectedIndex = i;
			break;
			}
		}
	sel.disabled = disabled;
}

function setAMPM(field) {
	ampm_field = document.getElementById(field.name + "_ampm");
	if (ampm_field) {
		if (field.options[field.selectedIndex].value > 11){
			ampm_field.value = "pm";
		} else {
			ampm_field.value = "am";
		}
	}
}

/**************************** UTILITY FUNCTIONS FOR DATE MANAGEMENT **********************************/
/*
*	Calendar functions and date setting
*/
var calendarField = "";
function popCalendar(field) {
	calendarField = field;
	idate = field.value;
	window.open("index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=" + idate, 
				"calwin", "width=250, height=220, scrollbars=no, status=no");
}

function setCalendar(idate, fdate) {
	calendarField.value = idate;
	document.getElementById( "show_"+calendarField.name ).value = fdate;
}

function scrollPrev() {
/*
*	Move Gantt start and end dates to previous month
*/
	var f = document.optionFrm;
	var date = f.sdate.value;
	f.sdate.value = scrollDate( date, -1 );
	var date = f.edate.value;
	f.edate.value = scrollDate( date, -1);
	displayLoading();
	xajax_drawGraph( xajax.getFormValues("optionFrm"));
}

function scrollNext() {
/*
*	Move Gantt start and end dates to next month
*/
	f = document.optionFrm;
	var date = f.sdate.value;
	f.sdate.value = scrollDate( date, 1 );
	var date = f.edate.value;
	f.edate.value = scrollDate( date, 1);
	displayLoading();
	xajax_drawGraph( xajax.getFormValues("optionFrm"));
}

function scrollDate( date, value ) {
/*
*	Utility function move date by value month (value is negative if previous month)
*		@param	date in MySQL date format
*		@param	number of months (+/-) to scroll
*		@return	new date in MySQL format
*/
	var year = parseInt(date.substring(0, 4));
	var month = 10*parseInt(date.substring(4, 5))+parseInt(date.substring(5,6));
	var day = 10*parseInt(date.substring(6, 7))+parseInt(date.substring(7, 8));
	month = month+parseInt(value);
	if ( month > 12 ) {
		month = month-12;
		year = year+1;
	} else if ( month < 1 ) {
		month = month+12;
		year = year-1;
	}
// Account for bissextile year
	if ( month == 2 ) {
		if ( year%4 == 0 && year%100 != 0 || year%400 == 0) {
			var maxDay = 29;
		} else {
			var maxDay = 28;
		}
		if ( parseInt(day) > parseInt(maxDay) ) {
			day = parseInt(day) - parseInt(maxDay);
			month = month+1;
		}
	}
	month = month-1;
// Now format the resulting date
	var returnDate = new Date();
	returnDate.setDate(day);
	returnDate.setMonth(month);
	returnDate.setFullYear(year);
	return dateToFormat( returnDate, "%Y%m%d");
}

function showThisMonth() {
/*
*	Set graph date limits to current month first day and last day
*/
	f = document.optionFrm;
	var today = new Date();
	var thisMonth = today.getMonth();
// Calculate unix time for 1st of current month start of day in milliseconds
	var startMonth = new Date();
	startMonth.setDate(1);
	startMonth.setHours(dayStart);
	startMonth.setMinutes(0);
// Format start date to MySQL time stamp
	f.sdate.value = dateToFormat( startMonth, "%Y%m%d");
// Calculate unix time for last day of month end of day in milliseconds
	var endMonth = new Date();
	thisMonth = parseInt(thisMonth)+1;
// Calculate next month
	if ( thisMonth > 11 ) {
		thisMonth = 0;
		var thisYear = today.getFullYear();
		thisYear = parseInt(thisYear)+1;
		thisDate.setFullYear(thisYear);
		}
	endMonth.setDate(1);
	endMonth.setMonth(thisMonth);
	endMonth.setHours(dayEnd);
	endMonth.setTime(endMonth.getTime()-86400000); // Substract one day in milliseconds to 1st day of next month
// Format end date to MySQL time stamp
	f.edate.value = dateToFormat(endMonth, "%Y%m%d");
	displayLoading();
	xajax_drawGraph( xajax.getFormValues("optionFrm"));
}

function showFullProject() {
/*
*	Set graph date limits to null (interpreted as display full project at the server side)
*/
	document.optionFrm.sdate.value = null;
	document.optionFrm.edate.value = null;
	displayLoading();
	xajax_drawGraph( xajax.getFormValues("optionFrm"));
}

function dateToFormat( date, format ) {
/*
*	This function format a date object using standard PHP date format string
*		date format indicators are restricted to  %y %Y %m %d %H %M ans %S
*		@param	the date in unixtime
*		@param	date format (PHP like)
*/
	var out = format;
	var i = out.indexOf("%");
	var df = "";
	var end = "";
	while ( i >= 0 ) {
		// Get the format indicator
		df = out.substring(i, i+2);
		end = out.substring(i+2);
		switch ( df ) {
			case "%Y" :	var  str = date.getFullYear();
						break;
			case "%y" :	var str = date.getYear();
						break;
			case "%m" :	var str = 1+date.getMonth();
						break;
			case "%d" :	var str = date.getDate();
						break;
			case "%H" :	var str = date.getHours();
						break;
			case "%M" :	var str = date.getMinutes();
						break;
			case "%S" :	var str = "";		// Do not show seconds
						break;
			default :	var str = "**";		// unknown format
						break;
			}
		if ( parseInt(str) < 10 ) { str = "0"+str; } 	// Display non significative zero
		out = out.substring( 0, i)+str+end;				// Replace the format indicator by the computed value
		i = out.indexOf("%");							// Seek index of the next format indicator
	}
	return out;		
}
