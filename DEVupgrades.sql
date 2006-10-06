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
DROP INDEX i_xar_privmembers_id;
ALTER TABLE xar_privmembers ADD PRIMARY KEY (xar_pid,xar_parentid);
DROP INDEX i_xar_rolememb_id;
ALTER TABLE xar_rolemembers ADD PRIMARY KEY (xar_uid,xar_parentid);
DROP INDEX i_xar_security_acl_id;
ALTER TABLE xar_security_acl ADD PRIMARY KEY(xar_partid,xar_permid);

DROP INDEX i_xar_cache_blocks_1;
ALTER TABLE xar_cache_blocks ADD PRIMARY KEY (xar_bid);

DROP TABLE xar_security_levels;

/* Replace the data-list handler
UPDATE `xar_template_tags` SET `xar_handler` = 'dynamicdata_userapi_handleViewTag',
`xar_data` = 'O:14:"xarTemplateTag":12:{s:5:"_name";s:9:"data-list";s:11:"_attributes";a:0:{}s:8:"_handler";s:34:"dynamicdata_adminapi_handleListTag";s:7:"_module";s:11:"dynamicdata";s:5:"_type";s:4:"user";s:5:"_func";s:13:"handleViewTag";s:12:"_hasChildren";b:0;s:8:"_hasText";b:0;s:13:"_isAssignable";b:0;s:10:"_isPHPCode";b:1;s:15:"_needAssignment";b:0;s:14:"_needParameter";b:0;}' WHERE `xar_template_tags`.`xar_handler` = 'dynamicdata_userapi_handleViewTag';
