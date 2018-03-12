<?php
/**
 * Initialisation functions for the security module
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */
sys::import('xaraya.tableddl');
 /**
 * Initialise the privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 *
 * @return boolean true on success, false on failure
 * @throws DATABASE_ERROR
 */
function privileges_init()
{
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        sys::import('xaraya.tableddl');
        xarXMLInstaller::createTable('table_schema-def', 'privileges');
        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    // Installation complete; check for upgrades
    return privileges_upgrade('2.0.0');
}
function privileges_activate()
{
    // On activation, set our variables
    xarModVars::set('privileges', 'showrealms', false);
    xarModVars::set('privileges', 'inheritdeny', true);
    xarModVars::set('privileges', 'tester', 0);
    xarModVars::set('privileges', 'test', false);
    xarModVars::set('privileges', 'testdeny', false);
    xarModVars::set('privileges', 'testmask', 'All');
    xarModVars::set('privileges', 'realmvalue', 'none');
    xarModVars::set('privileges', 'realmcomparison','exact');
    xarModVars::set('privileges', 'exceptionredirect', false);
    xarModVars::set('privileges', 'maskbasedsecurity', false);
    xarModVars::set('privileges', 'clearcache', time());
    return true;
}
/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function privileges_upgrade($oldversion)
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
function privileges_delete()
{
    // this module cannot be removed
    return false;
}
?>