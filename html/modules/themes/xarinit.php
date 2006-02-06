<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */

// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the themes module
 * Themes administration
 * @author Marty Vance
 * @param none $
 * @returns bool
 * @raise DATABASE_ERROR
 */
function themes_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables['themes'] = $systemPrefix . '_themes';
    $tables['theme_states'] = $sitePrefix . '_theme_states';
    $tables['theme_vars'] = $sitePrefix . '_theme_vars';
    // Create tables
    /**
     * Here we create all the tables for the theme system
     *
     * prefix_themes       - basic theme info
     * prefix_theme_states - table to hold states for unshared themes
     * prefix_theme_vars   - theme variables table
     */
    // prefix_themes
    /**
     * CREATE TABLE xar_themes (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_regid int(10) unsigned NOT NULL default '0',
     *   xar_directory varchar(64) NOT NULL default '',
     *   xar_mode smallint(6) NOT NULL default '1',
     *   xar_author varchar(64) NOT NULL default '',
     *   xar_homepage varchar(64) NOT NULL default '',
     *   xar_email varchar(64) NOT NULL default '',
     *   xar_description varchar(255) NOT NULL default '',
     *   xar_contactinfo varchar(255) NOT NULL default '',
     *   xar_publishdate varchar(32) NOT NULL default '',
     *   xar_license varchar(255) NOT NULL default '',
     *   xar_version varchar(10) NOT NULL default '',
     *   xar_xaraya_version varchar(10) NOT NULL default '',
     *   xar_bl_version varchar(10) NOT NULL default '',
     *   xar_class int(10) unsigned NOT NULL default '0',
     *   PRIMARY KEY  (xar_id)
     * )TYPE=MyISAM;
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_regid' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
        'xar_directory' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_mode' => array('type' => 'integer', 'null' => false, 'default' => '1'),
        'xar_author' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_homepage' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_email' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_description' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_contactinfo' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_publishdate' => array('type' => 'varchar', 'size' => 32, 'null' => false),
        'xar_license' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_xaraya_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_bl_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_class' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['themes'], $fields);
    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // prefix_theme_states
    /**
     * CREATE TABLE xar_theme_states (
     *   xar_regid int(10) unsigned NOT NULL default '0',
     *   xar_state int(11) NOT NULL default '1'
     * ) TYPE=MyISAM;
     */
    $fields = array(
                    'xar_regid' => array('type' => 'integer', 'null' => false, 'unsigned' => true, 'primary_key' => false),
                    'xar_state' => array('type' => 'integer', 'null' => false, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['theme_states'], $fields);
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    xarModSetVar('themes', 'default', 'Xaraya_Classic');
    xarModSetVar('themes', 'selsort', 'nameasc');

    // Make sure we dont miss empty variables (which were not passed thru)
    // FIXME: how would these values ever be passed in?
    if (empty($selstyle)) $selstyle = 'plain';
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;
    if (empty($selsort)) $selsort = 'namedesc';

    xarModSetVar('themes', 'hidecore', $hidecore);
    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);

    xarModSetVar('themes', 'SiteName', 'Your Site Name');
    xarModSetVar('themes', 'SiteSlogan', 'Your Site Slogan');
    xarModSetVar('themes', 'SiteCopyRight', '&copy; Copyright 2003 ');
    xarModSetVar('themes', 'SiteTitleSeparator', ' :: ');
    xarModSetVar('themes', 'SiteTitleOrder', 'default');
    xarModSetVar('themes', 'SiteFooter', '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" class="xar-noborder" /></a>');
    xarModSetVar('themes', 'ShowPHPCommentBlockInTemplates', 0);
    xarModSetVar('themes', 'ShowTemplates', 0);

    // Register theme tags.

    // register complete set of css tags is now encapsulated in the module's api function
    if(!xarModAPIFunc('themes', 'css', 'registercsstags', array())) {
        return false;
    }


    // Set up usermenu hook
    if (!xarModRegisterHook('item', 'usermenu', 'GUI', 'themes', 'user', 'usermenu')) {
        return false;
    }

    // Register the meta blocktype
    if (!xarModAPIFunc('blocks', 'admin', 'register_block_type',
                        array('modName' => 'themes',
                              'blockType' => 'meta'))) return;
    // Register the skins blocktype
    if (!xarModAPIFunc('blocks', 'admin', 'register_block_type',
                        array('modName' => 'themes',
                              'blockType' => 'skin'))) return;

    // Initialisation successful
    return true;
}

/**
 * Upgrade the themes theme from an old version
 *
 * @param oldversion $ the old version to upgrade from
 * @returns bool
 */
function themes_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '1.0':
            if (!xarModRegisterHook('item', 'usermenu', 'GUI', 'themes', 'user', 'usermenu')) {
                return false;
            }

        case '1.1':
            if (!xarModAPIFunc('blocks', 'admin', 'register_block_type',
                array('modName' => 'themes', 'blockType' => 'meta'))) return;

        case '1.2':
        case '1.3.0':
            // Register additional styles tag.
            // This is for bug 3868 only - available to those that want to use it, but
            // not a permanent replacement for the additional styles global or corecss.

            // register complete set of css tags is now encapsulated in the module's api function
            if(!xarModAPIFunc('themes', 'css', 'registercsstags', array())) {
                return false;
            }

            // Ensure the meta blocktype is registered
            if(!xarModAPIFunc('blocks','admin','block_type_exists',array('modName' => 'themes','blockType' => 'meta'))) {
                if (!xarModAPIFunc('blocks', 'admin', 'register_block_type',
                                    array('modName' => 'themes',
                                          'blockType' => 'meta'))) return;
            }

    }
    // Update successful
    return true;
}

/**
 * Delete the themes theme
 *
 * @param none $
 * @returns bool
 */
function themes_delete()
{
    // this module cannot be removed
    return false;
}

?>
