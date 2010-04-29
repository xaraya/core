/*
  Upgrade script for the table changes made in Xaraya 2.1.0
  compared to Xaraya 2.0.0
  
  This script works with MySQL. It should be appropriately modified for other databases
*/

/* Adding the releasenumber modvar */
INSERT INTO `xar_module_vars` (module_id, name, value)
    SELECT mods.id, 'releasenumber', 10 FROM xar_modules mods
    WHERE mods.name = 'base';

/* --------------------------------------------------------- */
/* Upgrading the core module version numbers */
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'authsystem';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'base';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'blocks';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'dynamicdata';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'installer';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'mail';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'modules';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'privileges';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'roles';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'themes';
UPDATE `xar_modules` SET version = '2.1.0' WHERE `name` = 'authsystem';

/* --------------------------------------------------------- */

/* Merging the blockgroups and blocks tables */
/* Add blockgroups as a type of block */
INSERT INTO `xar_block_types` (name, module_id, info) 
    SELECT 'blockgroup', m.id, 'a:27:{s:4:"name";s:15:"BlockgroupBlock";s:6:"module";s:6:"blocks";s:9:"text_type";s:10:"Blockgroup";s:14:"text_type_long";s:10:"Blockgroup";s:14:"allow_multiple";b:1;s:12:"show_preview";b:1;s:7:"nocache";i:0;s:10:"pageshared";i:1;s:10:"usershared";i:1;s:3:"bid";i:0;s:7:"groupid";i:0;s:5:"group";s:0:"";s:19:"group_inst_template";s:0:"";s:8:"template";s:0:"";s:14:"group_template";s:0:"";s:8:"position";i:0;s:7:"refresh";i:0;s:5:"state";i:2;s:3:"tid";i:0;s:4:"type";s:5:"Block";s:5:"title";s:0:"";s:11:"cacheexpire";N;s:6:"expire";i:0;s:14:"display_access";a:3:{s:5:"group";i:0;s:5:"level";i:100;s:7:"failure";i:0;}s:13:"modify_access";a:3:{s:5:"group";i:0;s:5:"level";i:100;s:7:"failure";i:0;}s:13:"delete_access";a:3:{s:5:"group";i:0;s:5:"level";i:100;s:7:"failure";i:0;}s:7:"content";a:0:{}}' FROM xar_modules m WHERE m.name = 'blocks';

/* Move blockgroup data from the xar_block_groups to the xar_block_instances table */
INSERT INTO `xar_block_instances` (type_id, name, title, content, template, state)
    SELECT t.id, g.name, '', 'a:0:{}', g.template, 2 FROM xar_block_groups g, xar_block_types t WHERE t.name = 'blockgroup';
    
/* Reset the group pointers in the  xar_block_group_instances table */
UPDATE `xar_block_group_instances` gi SET group_id = 
    (SELECT i.id FROM xar_block_groups g, xar_block_instances i WHERE i.name = g.name AND g.id = gi.group_id);

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

/* --------------------------------------------------------- */

/* Adding sitemanager masks */
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageBase',  m.id, 'All', 'All', 700, 'Site Manager mask for base module',3 FROM `xar_modules` m WHERE name = 'base';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageBlocks',  m.id, 'All', 'All', 700, 'Site Manager mask for blocks module',3 FROM `xar_modules` m WHERE name = 'blocks';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageMail',  m.id, 'All', 'All', 700, 'Site Manager mask for mail module',3 FROM `xar_modules` m WHERE name = 'mail';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageModules',  m.id, 'All', 'All', 700, 'Site Manager mask for modules module',3 FROM `xar_modules` m WHERE name = 'modules';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManagePrivileges',  m.id, 'All', 'All', 700, 'Site Manager mask for privileges module',3 FROM `xar_modules` m WHERE name = 'privileges';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageRoles',  m.id, 'All', 'All', 700, 'Site Manager mask for roles module',3 FROM `xar_modules` m WHERE name = 'roles';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageThemes',  m.id, 'All', 'All', 700, 'Site Manager mask for themes module',3 FROM `xar_modules` m WHERE name = 'themes';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageAuthsystem',  m.id, 'All', 'All', 700, 'Site Manager mask for authsystem module',3 FROM `xar_modules` m WHERE name = 'authsystem';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ManageDynamicData',  m.id, 'All', 'All', 700, 'Site Manager mask for dynamicdata module',3 FROM `xar_modules` m WHERE name = 'dynamicdata';

/* --------------------------------------------------------- */

/* Redefining blocks module masks */
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'ActivateBlocks',  m.id, 'All', 'All', 400, 'Activate mask for blocks module',3 FROM `xar_modules` m WHERE name = 'blocks';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'EditBlocks',  m.id, 'All', 'All', 500, 'Edit mask for blocks module',3 FROM `xar_modules` m WHERE name = 'blocks';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'AddBlocks',  m.id, 'All', 'All', 600, 'Add mask for blocks module',3 FROM `xar_modules` m WHERE name = 'blocks';
INSERT INTO `xar_privileges` (name,  module_id, component, instance, level, description, itemtype)  
    SELECT 'AdminBlocks',  m.id, 'All', 'All', 800, 'Admin mask for blocks module',3 FROM `xar_modules` m WHERE name = 'blocks';

DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'ViewBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'ReadBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'CommentBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'ModerateBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'EditBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'AddBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'DeleteBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'AdminBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'EditBlockGroup';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'ReadBlocksBlock';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'ViewAuthsystemBlocks';

/* --------------------------------------------------------- */

/* Redefining mail module masks */
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'DeleteMail';

/* --------------------------------------------------------- */

/* Redefining privileges module masks */
UPDATE `xar_privileges` SET name = 'EditPrivileges' WHERE name = 'EditPrivilege';
UPDATE `xar_privileges` SET name = 'AddPrivileges' WHERE name = 'AddPrivilege';
UPDATE `xar_privileges` SET name = 'AdminPrivileges' WHERE name = 'AdminPrivilege';

DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'DeletePrivilege';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'ViewPrivileges';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'AssignPrivilege';
DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'DeassignPrivilege';

/* --------------------------------------------------------- */

/* Redefining privileges module masks */
UPDATE `xar_privileges` SET name = 'ReadRoles' WHERE name = 'ReadRole';
UPDATE `xar_privileges` SET name = 'EditRoles' WHERE name = 'EditRole';
UPDATE `xar_privileges` SET name = 'AddRoles' WHERE name = 'AddRole';
UPDATE `xar_privileges` SET name = 'AdminRoles' WHERE name = 'AdminRole';

DELETE FROM `xar_privileges` WHERE `xar_privileges`.`name` = 'DeleteRole';

/* --------------------------------------------------------- */

/* Redefining privileges module masks */
UPDATE `xar_privileges` SET name = 'AdminThemes' WHERE name = 'AdminTheme';

/* --------------------------------------------------------- */

/* Adding the email confirm configuration */
INSERT INTO `xar_dynamic_configurations` (`name`, `description`, `property_id`, `label`, `ignore_empty`, `configuration`) VALUES
('validation_email_confirm', 'Show a second email field to be filled in', 14, 'Confirm Email', 1, 'a:1:{s:14:"display_layout";s:7:"default";}');

/* --------------------------------------------------------- */

/* Hide the roles_role object */
 DELETE FROM `xar_dynamic_objects` WHERE `xar_dynamic_objects`.`name` = 'roles_roles';
/* Changetheuser and group itemtypes */
UPDATE `xar_roles` SET itemtype = 1 WHERE itemtype = 2;
UPDATE `xar_roles` SET itemtype = 2 WHERE itemtype = 3;
