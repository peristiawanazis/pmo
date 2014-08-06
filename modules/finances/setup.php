<?php
/* FINANCES setup.php, v 0.1.0 2012/07/20 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Setup page of the Finances module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
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

/**
 *  Name: Finances
 *  Directory: finances
 *  Version 1.0
 *  Type: user
 *  UI Name: finances
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'Finances';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'finances';
$config['mod_setup_class'] = 'CSetupFinances';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Finances';
$config['mod_ui_icon'] = 'folder5.png';
$config['mod_description'] = 'This module add budget improvements';
$config['mod_config'] = false;


if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}


class CSetupFinances {

  function configure() { return true; }

  function remove() {
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;
/*
		$bulk_sql[] = "DROP TABLE `{$dbprefix}budget`";
		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		} */
		return $success; 
	}
  
	function upgrade($old_version) { return true; }

	function install() { 
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;
/*		$bulk_sql[] = "
                  CREATE TABLE IF NOT EXISTS `".$dbprefix."budget` (
				  `budget_id` int(11) NOT NULL AUTO_INCREMENT,
				  `task_id` int(11) NOT NULL DEFAULT '0',
				  `Tax` decimal(4,2) NOT NULL DEFAULT '0',
				  `display_tax` tinyint(1) NOT NULL DEFAULT '0',
				  `only_financial` tinyint(1) NOT NULL DEFAULT '0',
				  `equipment_investment` decimal(15,2) DEFAULT '0',
				  `intangible_investment` decimal(15,2) DEFAULT '0',
				  `service_investment` decimal(15,2) DEFAULT '0',
				  `equipment_operation` decimal(15,2) DEFAULT '0',
				  `intangible_operation` decimal(15,2) DEFAULT '0',
				  `service_operation` decimal(15,2) DEFAULT '0',
				  PRIMARY KEY (`budget_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;";
			foreach ($bulk_sql as $s) {
                  db_exec($s);
                  
                  if (db_error()) {
                        $success = 0;
                  }
            } */
		return $success; 
	}
}
