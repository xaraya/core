<?php

/**
 * Handle module installer functions
 *
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules;

use Xaraya\Modules\InstallerClass;
use Exception;
use xarEvents;
use xarHooks;
use ixarMod;
use xarSystemVars;
use xarTableDDL;
use xarXMLInstaller;
use sys;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @internal replaced modules_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the modules module
     * @return bool|void
     */
    public function init()
    {
        // Create tables inside a transaction
        $dbconn = $this->db()->getConn();

        try {
            $dbconn->begin();
            sys::import('xaraya.tableddl');
            xarXMLInstaller::createTable('table_schema-def', 'modules');
            // We're done, commit
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }

        // Get database information
        $tables = $this->db()->getTables();
        try {
            $dbconn->begin();
            // Manually Insert the Base and Modules module into modules table
            $query = "INSERT INTO " . $tables['modules'] . "
                  (name, regid, directory, version,
                   class, category, admin_capable, user_capable, state )
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $modInfo = $this->mod()->getFileInfo('modules');
            if (empty($modInfo)) {
                return;
            } // throw back
            // Use version, since that's the only info likely to change
            $modVersion = $modInfo['version'];
            $bindvars = ['modules',1,'modules',(string) $modVersion,'Core Admin','System',true,false,3];
            $dbconn->Execute($query, $bindvars);
            $modInfo = $this->mod()->getFileInfo('base');
            if (empty($modInfo)) {
                return;
            } // throw back
            // Use version, since that's the only info likely to change
            $modVersion = $modInfo['version'];
            $bindvars = ['base',68,'base',(string) $modVersion,'Core Admin','System',true,true,3];
            $dbconn->Execute($query, $bindvars);
            $modulesmodid = $this->mod()->getID('modules');
            $sql = "INSERT INTO " . $tables['module_vars'] . " (module_id, name, value)
                    VALUES (?,?,?)";
            $stmt = $dbconn->prepareStatement($sql);
            $modvars = [
                // default show-hide core modules
                [$modulesmodid,'hidecore','0'],
                // default regenerate command
                [$modulesmodid,'regen','0'],
                // default style of module list
                [$modulesmodid,'selstyle','plain'],
                // default filtering based on module states
                [$modulesmodid,'selfilter', '0'],
                // default modules list sorting order
                [$modulesmodid,'selsort','nameasc'],
                // default show-hide modules statistics
                [$modulesmodid,'hidestats','0'],
                // default maximum number of modules listed per page
                [$modulesmodid,'selmax','all'],
                // default start page
                [$modulesmodid,'startpage','overview'],
                // disable overviews
                [$modulesmodid,'disableoverview',false],
                // expertlist
                [$modulesmodid,'expertlist','0'],
                // the configuration settings pertaining to modules for the base module
                [$modulesmodid,'defaultmoduletype','user'],
                [$modulesmodid,'defaultmodule','base'],
                [$modulesmodid,'defaultmodulefunction','main'],
                [$modulesmodid,'defaultdatapath','lib/']];
            foreach ($modvars as &$modvar) {
                $stmt->executeUpdate($modvar);
            }
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }
        // Installation complete; check for upgrades
        return $this->upgrade('2.0.1');
    }

    public function activate()
    {
        // make sure we dont miss empty variables (which were not passed thru)
        $selstyle = $this->mod()->getVar('hidecore');
        $selstyle = $this->mod()->getVar('selstyle');
        $selstyle = $this->mod()->getVar('selfilter');
        $selstyle = $this->mod()->getVar('selsort');
        if (empty($hidecore)) {
            $this->mod()->setVar('hidecore', 0);
        }
        if (empty($selstyle)) {
            $this->mod()->setVar('selstyle', 'plain');
        }
        if (empty($selfilter)) {
            $this->mod()->setVar('selfilter', ixarMod::STATE_ANY);
        }
        if (empty($selsort)) {
            $this->mod()->setVar('selsort', 'nameasc');
        }
        // New in 1.1.x series but not used
        $this->mod()->setVar('disableoverview', 0);
        return true;
    }

    public function upgrade($oldversion)
    {
        switch ($oldversion) {
            case '2.0.0':
                $dbconn = $this->db()->getConn();
                $xartable = $this->db()->getTables();
                //Load Table Maintainance API
                sys::import('xaraya.tableddl');
                $hookstable = $xartable['hooks'];
                $charset = $this->sysConfig()->getVar('DB.Charset');
                $fieldargs = ['command' => 'add', 'field' => 't_file', 'type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset];
                $query = xarTableDDL::alterTable($hookstable, $fieldargs);
                $result = $dbconn->Execute($query);
                if (!$result) {
                    return false;
                }
                // no break
            case '2.0.1':
                $dbconn = $this->db()->getConn();
                $tables = ['eventsystem' => $this->db()->getPrefix() . '_eventsystem'];
                $this->db()->importTables($tables);
                // Register base module event subjects
                // Base module inits before modules, so we have to register events for it here
                $this->events()->registerSubject('Event', 'event', 'base');
                $this->events()->registerSubject('ServerRequest', 'server', 'base');
                $this->events()->registerSubject('SessionCreate', 'session', 'base');
                // Register base module event observers
                $this->events()->registerObserver('Event', 'base');
                // Register modules module event subjects
                $this->events()->registerSubject('ModLoad', 'module', 'modules');
                $this->events()->registerSubject('ModApiLoad', 'module', 'modules');
                // Register modules module hook subjects
                $this->hooked()->registerSubject('ModuleModifyconfig', 'module', 'modules');
                $this->hooked()->registerSubject('ModuleUpdateconfig', 'module', 'modules');
                $this->hooked()->registerSubject('ModuleRemove', 'module', 'modules');
                $this->hooked()->registerSubject('ModuleInit', 'module', 'modules');
                $this->hooked()->registerSubject('ModuleActivate', 'module', 'modules');
                $this->hooked()->registerSubject('ModuleUpgrade', 'module', 'modules');
                // Module itemtype hook subjects
                $this->hooked()->registerSubject('ItemtypeCreate', 'itemtype', 'modules');
                $this->hooked()->registerSubject('ItemtypeDelete', 'itemtype', 'modules');
                $this->hooked()->registerSubject('ItemtypeView', 'itemtype', 'modules');
                // Module item hook subjects (@TODO: these should no longer apply to roles)
                $this->hooked()->registerSubject('ItemNew', 'item', 'modules');
                $this->hooked()->registerSubject('ItemCreate', 'item', 'modules');
                $this->hooked()->registerSubject('ItemModify', 'item', 'modules');
                $this->hooked()->registerSubject('ItemUpdate', 'item', 'modules');
                $this->hooked()->registerSubject('ItemDisplay', 'item', 'modules');
                $this->hooked()->registerSubject('ItemDelete', 'item', 'modules');
                $this->hooked()->registerSubject('ItemSubmit', 'item', 'modules');
                // Transform hooks
                // @TODO: these really need to go away...
                $this->hooked()->registerSubject('ItemTransform', 'item', 'modules');
                $this->hooked()->registerSubject('ItemTransforminput', 'item', 'modules');
                // @TODO: these need evaluating
                $this->hooked()->registerSubject('ItemFormheader', 'item', 'modules');
                $this->hooked()->registerSubject('ItemFormaction', 'item', 'modules');
                $this->hooked()->registerSubject('ItemFormdisplay', 'item', 'modules');
                $this->hooked()->registerSubject('ItemFormarea', 'item', 'modules');
                // Register base module hook subjects
                $this->hooked()->registerSubject('ItemWaitingcontent', 'item', 'base');
                // NOTE: UserLogin and UserLogout are registered by authsystem module
                // @TODO: ItemSearch is registered by search module = gone
                // @TODO: Roles module to register User* and Group* event subjects
                // no break
            case '2.2.0':
                // Register modules module event subjects
                $this->events()->registerSubject('ModInitialise', 'module', 'modules');
                $this->events()->registerSubject('ModActivate', 'module', 'modules');
                $this->events()->registerSubject('ModDeactivate', 'module', 'modules');
                $this->events()->registerSubject('ModRemove', 'module', 'modules');
                // Register modules module event observers
                $this->events()->registerObserver('ModInitialise', 'modules');
                $this->events()->registerObserver('ModActivate', 'modules');
                $this->events()->registerObserver('ModDeactivate', 'modules');
                $this->events()->registerObserver('ModRemove', 'modules');
                // no break
            case '2.3.0':
                break;
        }
        return true;
    }

    public function delete()
    {
        // this module cannot be removed
        return false;
    }
}
