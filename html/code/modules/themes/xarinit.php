<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * Initialise the themes module
 * @author Marty Vance
 * @return boolean
 * @throws DATABASE_ERROR
 */
function themes_init()
{
    // Get database information
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        sys::import('xaraya.tableddl');
        xarXMLInstaller::createTable('table_schema-def', 'themes');
        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    xarModVars::set('themes', 'default_theme', 'default');
    xarModVars::set('themes', 'selsort', 'nameasc');
    // Make sure we dont miss empty variables (which were not passed thru)
    // FIXME: how would these values ever be passed in?
    if (empty($selstyle)) $selstyle = 'plain';
    // TODO: this is themes, not mods
    if (empty($selfilter)) $selfilter = xarMod::STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;
    xarModVars::set('themes', 'hidecore', $hidecore);
    xarModVars::set('themes', 'selstyle', $selstyle);
    xarModVars::set('themes', 'selfilter', $selfilter);
    xarModVars::set('themes', 'selclass', 'all');
    xarModVars::set('themes', 'useicons', false);
    xarModVars::set('themes', 'SiteName', 'Your Site Name');
    xarModVars::set('themes', 'SiteSlogan', 'Your Site Slogan');
    xarModVars::set('themes', 'SiteCopyRight', '&copy; Copyright 2013 ');
    xarModVars::set('themes', 'SiteTitleSeparator', ' :: ');
    xarModVars::set('themes', 'SiteTitleOrder', 'default');
    xarModVars::set('themes', 'SiteFooter', '<a href="http://www.xaraya.info"><img src="themes/common/images/xaraya.gif" alt="Powered by Xaraya" class="xar-noborder"/></a>');
    xarModVars::set('themes', 'ShowPHPCommentBlockInTemplates', false);
    xarModVars::set('themes', 'ShowTemplates', false);
    xarModVars::set('themes', 'variable_dump', false);
    xarModVars::set('themes', 'AtomTag', false);
    //Moved here in 1.1.x series
    xarModVars::set('themes', 'usedashboard', false);
    xarModVars::set('themes', 'dashtemplate', 'dashboard');
    xarModVars::set('themes', 'adminpagemenu', true);
    xarModVars::set('themes', 'userpagemenu', true);
    xarMasks::register('ViewThemes','All','themes','All','All','ACCESS_OVERVIEW');
    xarMasks::register('EditThemes','All','themes','All','All','ACCESS_EDIT');
    xarMasks::register('AddThemes','All','themes','All','All','ACCESS_ADD');
    xarMasks::register('ManageThemes','All','themes','All','All','ACCESS_DELETE');
    xarMasks::register('AdminThemes','All','themes','All','All','ACCESS_ADMIN');
    xarModVars::set('themes', 'selclass', 'all');
    xarModVars::set('themes', 'useicons', false);
    xarModVars::set('themes','flushcaches', '');
    xarModVars::set('themes', 'templcachepath', sys::varpath()."/cache/templates");
    // Installation complete; check for upgrades
    return themes_upgrade('2.0.0');
}
/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function themes_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        default:
      break;
    }
    return true;
}
/**
 * Delete this module
 *
 * @return boolean
 */
function themes_delete()
{
    // this module cannot be removed
    return false;
}
?>