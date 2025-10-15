<?php

/**
 * Handle module installer functions
 *
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges;

use Xaraya\Modules\InstallerClass;
use Exception;
use xarXMLInstaller;
use sys;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @internal replaced privileges_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the privileges module
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool true on success, false on failure
     */
    public function init()
    {
        $dbconn = $this->db()->getConn();
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
        return $this->upgrade('2.0.0');
    }

    public function activate()
    {
        // On activation, set our variables
        $this->mod()->setVar('showrealms', false);
        $this->mod()->setVar('inheritdeny', true);
        $this->mod()->setVar('tester', 0);
        $this->mod()->setVar('test', false);
        $this->mod()->setVar('testdeny', false);
        $this->mod()->setVar('testmask', 'All');
        $this->mod()->setVar('realmvalue', 'none');
        $this->mod()->setVar('realmcomparison', 'exact');
        $this->mod()->setVar('exceptionredirect', false);
        $this->mod()->setVar('maskbasedsecurity', false);
        $this->mod()->setVar('clearcache', time());
        return true;
    }

    /**
     * Upgrade this module from an old version
     * @param string $oldversion
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
