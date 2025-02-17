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
use xarDB;
use xarModVars;
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
            //'privileges_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the privileges module
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return bool true on success, false on failure
     */
    public function init()
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
        return $this->upgrade('2.0.0');
    }

    public function activate()
    {
        // On activation, set our variables
        xarModVars::set('privileges', 'showrealms', false);
        xarModVars::set('privileges', 'inheritdeny', true);
        xarModVars::set('privileges', 'tester', 0);
        xarModVars::set('privileges', 'test', false);
        xarModVars::set('privileges', 'testdeny', false);
        xarModVars::set('privileges', 'testmask', 'All');
        xarModVars::set('privileges', 'realmvalue', 'none');
        xarModVars::set('privileges', 'realmcomparison', 'exact');
        xarModVars::set('privileges', 'exceptionredirect', false);
        xarModVars::set('privileges', 'maskbasedsecurity', false);
        xarModVars::set('privileges', 'clearcache', time());
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
