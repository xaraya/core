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
use xarDB;
use xarEvents;
use xarHooks;
use xarMod;
use xarModVars;
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
        $dbconn = xarDB::getConn();

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
        $tables = xarDB::getTables();
        try {
            $dbconn->begin();
            // Manually Insert the Base and Modules module into modules table
            $query = "INSERT INTO " . $tables['modules'] . "
                  (name, regid, directory, version,
                   class, category, admin_capable, user_capable, state )
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $modInfo = xarMod::getFileInfo('modules');
            if (empty($modInfo)) {
                return;
            } // throw back
            // Use version, since that's the only info likely to change
            $modVersion = $modInfo['version'];
            $bindvars = ['modules',1,'modules',(string) $modVersion,'Core Admin','System',true,false,3];
            $dbconn->Execute($query, $bindvars);
            $modInfo = xarMod::getFileInfo('base');
            if (empty($modInfo)) {
                return;
            } // throw back
            // Use version, since that's the only info likely to change
            $modVersion = $modInfo['version'];
            $bindvars = ['base',68,'base',(string) $modVersion,'Core Admin','System',true,true,3];
            $dbconn->Execute($query, $bindvars);
            $modulesmodid = xarMod::getID('modules');
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
        $selstyle = xarModVars::get('modules', 'hidecore');
        $selstyle = xarModVars::get('modules', 'selstyle');
        $selstyle = xarModVars::get('modules', 'selfilter');
        $selstyle = xarModVars::get('modules', 'selsort');
        if (empty($hidecore)) {
            xarModVars::set('modules', 'hidecore', 0);
        }
        if (empty($selstyle)) {
            xarModVars::set('modules', 'selstyle', 'plain');
        }
        if (empty($selfilter)) {
            xarModVars::set('modules', 'selfilter', xarMod::STATE_ANY);
        }
        if (empty($selsort)) {
            xarModVars::set('modules', 'selsort', 'nameasc');
        }
        // New in 1.1.x series but not used
        xarModVars::set('modules', 'disableoverview', 0);
        return true;
    }

    public function upgrade($oldversion)
    {
        switch ($oldversion) {
            case '2.0.0':
                $dbconn = xarDB::getConn();
                $xartable = xarDB::getTables();
                //Load Table Maintainance API
                sys::import('xaraya.tableddl');
                $hookstable = $xartable['hooks'];
                $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
                $fieldargs = ['command' => 'add', 'field' => 't_file', 'type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset];
                $query = xarTableDDL::alterTable($hookstable, $fieldargs);
                $result = $dbconn->Execute($query);
                if (!$result) {
                    return false;
                }
                // no break
            case '2.0.1':
                $dbconn = xarDB::getConn();
                $tables = ['eventsystem' => xarDB::getPrefix() . '_eventsystem'];
                xarDB::importTables($tables);
                // Register base module event subjects
                // Base module inits before modules, so we have to register events for it here
                xarEvents::registerSubject('Event', 'event', 'base');
                xarEvents::registerSubject('ServerRequest', 'server', 'base');
                xarEvents::registerSubject('SessionCreate', 'session', 'base');
                // Register base module event observers
                xarEvents::registerObserver('Event', 'base');
                // Register modules module event subjects
                xarEvents::registerSubject('ModLoad', 'module', 'modules');
                xarEvents::registerSubject('ModApiLoad', 'module', 'modules');
                // Register modules module hook subjects
                xarHooks::registerSubject('ModuleModifyconfig', 'module', 'modules');
                xarHooks::registerSubject('ModuleUpdateconfig', 'module', 'modules');
                xarHooks::registerSubject('ModuleRemove', 'module', 'modules');
                xarHooks::registerSubject('ModuleInit', 'module', 'modules');
                xarHooks::registerSubject('ModuleActivate', 'module', 'modules');
                xarHooks::registerSubject('ModuleUpgrade', 'module', 'modules');
                // Module itemtype hook subjects
                xarHooks::registerSubject('ItemtypeCreate', 'itemtype', 'modules');
                xarHooks::registerSubject('ItemtypeDelete', 'itemtype', 'modules');
                xarHooks::registerSubject('ItemtypeView', 'itemtype', 'modules');
                // Module item hook subjects (@TODO: these should no longer apply to roles)
                xarHooks::registerSubject('ItemNew', 'item', 'modules');
                xarHooks::registerSubject('ItemCreate', 'item', 'modules');
                xarHooks::registerSubject('ItemModify', 'item', 'modules');
                xarHooks::registerSubject('ItemUpdate', 'item', 'modules');
                xarHooks::registerSubject('ItemDisplay', 'item', 'modules');
                xarHooks::registerSubject('ItemDelete', 'item', 'modules');
                xarHooks::registerSubject('ItemSubmit', 'item', 'modules');
                // Transform hooks
                // @TODO: these really need to go away...
                xarHooks::registerSubject('ItemTransform', 'item', 'modules');
                xarHooks::registerSubject('ItemTransforminput', 'item', 'modules');
                // @TODO: these need evaluating
                xarHooks::registerSubject('ItemFormheader', 'item', 'modules');
                xarHooks::registerSubject('ItemFormaction', 'item', 'modules');
                xarHooks::registerSubject('ItemFormdisplay', 'item', 'modules');
                xarHooks::registerSubject('ItemFormarea', 'item', 'modules');
                // Register base module hook subjects
                xarHooks::registerSubject('ItemWaitingcontent', 'item', 'base');
                // NOTE: UserLogin and UserLogout are registered by authsystem module
                // NOTE: ItemSearch is registered by search module
                // @TODO: Roles module to register User* and Group* event subjects
                // no break
            case '2.2.0':
                // Register modules module event subjects
                xarEvents::registerSubject('ModInitialise', 'module', 'modules');
                xarEvents::registerSubject('ModActivate', 'module', 'modules');
                xarEvents::registerSubject('ModDeactivate', 'module', 'modules');
                xarEvents::registerSubject('ModRemove', 'module', 'modules');
                // Register modules module event observers
                xarEvents::registerObserver('ModInitialise', 'modules');
                xarEvents::registerObserver('ModActivate', 'modules');
                xarEvents::registerObserver('ModDeactivate', 'modules');
                xarEvents::registerObserver('ModRemove', 'modules');
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
