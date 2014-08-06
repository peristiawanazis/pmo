#
# $Id: upgrade_latest.sql 6177 2012-08-14 07:51:05Z ajdonnison $
#
# DO NOT USE THIS SCRIPT DIRECTLY - USE THE INSTALLER INSTEAD.
#
# All entries must be date stamped in the correct format.
#

# Extend value_charvalue from 250 to 1000 characters
ALTER TABLE `%dbprefix%custom_fields_values` MODIFY `value_charvalue` `value_charvalue` VARCHAR( 1000 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL; 