/*
  Mysql 5.0 Upgrade script for the table changes made in 2.x branch
  This is temporary for development. The whole upgrade needs to be
  integrated in the (new) DDL system.
  The script here is mysql specific, but can easily be adapted for
  other databases. Again, this is a TEMPORARY HELPER SCRIPT
  TODO list for this script:
  * adapt all the instance queries wrt the block_types changes!!!!
    (on last count in core, 15, plus 1 for each block in modules)
    i would like to change this method of doing instances though, tying it
    to the physical datamodel like this isnt very nice
  * add new indexes for security_instances?
  * make it less mysql specific
*/

/* Module user vars table is now module itemvars table */
/* The primary key index is automatically adapted */
RENAME TABLE xar_module_uservars TO xar_module_itemvars;
ALTER TABLE xar_module_itemvars CHANGE COLUMN xar_uid xar_itemid INT(11) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE xar_module_itemvars CHANGE COLUMN xar_mvid INT(11) UNSIGNED NOT NULL DEFAULT '0';

/* Hooks table smodule and tmodule changed to smodid and tmodid where the registration is the module id now instead of the name */
/* First add the two new colum definitions */
ALTER TABLE xar_hooks ADD COLUMN xar_smodid INT(11) NOT NULL DEFAULT '0';
ALTER TABLE xar_hooks ADD COLUMN xar_tmodid INT(11) NOT NULL DEFAULT '0';
/* Make sure we insert the data properly */
UPDATE xar_hooks INNER JOIN xar_modules ON xar_hooks.xar_smodule = xar_modules.xar_name
SET    xar_hooks.xar_smodid = xar_modules.xar_id;
UPDATE xar_hooks INNER JOIN xar_modules ON xar_hooks.xar_tmodule = xar_modules.xar_name
SET    xar_hooks.xar_tmodid = xar_modules.xar_id;
ALTER TABLE xar_hooks DROP COLUMN xar_smodule;
ALTER TABLE xar_hooks DROP COLUMN xar_tmodule;

/* Template tags table column xar_module replaced by xar_modid */
ALTER TABLE xar_template_tags ADD COLUMN xar_modid INT(11) NOT NULL DEFAULT '0';
UPDATE xar_template_tags INNER JOIN xar_modules ON xar_template_tags.xar_module = xar_modules.xar_name
SET    xar_template_tags.xar_modid = xar_modules.xar_id;
ALTER TABLE xar_template_tags DROP COLUMN xar_module;

/* security_instances table columns xar_module replaced by column xar_modid */
ALTER TABLE xar_security_instances ADD COLUMN xar_modid INT(11) NOT NULL DEFAULT '0';
UPDATE xar_security_instances INNER JOIN xar_modules ON xar_security_instances.xar_module = xar_modules.xar_name
SET    xar_security_instances.xar_modid = xar_modules.xar_id;
ALTER TABLE xar_security_instances DROP COLUMN xar_module;

/* security_masks table column xar_module replaced by column xar_modid */
/* NOTE: there were values of 'All' in this column, which are now defined as 0 */
ALTER TABLE xar_security_masks ADD COLUMN xar_modid INT(11) NOT NULL DEFAULT '0';
UPDATE xar_security_masks INNER JOIN xar_modules ON xar_security_masks.xar_module = xar_modules.xar_name
SET    xar_security_masks.xar_modid = xar_modules.xar_id;
ALTER TABLE xar_security_masks DROP COLUMN xar_module;
CREATE INDEX i_xar_security_masks_modid ON xar_security_masks (xar_modid);

/* roles table column xar_authmodule replaced by column xar_auth_modid */
ALTER TABLE xar_roles ADD COLUMN xar_auth_modid INT(11) NOT NULL DEFAULT '0';
UPDATE xar_roles INNER JOIN xar_modules ON xar_roles.xar_auth_module = xar_modules.xar_name
SET    xar_roles.xar_auth_modid = xar_modules.xar_id;
ALTER TABLE xar_roles DROP COLUMN xar_auth_module;

/* block_types table column xar_module replaced by column xar_modid */
ALTER TABLE xar_block_types ADD COLUMN xar_modid INT(11) NOT NULL DEFAULT '0';
UPDATE xar_block_types INNER JOIN xar_modules ON xar_block_types.xar_module = xar_modules.xar_name
SET    xar_block_types.xar_modid = xar_modules.xar_id;
DROP INDEX i_xar_block_types2 ON xar_block_types;
ALTER TABLE xar_block_types DROP COLUMN xar_module;
CREATE UNIQUE INDEX i_xar_block_types2 ON xar_block_types (xar_modid,xar_type);

/* Themes table gained a xar_state column */
ALTER TABLE xar_themes ADD COLUMN xar_state INT(11) NOT NULL DEFAULT '0';
UPDATE xar_themes INNER JOIN xar_theme_states ON xar_themes.xar_regid = xar_theme_states.xar_regid
SET    xar_themes.xar_state = xar_theme_states.xar_state;

/* Modules table gained a xar_state column */
ALTER TABLE xar_modules ADD COLUMN xar_state INT(11) NOT NULL DEFAULT '0';
UPDATE xar_modules INNER JOIN xar_module_states ON xar_modules.xar_regid = xar_module_states.xar_regid
SET    xar_modules.xar_state = xar_module_states.xar_state;

/* Storage of config_vars and module_vars is consolidated into one table now */
/* Copy the entries of the config_vars table to the module vars table */
INSERT INTO xar_module_vars (xar_modid, xar_name, xar_value)
SELECT 0,xar_name,xar_value FROM xar_config_vars

/* Easy ones, tables not needed anymore */
DROP TABLE xar_admin_menu;
DROP TABLE xar_theme_vars;
DROP TABLE xar_tables;
DROP TABLE xar_theme_states;
DROP TABLE xar_module_states;
DROP TABLE xar_config_vars;

/* DD extending */
ALTER TABLE xar_dynamic_objects ADD COLUMN xar_object_parent INT(11) NOT NULL DEFAULT '0';

/* Defining a primary key for:
   - xar_privmembers
   - xar-rolemembers
   - xar_security_acl
   instead of the artificial unique key
*/
DROP INDEX i_xar_privmembers_id ON xar_privmembers;
ALTER TABLE xar_privmembers ADD PRIMARY KEY (xar_pid,xar_parentid);
DROP INDEX i_xar_rolememb_id ON xar_rolemembers;
ALTER TABLE xar_rolemembers ADD PRIMARY KEY (xar_uid,xar_parentid);
DROP INDEX i_xar_security_acl_id ON xar_security_acl;
ALTER TABLE xar_security_acl ADD PRIMARY KEY(xar_partid,xar_permid);

DROP INDEX i_xar_cache_blocks_1 ON xar_cache_blocks;
ALTER TABLE xar_cache_blocks ADD PRIMARY KEY (xar_bid);

DROP TABLE xar_security_levels;

/* Replace the data-list handler
  TODO: might need to be changed again depending on how we handle the new compiler tag registration */
UPDATE xar_template_tags
SET xar_handler = 'dynamicdata_userapi_handleViewTag',
    xar_data    = 'O:14:"xarTemplateTag":12:{s:5:"_name";s:9:"data-list";s:11:"_attributes";a:0:{}s:8:"_handler";s:34:"dynamicdata_adminapi_handleListTag";s:7:"_module";s:11:"dynamicdata";s:5:"_type";s:4:"user";s:5:"_func";s:13:"handleViewTag";s:12:"_hasChildren";b:0;s:8:"_hasText";b:0;s:13:"_isAssignable";b:0;s:10:"_isPHPCode";b:1;s:15:"_needAssignment";b:0;s:14:"_needParameter";b:0;}' WHERE `xar_template_tags`.`xar_handler` = 'dynamicdata_adminapi_handleListTag';

/* Revisiting configvars reference in module_vars table */
ALTER TABLE xar_module_vars MODIFY COLUMN `xar_modid` INTEGER DEFAULT NULL;
UPDATE xar_module_vars SET xar_modid=NULL WHERE xar_modid=0;

/* Let the xar_realmid column reference the realms table
 - add the new column (default is nul ~~ All
 - update the others with the rids in xar_security_realms
*/
ALTER TABLE xar_privileges ADD COLUMN xar_realmid INTEGER DEFAULT NULL;
UPDATE xar_privileges INNER JOIN xar_security_realms ON xar_privileges.xar_realm = xar_security_realms.xar_name
SET    xar_privileges.xar_realmid = xar_security_realms.xar_rid;
CREATE INDEX i_xar_privileges_realmid ON xar_privileges (xar_realmid);
ALTER TABLE xar_privileges DROP COLUMN xar_realm;

ALTER TABLE xar_security_masks ADD COLUMN xar_realmid INTEGER DEFAULT NULL;
UPDATE xar_security_masks INNER JOIN xar_security_realms ON xar_security_masks.xar_realm = xar_security_realms.xar_name
SET    xar_security_masks.xar_realmid = xar_security_realms.xar_rid;
CREATE INDEX i_xar_security_masks_realmid ON xar_privileges (xar_realmid);
ALTER TABLE xar_security_masks DROP COLUMN xar_realm;

CREATE UNIQUE INDEX i_xar_privileges_name ON xar_privileges (xar_name);

/* Relations is still todo after N years */
DROP TABLE xar_dynamic_relations;

/* properties_def.reqmodules -> modid int not null */
ALTER TABLE xar_dynamic_properties_def ADD COLUMN xar_prop_modid INTEGER DEFAULT NULL;
UPDATE xar_dynamic_properties_def INNER JOIN xar_modules ON xar_dynamic_properties_def.xar_prop_reqmodules = xar_modules.xar_name
SET xar_dynamic_properties_def.xar_prop_modid = xar_modules.xar_id;
ALTER TABLE xar_dynamic_properties_def DROP column xar_prop_reqmodules;
CREATE INDEX i_xar_dynpropdef_modid ON xar_dynamic_properties_def (xar_prop_modid);

/* Making the hooks table modid columns 'foreign keyable' */
ALTER TABLE xar_hooks MODIFY COLUMN xar_smodid INTEGER DEFAULT NULL;
ALTER TABLE xar_hooks MODIFY COLUMN xar_tmodid INTEGER NOT NULL;

/* removing unneeded moduleid and itemtype fields for the properties table */
ALTER TABLE `xar_dynamic_properties` DROP INDEX `i_xar_dynprops_combo`;
ALTER TABLE `xar_dynamic_properties` DROP `xar_prop_moduleid` , DROP `xar_prop_itemtype` ;
DELETE FROM `xar_dynamic_properties` WHERE `xar_dynamic_properties`.`xar_prop_id` =15 LIMIT 1 ;
DELETE FROM `xar_dynamic_properties` WHERE `xar_dynamic_properties`.`xar_prop_id` =16 LIMIT 1 ;
ALTER TABLE `xar_dynamic_properties` DROP INDEX `i_xar_dynprops_name`;
ALTER TABLE `xar_dynamic_properties` DROP INDEX `i_xar_dynprops_objectid`;
ALTER TABLE `xar_dynamic_properties` ADD UNIQUE `i_xar_dynprops_combo` ( `xar_prop_objectid` , `xar_prop_name` );

/* merging the masks and privileges tables */
/*
 - update the module column values containing "All" to contain 0
 - update the module column values containing "empty" to contain null
 - change the name and definition of xar_module to xar_modid as INTEGER
 - add the rows of the masks table to the privileges table
 - add a new column type to the privileges table
 - set the type values to 2 (privileges) or 3 (masks)
 - drop the masks table
*/
ALTER TABLE `xar_privileges` ADD COLUMN type INTEGER DEFAULT NULL;
UPDATE `xar_privileges` SET type = 2;
UPDATE `xar_privileges` a INNER JOIN `xar_modules` b ON a.xar_module = b.xar_name SET a.xar_module = b.xar_id;
ALTER TABLE `xar_privileges` CHANGE `xar_module` `xar_module` VARCHAR(100) DEFAULT NULL;
UPDATE `xar_privileges` SET xar_module = NULL WHERE xar_module = 'empty';
UPDATE `xar_privileges` SET xar_module = 0 WHERE xar_module = 'All';
ALTER TABLE `xar_privileges` CHANGE `xar_module` `xar_modid` INTEGER DEFAULT NULL;
ALTER TABLE `xar_privileges` DROP INDEX `i_xar_privileges_name`;
CREATE UNIQUE INDEX i_xar_privileges_name ON xar_privileges (xar_name,xar_modid,type);
INSERT INTO `xar_privileges` (xar_pid, xar_name, xar_realmid, xar_modid, xar_component, xar_instance, xar_level, xar_description)
    SELECT 0,xar_name, xar_realmid, xar_modid, xar_component, xar_instance, xar_level, xar_description FROM `xar_security_masks`;
UPDATE `xar_privileges` SET type = 3 WHERE type IS NULL;
DROP TABLE `xar_security_masks`;

/* Making the default value in the rolemembers table form 0 to null' */
DROP INDEX i_xar_rolememb_id ON xar_rolemembers;
ALTER TABLE `xar_rolemembers` CHANGE `xar_parentid` `xar_parentid` INTEGER DEFAULT NULL;
UPDATE `xar_rolemembers` SET xar_parentid = NULL WHERE xar_parentid = 0;

/* Making the default value in the privmembers table form 0 to null' */
DROP INDEX i_xar_privmembers_uid ON xar_privmembers;
ALTER TABLE `xar_privmembers` CHANGE `xar_parentid` `xar_parentid` INTEGER DEFAULT NULL;
UPDATE `xar_privmembers` SET xar_parentid = NULL WHERE xar_parentid = 0;

/* Dropping column prefixes from xar_session_info, session_info is moot, so we take the high road :-) */
DROP table xar_session_info; /* Drops the indexes too */
CREATE TABLE  xar_session_info (
  `id` varchar(32) NOT NULL,
  `ip_addr` varchar(20) NOT NULL,
  `first_use` int(11) NOT NULL default '0',
  `last_use` int(11) NOT NULL default '0',
  `role_id` int(11) NOT NULL default '0',
  `vars` blob,
  `remember` int(11) default '0',
  PRIMARY KEY  (`id`),
  KEY `i_xar_session_role_id` (`role_id`),
  KEY `i_xar_session_lastused` (`last_use`)
);


/* Dropping column prefixes from xar_module_vars table, need to preserve data */
ALTER TABLE `xar_module_vars`
 CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_modid` `module_id` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_name` `name` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_value` `value` LONGTEXT DEFAULT NULL;
/* FIXME: incomplete, indexes probably need to be recreated, or at least renamed */


ALTER TABLE `xar_module_itemvars`
 CHANGE COLUMN `xar_mvid` `module_var_id` INTEGER NOT NULL,
 CHANGE COLUMN `xar_itemid` `item_id` INTEGER UNSIGNED NOT NULL,
 CHANGE COLUMN `xar_value` `value` LONGTEXT DEFAULT NULL;
/* FIXME: incomplete, indexes probably need to be recreated, or at least renamed */


ALTER TABLE `xar_template_tags`
 CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_name` `name` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_modid` `module_id` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_handler` `handler` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_data` `data` TEXT DEFAULT NULL;


 ALTER TABLE `xar_hooks`
  CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
  CHANGE COLUMN `xar_object` `object` VARCHAR(64) NOT NULL,
  CHANGE COLUMN `xar_action` `action` VARCHAR(64) NOT NULL,
  CHANGE COLUMN `xar_smodid` `s_module_id` INTEGER DEFAULT NULL,
  CHANGE COLUMN `xar_stype` `s_type` VARCHAR(64) NOT NULL,
  CHANGE COLUMN `xar_tarea` `t_area` VARCHAR(64) NOT NULL,
  CHANGE COLUMN `xar_tmodid` `t_module_id` INTEGER NOT NULL,
  CHANGE COLUMN `xar_ttype` `t_type` VARCHAR(64) NOT NULL,
  CHANGE COLUMN `xar_tfunc` `t_func` VARCHAR(64) NOT NULL,
  CHANGE COLUMN `xar_order` `priority` INTEGER NOT NULL DEFAULT 0;


ALTER TABLE `xar_privileges`
 CHANGE COLUMN `xar_pid` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_name` `name` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_realmid` `realmid` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_modid` `module_id` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_component` `component` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_instance` `instance` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_level` `level` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_description` `description` VARCHAR(255) NOT NULL;

ALTER TABLE `xar_privmembers`
 CHANGE COLUMN `xar_pid` `id`  INTEGER DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_parentid` `parentid` INTEGER DEFAULT NULL;
/* FIXME: rename the pid index */

ALTER TABLE `xar_security_realms`
 CHANGE COLUMN `xar_rid` `id` INTEGER NOT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_name` `name` VARCHAR(255) NOT NULL;

ALTER TABLE `xar_security_acl`
 CHANGE COLUMN `xar_partid` `partid` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_permid` `permid` INTEGER NOT NULL DEFAULT 0;
/* FIXME: phpmyadmin shows an error on the indexes */

ALTER TABLE `xar_security_instances`
 CHANGE COLUMN `xar_iid` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_modid` `module_id` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_component` `component` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_header` `header` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_query` `query` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_limit` `ddlimit` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_propagate` `propagate` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_instancetable2` `instancetable2` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_instancechildid` `instancechildid` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_instanceparentid` `instanceparentid` VARCHAR(100) NOT NULL,
 CHANGE COLUMN `xar_description` `description` VARCHAR(255) NOT NULL;
/* TODO: this table will surely be lightened up */

ALTER TABLE `xar_themes`
 CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_name` `name` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_regid` `regid` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_directory` `directory` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_mode` `mode` INTEGER NOT NULL DEFAULT 1,
 CHANGE COLUMN `xar_author` `author` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_homepage` `homepage` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_email` `email` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_description` `description` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_contactinfo` `contactinfo` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_publishdate` `publishdate` VARCHAR(32) NOT NULL,
 CHANGE COLUMN `xar_license` `license` VARCHAR(255) NOT NULL,
 CHANGE COLUMN `xar_version` `version` VARCHAR(10) NOT NULL,
 CHANGE COLUMN `xar_xaraya_version` `xaraya_version` VARCHAR(10) NOT NULL,
 CHANGE COLUMN `xar_bl_version` `bl_version` VARCHAR(10) NOT NULL,
 CHANGE COLUMN `xar_class` `class` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_state` `state` INTEGER NOT NULL DEFAULT 1;

ALTER TABLE `xar_modules`
 CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL DEFAULT NULL AUTO_INCREMENT,
 CHANGE COLUMN `xar_name` `name` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_regid` `regid` INTEGER DEFAULT NULL,
 CHANGE COLUMN `xar_directory` `directory` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_version` `version` VARCHAR(10) NOT NULL,
 CHANGE COLUMN `xar_mode` `mode` INTEGER NOT NULL DEFAULT 1,
 CHANGE COLUMN `xar_class` `class` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_category` `category` VARCHAR(64) NOT NULL,
 CHANGE COLUMN `xar_admin_capable` `admin_capable` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_user_capable` `user_capable` INTEGER NOT NULL DEFAULT 0,
 CHANGE COLUMN `xar_state` `state` INTEGER NOT NULL DEFAULT 0;

ALTER TABLE `xar_dynamic_data`
  CHANGE COLUMN `xar_dd_id` `dd_id` INTEGER NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `xar_dd_propid` `dd_propid` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_dd_itemid` `dd_itemid` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_dd_value` `dd_value` mediumtext;

ALTER TABLE `xar_dynamic_objects`
  CHANGE COLUMN `xar_object_id` `object_id` INTEGER NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `xar_object_name` `object_name` varchar(30) NOT NULL default '',
  CHANGE COLUMN `xar_object_label` `object_label` varchar(254) NOT NULL default '',
  CHANGE COLUMN `xar_object_moduleid` `object_moduleid` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_object_itemtype` `object_itemtype` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_object_parent` `object_parent` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_object_urlparam` `object_urlparam` varchar(30) NOT NULL default 'itemid',
  CHANGE COLUMN `xar_object_maxid` `object_maxid` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_object_config` `object_config` text,
  CHANGE COLUMN `xar_object_isalias` `object_isalias` tinyint(4) NOT NULL default '1';

ALTER TABLE `xar_dynamic_properties`
  CHANGE COLUMN `xar_prop_id` `prop_id` INTEGER NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `xar_prop_name` `prop_name` varchar(30) NOT NULL default '',
  CHANGE COLUMN `xar_prop_label` `prop_label` varchar(254) NOT NULL default '',
  CHANGE COLUMN `xar_prop_objectid` `prop_objectid` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_prop_type` `prop_type` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_prop_default` `prop_default` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_source` `prop_source` varchar(254) NOT NULL default 'dynamic_data',
  CHANGE COLUMN `xar_prop_status` `prop_status` INTEGER NOT NULL default '33',
  CHANGE COLUMN `xar_prop_order` `prop_order` tinyint(4) NOT NULL default '0',
  CHANGE COLUMN `xar_prop_validation` `prop_validation` text;

ALTER TABLE `xar_dynamic_properties_def`
  CHANGE COLUMN `xar_prop_id` `prop_id` INTEGER NOT NULL auto_increment,
  CHANGE COLUMN `xar_prop_name` `prop_name` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_label` `prop_label` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_parent` `prop_parent` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_filepath` `prop_filepath` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_class` `prop_class` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_validation` `prop_validation` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_source` `prop_source` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_reqfiles` `prop_reqfiles` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_modid` `prop_modid` INTEGER default NULL,
  CHANGE COLUMN `xar_prop_args` `prop_args` mediumtext NOT NULL,
  CHANGE COLUMN `xar_prop_aliases` `prop_aliases` varchar(254) default NULL,
  CHANGE COLUMN `xar_prop_format` `prop_format` INTEGER default '0';

/* Replace property definitions in the properties table */
UPDATE `xar_dynamic_properties` SET `prop_source` = REPLACE(prop_source, ".xar_", ".");
UPDATE `xar_dynamic_properties` SET `prop_source` = REPLACE(prop_source, "xar_roles.uid", "xar_roles.id");

ALTER TABLE `xar_block_group_instances`
  CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL auto_increment,
  CHANGE COLUMN `xar_group_id` `group_id` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_instance_id` `instance_id` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_template` `template` varchar(100) default NULL,
  CHANGE COLUMN `xar_position` `position` INTEGER NOT NULL default '0';

/* TODO: fix these keys */
  KEY `i_xar_block_group_instances` (`xar_group_id`),
  KEY `i_xar_block_group_instances_2` (`xar_instance_id`)

ALTER TABLE `xar_block_groups`
  CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL auto_increment,
  CHANGE COLUMN `xar_name` `name` varchar(255) NOT NULL default '',
  CHANGE COLUMN `xar_template` `template` varchar(255) NOT NULL default '';

/* TODO: fix these keys */
  UNIQUE KEY `i_xar_block_groups` (`xar_name`)

ALTER TABLE `xar_block_instances`
  CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL auto_increment,
  CHANGE COLUMN `xar_type_id` `type_id` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_name` `name` varchar(100) NOT NULL default '',
  CHANGE COLUMN `xar_title` `title` varchar(255) default NULL,
  CHANGE COLUMN `xar_content` `content` text NOT NULL,
  CHANGE COLUMN `xar_template` `template` varchar(255) default NULL,
  CHANGE COLUMN `xar_state` `state` tinyint(4) NOT NULL default '2',
  CHANGE COLUMN `xar_refresh` `refresh` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_last_update` `last_update` INTEGER NOT NULL default '0';

/* TODO: fix these keys */
  UNIQUE KEY `i_xar_block_instances_u2` (`xar_name`),
  KEY `i_xar_block_instances` (`xar_type_id`)


ALTER TABLE `xar_block_types`
  CHANGE COLUMN `xar_id` `id` INTEGER NOT NULL auto_increment,
  CHANGE COLUMN `xar_type` `type` varchar(64) NOT NULL default '',
  CHANGE COLUMN `xar_modid` `modid` int(10) unsigned NOT NULL default '0',
  CHANGE COLUMN `xar_info` `info` text;

/* TODO: fix these keys */
  UNIQUE KEY `i_xar_block_types2` (`xar_modid`,`xar_type`)

ALTER TABLE `xar_cache_blocks`
  CHANGE COLUMN `xar_bid` `id` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_nocache` `nocache` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_page` `page` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_user` `user` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_expire` `expire` INTEGER default NULL;

ALTER TABLE `xar_roles`
  CHANGE COLUMN `xar_uid` `id` INTEGER NOT NULL auto_increment,
  CHANGE COLUMN `xar_name` `name` varchar(255) NOT NULL default '',
  CHANGE COLUMN `xar_type` `type` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_users` `users` INTEGER NOT NULL default '0',
  CHANGE COLUMN `xar_uname` `uname` varchar(255) NOT NULL default '',
  CHANGE COLUMN `xar_email` `email` varchar(255) NOT NULL default '',
  CHANGE COLUMN `xar_pass` `pass` varchar(100) NOT NULL default '',
  CHANGE COLUMN `xar_date_reg` `date_reg` varchar(100) NOT NULL default '0000-00-00 00:00:00',
  CHANGE COLUMN `xar_valcode` `valcode` varchar(35) NOT NULL default '',
  CHANGE COLUMN `xar_state` `state` INTEGER NOT NULL default '3',
  CHANGE COLUMN `xar_auth_modid` `auth_modid` INTEGER NOT NULL default '0';

/* TODO: fix these keys */
  UNIQUE KEY `i_xar_roles_uname` (`xar_uname`),
  KEY `i_xar_roles_type` (`xar_type`),
  KEY `i_xar_roles_name` (`xar_name`),
  KEY `i_xar_roles_email` (`xar_email`),
  KEY `i_xar_roles_state` (`xar_state`)

ALTER TABLE `xar_rolemembers`
  CHANGE COLUMN `xar_uid` `id` INTEGER default NULL,
  CHANGE COLUMN `xar_parentid` `parentid` INTEGER default NULL;

/* TODO: fix these keys */
  KEY `i_xar_rolememb_uid` (`xar_uid`),
  KEY `i_xar_rolememb_parentid` (`xar_parentid`)

/* change date_reg to an int */
ALTER TABLE `xar_roles` CHANGE `date_reg` `date_reg` INT( 11 ) NOT NULL DEFAULT '0';

/* drop mode field from modules and themes table */
ALTER TABLE `xar_modules` DROP `mode`;
ALTER TABLE `xar_themes` DROP `mode`;

/* add a filepath to show where a class lives */
ALTER TABLE xar_dynamic_objects ADD COLUMN object_filepath VARCHAR(255) NOT NULL DEFAULT '';
