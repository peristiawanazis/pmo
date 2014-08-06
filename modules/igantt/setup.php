<?php /* IGANTT setup.php,v 0.1.0 2008/12/10 */
/*
Copyright (c) 2008 -2009 Pierre-Yves SIMONOT Euxenis SAS 
*
* Description:	setup routine called for iGantt module installation in dotproject
*
* Author:		Pierre-Yves SIMONOT, Euxenis, <pierre-yves.simonot@euxenis.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'iGantt';
$config['mod_version'] = '0.1.0';
$config['mod_directory'] = 'igantt';
$config['mod_setup_class'] = 'CSetupiGantt';
$config['mod_type'] = 'utility';
$config['mod_ui_name'] = 'iGantt';
$config['mod_ui_icon'] = 'applet3-48.png';
$config['mod_description'] = 'This module uses CSS and AJAX technology to display Gantt charts and bar dragging for task update';
$config['mod_config'] = false;

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupiGantt{

	function install() {
		return null;
	}
	function remove() {
		return null;
	}
	
	function upgrade( $old_version ) {
		switch ( $old_version )
		{
		case "all":	
		case "0.1.0":
			return true;
		default:
			return false;
		}
		return false;
	}
}

?>	
	
