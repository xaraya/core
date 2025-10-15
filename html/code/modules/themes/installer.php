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
use xarMasks;
use xarMod;
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
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the themes module
     * @author Marty Vance
     * @return bool
     */
    public function init()
    {
        // Get database information
        $dbconn = $this->db()->getConn();
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
        $this->mod()->setVar('default_theme', 'default');
        $this->mod()->setVar('selsort', 'nameasc');
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
        $this->mod()->setVar('hidecore', $hidecore);
        $this->mod()->setVar('selstyle', $selstyle);
        $this->mod()->setVar('selfilter', $selfilter);
        $this->mod()->setVar('selclass', 'all');
        $this->mod()->setVar('useicons', false);
        $this->mod()->setVar('SiteName', 'Your Site Name');
        $this->mod()->setVar('SiteSlogan', 'Your Site Slogan');
        $this->mod()->setVar('SiteCopyRight', '&copy; Copyright 2013 ');
        $this->mod()->setVar('SiteTitleSeparator', ' :: ');
        $this->mod()->setVar('SiteTitleOrder', 'default');
        $this->mod()->setVar('SiteFooter', '<a href="http://www.xaraya.info"><img src="themes/common/images/xaraya.gif" alt="Powered by Xaraya" class="xar-noborder"/></a>');
        $this->mod()->setVar('ShowPHPCommentBlockInTemplates', false);
        $this->mod()->setVar('ShowTemplates', false);
        $this->mod()->setVar('variable_dump', false);
        $this->mod()->setVar('AtomTag', false);
        //Moved here in 1.1.x series
        $this->mod()->setVar('usedashboard', false);
        $this->mod()->setVar('dashtemplate', 'dashboard');
        $this->mod()->setVar('adminpagemenu', true);
        $this->mod()->setVar('userpagemenu', true);
        xarMasks::register('ViewThemes', 'All', 'themes', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('EditThemes', 'All', 'themes', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddThemes', 'All', 'themes', 'All', 'All', 'ACCESS_ADD');
        xarMasks::register('ManageThemes', 'All', 'themes', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminThemes', 'All', 'themes', 'All', 'All', 'ACCESS_ADMIN');
        $this->mod()->setVar('selclass', 'all');
        $this->mod()->setVar('useicons', false);
        $this->mod()->setVar('flushcaches', '');
        $this->mod()->setVar('templcachepath', sys::varpath() . "/cache/templates");
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
