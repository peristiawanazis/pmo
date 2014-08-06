<?php
/* MACRO_PROJECTS setup.php, v 0.1.0 2012/05/02 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Setup page of the Macro-Projects module.
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

/**
 *  Name: MacroProjects
 *  Directory: macroprojects
 *  Version 1.0
 *  Type: user
 *  UI Name: macroprojects
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'MacroProjects';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'macroprojects';
$config['mod_setup_class'] = 'CSetupMacroProjects';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'MacroProjects';
$config['mod_ui_icon'] = 'folder5.png';
$config['mod_description'] = 'This module permit to create and edit Macro-project';
$config['mod_config'] = false;


if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}


class CSetupMacroProjects {

	function configure() { return true; }

	function remove() {
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;

		$bulk_sql[] = "DROP TABLE `{$dbprefix}macroprojects`";
		$bulk_sql[1] = "DROP TABLE `{$dbprefix}macroproject_contacts`";
		$bulk_sql[2] = "DROP TABLE `{$dbprefix}macroproject_departments`";
		$bulk_sql[3] = "DROP TABLE `{$dbprefix}macroproject_project`";
		$bulk_sql[4] = "DROP TABLE `{$dbprefix}macroproject_macroproject`";
		$q = new DBQuery;
		$q->alterTable('forums');
		$q->dropField('forum_macroproject');
		$q->exec();
		$q->clear();
		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		}
		db_delete('sysvals', 'sysval_id', 46);
		db_delete('sysvals', 'sysval_id', 47);
		db_delete('sysvals', 'sysval_id', 48);
		db_delete('sysvals', 'sysval_id', 49);
		db_delete('sysvals', 'sysval_id', 50);
		db_delete('sysvals', 'sysval_id', 51);
		return $success;
	}
  
	function upgrade($old_version) { return true; }

	function install() { $dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;
		
		$bulk_sql[] = "
                  CREATE TABLE IF NOT EXISTS `{$dbprefix}macroprojects` (
				  `macroproject_id` int(11) NOT NULL AUTO_INCREMENT,
				  `macroproject_company` int(11) NOT NULL DEFAULT '0',
				  `macroproject_company_internal` int(11) NOT NULL DEFAULT '0',
				  `macroproject_department` int(11) NOT NULL DEFAULT '0',
				  `macroproject_name` varchar(255) DEFAULT NULL,
				  `macroproject_short_name` varchar(10) DEFAULT NULL,
				  `macroproject_owner` int(11) DEFAULT '0',
				  `macroproject_url` varchar(255) DEFAULT NULL,
				  `macroproject_demo_url` varchar(255) DEFAULT NULL,
				  `macroproject_start_date` datetime DEFAULT NULL,
				  `macroproject_end_date` datetime DEFAULT NULL,
				  `macroproject_actual_end_date` datetime DEFAULT NULL,
				  `macroproject_status` int(11) DEFAULT '0',
				  `macroproject_percent_complete` tinyint(4) DEFAULT '0',
				  `macroproject_color_identifier` varchar(6) DEFAULT 'eeeeee',
				  `macroproject_description` text,
				  `macroproject_target_budget` decimal(15,2) DEFAULT NULL,
				  `macroproject_actual_budget` decimal(15,2) DEFAULT NULL,
				  `macroproject_creator` int(11) DEFAULT '0',
				  `macroproject_private` tinyint(3) unsigned DEFAULT '0',
				  `macroproject_departments` char(100) DEFAULT NULL,
				  `macroproject_contacts` char(100) DEFAULT NULL,
				  `macroproject_priority` tinyint(4) DEFAULT '0',
				  `macroproject_type` smallint(6) NOT NULL DEFAULT '0',
				  PRIMARY KEY (`macroproject_id`),
				  KEY `idx_sdate` (`macroproject_start_date`),
				  KEY `idx_edate` (`macroproject_end_date`),
				  KEY `idx_macroproject_owner` (`macroproject_owner`),
				  KEY `idx_macroproj1` (`macroproject_company`),
				  KEY `macroproject_short_name` (`macroproject_short_name`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;";
		$bulk_sql[1] = "
                 CREATE TABLE IF NOT EXISTS `{$dbprefix}macroproject_contacts` (
				  `macroproject_id` int(10) NOT NULL,
				  `contact_id` int(10) NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$bulk_sql[2] = "
                  CREATE TABLE IF NOT EXISTS `{$dbprefix}macroproject_departments` (
				  `macroproject_id` int(10) NOT NULL,
				  `department_id` int(10) NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$bulk_sql[3] = "
                  CREATE TABLE IF NOT EXISTS `{$dbprefix}macroproject_project` (
				  `macroproject_id` int(10) NOT NULL,
				  `project_id` int(10) NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$bulk_sql[4] = "
		  CREATE TABLE IF NOT EXISTS `{$dbprefix}macroproject_macroproject` (
		  `macroproject_father` int(10) NOT NULL,
		  `macroproject_son` int(10) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$bulk_sql[5] = "
                  INSERT INTO `sysvals` (`sysval_id`, `sysval_key_id`, `sysval_title`, `sysval_value`) VALUES
				(46, 1, 'MacroProjectStatus', '0|Not Defined\r\n1|Proposed\r\n2|In Planning\r\n3|In Progress\r\n4|On Hold\r\n5|Complete\r\n6|Template\r\n7|Archived'),
				(47, 1, 'MacroProjectType', '0|Unknown\r\n1|Administrative\r\n2|Operative'),
				(48, 3, 'MacroProjectColors', 'Web|FFE0AE\r\nEngineering|AEFFB2\r\nHelpDesk|FFFCAE\r\nSystem Administration|FFAEAE'),
				(49, 1, 'MacroProjectPriority', '-1|low\r\n0|normal\r\n1|high'),
				(50, 1, 'MacroProjectPriorityColor', '-1|#E5F7FF\r\n0|\r\n1|#FFDCB3'),
				(51, 1, 'MacroProjectRequiredFields', 'f.macroproject_name.value.length|<3\r\nf.macroproject_color_identifier.value.length|<3\r\nf.macroproject_company.options[f.macroproject_company.selectedIndex].value|<1');";
        $bulk_sql[6] = "
				  ALTER TABLE `forums` ADD `forum_macroproject` INT( 11 ) NOT NULL DEFAULT '0' AFTER `forum_project`;";
			foreach ($bulk_sql as $s) {
                  db_exec($s);
                  
                  if (db_error()) {
                        $success = 0;
                  }
            }      
		return $success;
	}
}
