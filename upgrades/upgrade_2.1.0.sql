/*
  Upgrade script for the table changes made in Xaraya 2.1.0
  compared to Xaraya 2.0.0
*/

/* Merging the blockgroups and blocks tables */
/* FIXME: add SQL to move blockgroup data from xar_block_groups to xar_block_instances*/
DROP TABLE xar_block_groups;

/* Changing the name of the installation_rows configuration to installation_addremove */
UPDATE `xar_dynamic_configurations` SET `name` = REPLACE(name, "installation_rows", "installation_addremove");
UPDATE `xar_dynamic_configurations` SET `description` = REPLACE(description, "Allow adding of rows", "Allow adding/removing of items");
UPDATE `xar_dynamic_configurations` SET `label` = REPLACE(label, "Add/Delete Rows", "Add/Remove Items");
UPDATE `xar_dynamic_properties` SET `configuration` = REPLACE(configuration, 's:19:"initialization_rows";', 's:24:"initialization_addremove";');