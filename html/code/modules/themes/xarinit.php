<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @link http://xaraya.com/index.php/release/70.html
 */

// Load Table Maintainance API
sys::import('xaraya.tableddl');

/**
 * Initialise the themes module
 * @author Marty Vance
 * @return bool
 * @throws DATABASE_ERROR
 */
function themes_init()
{
    // Get database information
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();
    $tables['themes']     = $prefix . '_themes';

    // Create tables
    /**
     * Here we create all the tables for the theme system
     *
     * prefix_themes       - basic theme info
     */
    // prefix_themes
    /**
     * CREATE TABLE xar_themes (
     *   id integer unsigned NOT NULL auto_increment,
     *   name varchar(64) NOT NULL,
     *   regid int(10) INTEGER unsigned NOT NULL,
     *   directory varchar(64) NOT NULL,
     *   version varchar(10) NOT NULL,
     *   class int(10) INTEGER NOT NULL default '0',
     *   state tinyint(3) not null default '1'
     *   PRIMARY KEY  (id)
     * )
     */
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
    $fields = array(
        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
        'name' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
        'regid' => array('type' => 'integer', 'unsigned' => true, 'null' => false),
        'directory' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
        'version' => array('type' => 'varchar', 'size' => 10, 'null' => false, 'charset' => $charset),
        'state' => array('type' => 'integer', 'size' => 'tiny', 'unsigned'=> true, 'null' => false, 'default' => '1')
        );

    $query = xarDBCreateTable($tables['themes'], $fields);
    $result =& $dbconn->Execute($query);

    xarModVars::set('themes', 'default', 'default');
    xarModVars::set('themes', 'selsort', 'nameasc');

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
    xarModVars::set('themes', 'ShowPHPCommentBlockInTemplates', false);
    xarModVars::set('themes', 'ShowTemplates', false);
    xarModVars::set('themes', 'variable_dump', false);
    xarModVars::set('themes', 'AtomTag', false);
    //Moved here in 1.1.x series
    xarModVars::set('themes', 'usedashboard', false);
    xarModVars::set('themes', 'dashtemplate', 'dashboard');
    xarModVars::set('themes', 'adminpagemenu', true);

    xarRegisterMask('ViewThemes','All','themes','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditThemes','All','themes','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminTheme','All','themes','All','All','ACCESS_ADMIN');

    if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
        array('modName' => 'themes', 'blockType' => 'meta'))) return;

    // Ensure the meta blocktype is registered
    if(!xarMod::apiFunc('blocks','admin','block_type_exists',array('modName' => 'themes','blockType' => 'meta'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
                            array('modName' => 'themes',
                                  'blockType' => 'meta'))) return;
    }

    xarModVars::set('themes', 'selclass', 'all');
    xarModVars::set('themes', 'useicons', false);

    // Installation complete; check for upgrades
    return themes_upgrade('2.0.0');
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function themes_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return bool
 */
function themes_delete()
{
    // this module cannot be removed
    return false;
}

?>