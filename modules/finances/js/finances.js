/* FINANCES finances.js, v 0.1.0 2012/07/20 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Js function page of the Finances module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
*/

/* Function called when mouseEnter on a <td></td> that can be edited by user.
*/ 
function tdEnter(){
	if(!$(this).hasClass('edit')) {
		$(this).append($("<img style='float:right;' src='./images/icons/pencil.gif' />"));
		$(this).css("cursor", "pointer");
	}
}

/* Function called when mouseLeave on a <td></td> that can be edited by user.
*/ 
function tdLeave(){
	if(!$(this).hasClass('edit')) {
		$(this).find("img:last").remove();
		$(this).css("cursor", "default");
	}
}

/* Add thousands separator to Str
** @param	string	Str
** @param	string	Sep
** @return	string	result
*/
function addThousandsSep(Str, Sep)
{
	var V = Str;
	V = V.replace(/,/g,'');
	var R = new RegExp('(-?[0-9]+)([0-9]{3})'); 
	while(R.test(V))
	{
		V = V.replace(R, '$1'+Sep+'$2');
	}
    return V;
}

/* Set the <td></td> values for all lines.
*/ 
function completeTd()
{
	var currency = getCurrency();
	var end = true;
	var sum;
	var wait;
	// reverse in order to begin with children
	$($(".todo").get().reverse()).each(
		function(i){
			sum = 0;
			wait = false;
			$(".child-of-"+this.parentNode.id+this.getAttribute('rel')).each(
				function (j){
					if($(this).hasClass("todo")){ wait = true; return;}
					else sum += parseFloat(this.innerHTML.replace(/ /g,''));
				}
			);
			if(wait)
				end = false;
			else{
				this.innerHTML = addThousandsSep(sum.toFixed(2), ' ')+currency;
				$(this).removeClass("todo");
			}
		}
	);
	if(!end) completeTd();
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
	
	if(button.id == "saveButton")
		document.mainFrm.edit.value = 1;
	
	button.form.submit();
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