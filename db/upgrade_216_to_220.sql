#
# $Id: upgrade_latest.sql 6104 2010-12-16 10:46:32Z ajdonnison $
# 
# DO NOT USE THIS SCRIPT DIRECTLY - USE THE INSTALLER INSTEAD.
#
# All entries must be date stamped in the correct format.
#

# 20110616
# Fix 100% tasks bug
ALTER TABLE `%dbprefix%tasks` MODIFY `task_percent_complete` decimal(5,2) DEFAULT '0';
UPDATE `%dbprefix%tasks` SET task_percent_complete = 100 WHERE task_percent_complete = 99.99;

# 20110616
# Allow gap dependencies
ALTER TABLE `%dbprefix%task_dependencies` ADD `dependencies_delay` INT( 4 ) NOT NULL DEFAULT '0';

# 20120830
# Create iCal table
CREATE TABLE IF NOT EXISTS `%dbprefix%tasks_ical` (
  `task_id` int(10) NOT NULL, 
  `UID` varchar(30) DEFAULT NULL,
  `created` varchar(15) NOT NULL, 
  `sequence` int(10) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

# 20120830
# Insert Dotproject version
INSERT INTO %dbprefix%dpversion VALUES ('2.2', 2, '2012-09-01', '2012-09-06');

# 20121019
# Create budget table
CREATE TABLE IF NOT EXISTS `%dbprefix%budget` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;
