/* RESOURCE_M resource_m.js, v 0.2.0 2012/07/19 */
/*
* Copyright (c) 2011-2012 Region Poitou-Charentes (France)
*
* Description:	Js function page of the Resource Management module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
* version 0.1.2
* 	Optimize the getAssignment function.
* version 0.1.2.2
*	Fix the 1 hour tasks bug.
* version 0.2.0
*	Fix multi-level dynamic tasks
*	Re-Expand lines at any time
*	Merge completeTds scripts
*	Add mergeTd function which change the display way
*/

/* Set the <td></td> values for all lines.
** @param	int		scale
** @param	string	title_string
*/ 
function completeTd(scale, title_string)
{
	var end = true;
	var sum;
	var wait;
	var buffR;
	var buffG;
	var buffB;
	// reverse in order to begin with children
	$($(".todo").get().reverse()).each(
		function(i){
			sum = 0;
			wait = false;
			$(".child-of-"+this.parentNode.id+this.getAttribute('rel')).each(
				function (j){
					if($(this).hasClass("todo")){ wait = true; return;}
					else if (!isNaN(parseFloat(this.innerHTML)))
						sum += parseFloat(this.innerHTML);
				}
			);
			if(wait)
				end = false;
			else{
				buffR = Math.round(230 + (sum/scale)*(170-230));
				buffG = Math.round(238 + (sum/scale)*(221-238));
				buffB = Math.round(221 + (sum/scale)*(170-221));
			
				if (sum>0){
					this.innerHTML = Math.round(sum*4)/4;
					$('#convert').html(title_string);
					title_string = $('#convert').text();
					this.title = title_string;
					if (sum>scale)
						this.style.backgroundColor = '#CC6666';
					else
						this.style.backgroundColor = 'rgb('+buffR+','+buffG+','+buffB+')';
				}else{
					this.innerHTML = '';
					this.style.backgroundColor = '#ECECEC';
				}
				
				$(this).removeClass("todo");
			}
		}
	);
	if(!end) completeTd(scale, title_string);
}

/* Merge <td></td> of each line when they have same value.
*/ 
function mergeTd()
{
	var remove=0;
	$("table.treeTable > tbody > tr").each(
		function(i){
			$(this).children().each(
				function(j){
					if(remove)
						remove--;
					else{
						while(($(this).text() == $(this).next().text()) && $(this).next().length != 0){
							$(this).next().remove();
							remove++;
							$(this).attr('colspan', ($(this).attr('colspan')+1));
						}
					}
				}
			);
		}
	);
}

/* Submit the button's <form> with an additional input 
** that hold what <tr></tr> are currently expanded in order to re-expand them on next page.
** @param	Object	button
*/ 
function submitWithExpandList(button)
{
	var element = document.createElement('input');
	element.setAttribute('type', 'hidden');
	element.setAttribute('value', getExpanded());
	element.setAttribute('name', 'expandedList');
	button.form.appendChild(element);
	button.form.submit();
}

/* Set the timeout for calling the function that will display the details : dispUserDetails().
** @param	event	ev
** @param	string	company
** @param	string	department
*/ 
function displayUserDetails(ev, company, department)
{
	return setTimeout(function() {dispUserDetails(ev, company, department);}, 1500);
}

/* Set correct values and display the details for a user.
** @param	event	ev
** @param	string	company
** @param	string	department
*/ 
function dispUserDetails(ev, company, department)
{
	var div = document.getElementById("userDetails");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	document.getElementById("uCompany").innerHTML=company;
	document.getElementById("uDepartment").innerHTML=department;
	div.style.display = "block";
	
}

/* Clear timeout and hide user details.
** @param	timer	t
*/ 
function hideUserDetails(t)
{
	clearTimeout(t);
	var div = document.getElementById("userDetails");
	div.style.display = "none";
}

/* Set the timeout for calling the function that will display the details : dispProjectDetails().
** @param	event	ev
** @param	string	owner
** @param	string	sdate
** @param	string	edate
** @param	string	company
** @param	string	department
*/ 
function displayProjectDetails(ev, owner, sdate, edate, company, department)
{
	return setTimeout(function() {dispProjectDetails(ev, owner, sdate, edate, company, department);}, 1500);
}

/* Set correct values and display the details for a project.
** @param	event	ev
** @param	string	owner
** @param	string	sdate
** @param	string	edate
** @param	string	company
** @param	string	department
*/ 
function dispProjectDetails(ev, owner, sdate, edate, company, department)
{
	var div = document.getElementById("projectDetails");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	document.getElementById("pOwner").innerHTML=owner;
	document.getElementById("pSDate").innerHTML=sdate;
	document.getElementById("pEDate").innerHTML=edate;
	document.getElementById("pCompany").innerHTML=company;
	document.getElementById("pDepartment").innerHTML=department;
	div.style.display = "block";
	
}

/* Clear timeout and hide project details.
** @param	timer	t
*/ 
function hideProjectDetails(t)
{
	clearTimeout(t);
	var div = document.getElementById("projectDetails");
	div.style.display = "none";
}

/* Set the timeout for calling the function that will display the details : dispTaskDetails().
** @param	event	ev
** @param	string	owner
** @param	string	percent
** @param	string	sdate
** @param	string	edate
** @param	string	dyna
*/
function displayTaskDetails(ev, owner, percent, sdate, edate, dyna)
{
	return setTimeout(function() {dispTaskDetails(ev, owner, percent, sdate, edate, dyna);}, 1500);
}

/* Set correct values and display the details for a task.
** @param	event	ev
** @param	string	owner
** @param	string	percent
** @param	string	sdate
** @param	string	edate
** @param	string	dyna
*/ 
function dispTaskDetails(ev, owner, percent, sdate, edate, disp)
{
	var div = document.getElementById("taskDetails");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	if(disp)
		document.getElementById("trDyna").style.visibility="visible";
	else
		document.getElementById("trDyna").style.visibility="hidden";
	document.getElementById("tOwner").innerHTML=owner;
	document.getElementById("tPercent").innerHTML=percent;
	document.getElementById("tSDate").innerHTML=sdate;
	document.getElementById("tEDate").innerHTML=edate;
	div.style.display = "block";
	
}

/* Clear timeout and hide task details.
** @param	timer	t
*/ 
function hideTaskDetails(t)
{
	clearTimeout(t);
	var div = document.getElementById("taskDetails");
	div.style.display = "none";
}

/* Set correct values and display the edition of the assignment of a user to a task.
** @param	event	ev
** @param	string	user
** @param	string	task
** @param	string	project
** @param	int		taskid
** @param	int		userid
** @param	float	complete
** @param	string	sdate
** @param	string	edate
** @param	dloat	aPercent
** @param	timer	t
*/ 
function displayAssignEdit(ev, user, task, project, taskid, userid, complete, sdate, edate, aPercent, t)
{
	var div = document.getElementById("assignEdit");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	document.getElementById("aUser").innerHTML		= user;
	document.getElementById("aTask").innerHTML		= task;
	document.getElementById("aProject").innerHTML	= project;
	document.getElementById("aTId").value			= taskid;
	document.getElementById("aUId").value			= userid;
	document.getElementById("aComplete").innerHTML	= complete;
	document.getElementById("show_aSDate").value	= sdate;
	document.getElementById("show_aEDate").value	= edate;
	var start 										= sdate.substr(6)+sdate.substr(3,2)+sdate.substr(0,2);
	var end 										= edate.substr(6)+edate.substr(3,2)+edate.substr(0,2);
	document.getElementById("aSDate").value			= start;
	document.getElementById("aEDate").value			= end;
	document.getElementById("aPercent").value		= aPercent;
	div.style.display 								= "block";	
	document.getElementById('aSCal').style.display	= 'table-cell';
	document.getElementById('aECal').style.display	= 'table-cell';
	document.getElementById("aPercent").disabled	= false;
	document.getElementById('ad').style.display		= 'none';
	document.getElementById('aa').style.display		= 'none';
	hideTaskDetails(t);
}

/* Return a list of the expanded tr separate by ','
** @return	string	list
*/ 
function getExpanded() {
	var tmp											= document.getElementsByTagName('tr');
	var pattern 									= new RegExp("(^|\\s)expanded(\\s|$)");
	var list										= ""
	for(var i=0; i<tmp.length; i++) {
		if(pattern.test(tmp[i].className))
			list = list + tmp[i].id + ",";
	}
	return list;
}

/* Switch the display mode of edit Form
*/ 
function switchTypeAA()
{
	document.getElementById('aPercent').disabled=true;
	document.getElementById('aa').style.display='table-row';
	document.getElementById('editType').value='aa';
}

/* Switch the display mode of edit Form
*/ 
function switchTypeAD()
{
	document.getElementById('aSCal').style.display='none';
	document.getElementById('aECal').style.display='none';
	document.getElementById('ad').style.display='table-row';
	document.getElementById('editType').value='ad';
}

/* Switch the display mode of edit Form
*/ 
function switchTypeNull()
{
	document.getElementById('aPercent').disabled=false;
	document.getElementById('aa').style.display='none';
	document.getElementById('aSCal').style.display='table-cell';
	document.getElementById('aECal').style.display='table-cell';
	document.getElementById('ad').style.display='none';
	document.getElementById('editType').value='';
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
