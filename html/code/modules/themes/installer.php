<?php

/**
 * Handle module installer functions
 *
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes;

use Xaraya\Modules\InstallerClass;
use Exception;
use xarDB;
use xarMasks;
use xarMod;
use xarModVars;
use xarXMLInstaller;
use sys;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @internal replaced themes_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /**
     * Configure this module - override this method
     *
     * @todo use this instead of init() etc. for standard installation
     * @return void
     */
    public function configure()
    {
        $this->objects = [
            // add your DD objects here
            //'themes_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the themes module
     * @author Marty Vance
     * @return bool
     */
    public function init()
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
        if (empty($selstyle)) {
            $selstyle = 'plain';
        }
        // TODO: this is themes, not mods
        if (empty($selfilter)) {
            $selfilter = xarMod::STATE_ANY;
        }
        if (empty($hidecore)) {
            $hidecore = 0;
        }
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
        xarMasks::register('ViewThemes', 'All', 'themes', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('EditThemes', 'All', 'themes', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddThemes', 'All', 'themes', 'All', 'All', 'ACCESS_ADD');
        xarMasks::register('ManageThemes', 'All', 'themes', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminThemes', 'All', 'themes', 'All', 'All', 'ACCESS_ADMIN');
        xarModVars::set('themes', 'selclass', 'all');
        xarModVars::set('themes', 'useicons', false);
        xarModVars::set('themes', 'flushcaches', '');
        xarModVars::set('themes', 'templcachepath', sys::varpath() . "/cache/templates");
        // Installation complete; check for upgrades
        return $this->upgrade('2.0.0');
    }

    /**
     * Upgrade this module from an old version
     * @param string oldversion
     * @return bool true on success, false on failure
     */
    public function upgrade($oldversion)
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
     * @return bool
     */
    public function delete()
    {
        // this module cannot be removed
        return false;
    }
}
