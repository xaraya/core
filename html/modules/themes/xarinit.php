<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @link http://xaraya.com/index.php/release/70.html
 */

// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the themes module
 * Themes administration
 * @author Marty Vance
 * @return bool
 * @throws DATABASE_ERROR
 */
function themes_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables['themes'] = $systemPrefix . '_themes';
    $tables['theme_vars'] = $sitePrefix . '_theme_vars';

    // Create tables
    /**
     * Here we create all the tables for the theme system
     *
     * prefix_themes       - basic theme info
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
     *   xar_state int(11) not null defaul '1'
     *   PRIMARY KEY  (xar_id)
     * )
     */
    $fields = array(
        'xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
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
        'xar_class' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
        'xar_state' => array('type' => 'integer', 'null' => false, 'default' => '1')
        );

    $query = xarDBCreateTable($tables['themes'], $fields);
    $result =& $dbconn->Execute($query);

    xarModSetVar('themes', 'default', 'Xaraya_Classic');
    xarModSetVar('themes', 'selsort', 'nameasc');

    // Make sure we dont miss empty variables (which were not passed thru)
    // FIXME: how would these values ever be passed in?
    if (empty($selstyle)) $selstyle = 'plain';
    // TODO: this is themes, not mods
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;

    xarModVars::set('themes', 'hidecore', $hidecore);
    xarModVars::set('themes', 'selstyle', $selstyle);
    xarModVars::set('themes', 'selfilter', $selfilter);
    xarModVars::set('themes', 'selclass', 'all');
    xarModVars::set('themes', 'useicons', false);

    xarModVars::set('themes', 'SiteName', 'Your Site Name');
    xarModVars::set('themes', 'SiteSlogan', 'Your Site Slogan');
    xarModVars::set('themes', 'SiteCopyRight', '&copy; Copyright 2003 ');
    xarModVars::set('themes', 'SiteTitleSeparator', ' :: ');
    xarModVars::set('themes', 'SiteTitleOrder', 'default');
    xarModVars::set('themes', 'SiteFooter', '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" class="xar-noborder" /></a>');
    xarModVars::set('themes', 'ShowPHPCommentBlockInTemplates', 0);
    xarModVars::set('themes', 'ShowTemplates', 0);
    xarModVars::set('themes', 'var_dump', 0);
    //Moved here in 1.1.x series
    xarModVars::set('themes', 'usedashboard', 0);
    xarModVars::set('themes', 'dashtemplate', 'dashboard');
    xarModVars::set('themes', 'adminpagemenu', 0);

    xarRegisterMask('ViewThemes','All','themes','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('AdminTheme','All','themes','All','All','ACCESS_ADMIN');

    // Initialisation successful
    return themes_upgrade('1.0');
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
      case '1.7.0':

       xarModSetVar('themes', 'selclass', 'all');
       xarModSetVar('themes', 'useicons', false);

      case '1.8.0' : //current version

      break;
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
