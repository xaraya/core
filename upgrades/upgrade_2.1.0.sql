/*
  Upgrade script for the table changes made in Xaraya 2.1.0
  compared to Xaraya 2.0.0
  
  This script works with MySQL. It should be appropriately modified for other databases
*/

/* Merging the blockgroups and blocks tables */
/* Add blockgroups as a type of block */
INSERT INTO `xar_block_types` (name, module_id, info) 
    SELECT 'blockgroup', m.id, 'a:27:{s:4:"name";s:15:"BlockgroupBlock";s:6:"module";s:6:"blocks";s:9:"text_type";s:10:"Blockgroup";s:14:"text_type_long";s:10:"Blockgroup";s:14:"allow_multiple";b:1;s:12:"show_preview";b:1;s:7:"nocache";i:0;s:10:"pageshared";i:1;s:10:"usershared";i:1;s:3:"bid";i:0;s:7:"groupid";i:0;s:5:"group";s:0:"";s:19:"group_inst_template";s:0:"";s:8:"template";s:0:"";s:14:"group_template";s:0:"";s:8:"position";i:0;s:7:"refresh";i:0;s:5:"state";i:2;s:3:"tid";i:0;s:4:"type";s:5:"Block";s:5:"title";s:0:"";s:11:"cacheexpire";N;s:6:"expire";i:0;s:14:"display_access";a:3:{s:5:"group";i:0;s:5:"level";i:100;s:7:"failure";i:0;}s:13:"modify_access";a:3:{s:5:"group";i:0;s:5:"level";i:100;s:7:"failure";i:0;}s:13:"delete_access";a:3:{s:5:"group";i:0;s:5:"level";i:100;s:7:"failure";i:0;}s:7:"content";a:0:{}}' FROM xar_modules m WHERE m.name = 'blocks';

/* Move blockgroup data from the xar_block_groups to the xar_block_instances table */
INSERT INTO `xar_block_instances` (type_id, name, title, content, template, state)
    SELECT t.id, g.name, '', 'a:0:{}', g.template, 2 FROM xar_block_groups g, xar_block_types t WHERE t.name = 'blockgroup';
    
/* Remove the xar_block_groups table */
DROP TABLE xar_block_groups;

/* --------------------------------------------------------- */

/* Changing the name of the installation_rows configuration to installation_addremove */
UPDATE `xar_dynamic_configurations` SET `name` = REPLACE(name, "installation_rows", "installation_addremove");
UPDATE `xar_dynamic_configurations` SET `description` = REPLACE(description, "Allow adding of rows", "Allow adding/removing of items");
UPDATE `xar_dynamic_configurations` SET `label` = REPLACE(label, "Add/Delete Rows", "Add/Remove Items");
UPDATE `xar_dynamic_properties` SET `configuration` = REPLACE(configuration, 's:19:"initialization_rows";', 's:24:"initialization_addremove";');

/* --------------------------------------------------------- */

/* Removing the DenyBlocks privilege */
DELETE p, pm FROM xar_privileges p INNER JOIN xar_privmembers pm WHERE p.id = pm.privilege_id AND p.name = 'DenyBlocks' AND p.itemtype= 3;

/* Removing all masks with component Block */
DELETE FROM `xar_privileges` WHERE `itemtype` = 3 AND  `component` =  'Block';

/* --------------------------------------------------------- */

/* Adding configuration info to objects 1 and 2 */
UPDATE `xar_dynamic_objects` SET `config` = 'a:3:{s:14:"display_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"200";s:7:"failure";s:1:"0";}s:13:"modify_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:13:"delete_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}}'
WHERE id = 1;

UPDATE `xar_dynamic_objects` SET `config` = 'a:3:{s:14:"display_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"200";s:7:"failure";s:1:"0";}s:13:"modify_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}s:13:"delete_access";a:3:{s:5:"group";s:1:"0";s:5:"level";s:3:"800";s:7:"failure";s:1:"0";}}'
WHERE id = 2;

/* --------------------------------------------------------- */

/* Adding the Site Management privilege */
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype) VALUES ('SiteManagement', 0, 'All', 'All', 700, 'Site Manager access to all modules', 2)

/* Adding the SiteManagers group */
INSERT INTO `xar_roles` (name, itemtype,  users, uname, date_reg, valcode, state, auth_module_id) VALUES ('SiteManagers', 3, 1, 'sitemanagers', UNIX_TIMESTAMP(), 'createdbysystem', 3, 4)

/* Adding the SiteManager user */
INSERT INTO `xar_roles` (name, itemtype,  users, uname, email, date_reg, valcode, state, auth_module_id) VALUES ('SiteManager', 2, 0, 'manager', 'none@none.com', UNIX_TIMESTAMP(), 'createdbysystem', 3, 4)
