<?php
/**
 * Module initialization functions
 *
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Initialise the modules module
 *
 * @return boolean|void
 */
function modules_init()
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
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];
        $bindvars = array('modules',1,'modules',(string) $modVersion,'Core Admin','System',true,false,3);
        $dbconn->Execute($query,$bindvars);
        $modInfo = xarMod::getFileInfo('base');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];
        $bindvars = array('base',68,'base',(string) $modVersion,'Core Admin','System',true,true,3);
        $dbconn->Execute($query,$bindvars);
        $modulesmodid = xarMod::getID('modules');
        $sql = "INSERT INTO " . $tables['module_vars'] . " (module_id, name, value)
                VALUES (?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);
        $modvars = array(
                         // default show-hide core modules
                         array($modulesmodid,'hidecore','0'),
                         // default regenerate command
                         array($modulesmodid,'regen','0'),
                         // default style of module list
                         array($modulesmodid,'selstyle','plain'),
                         // default filtering based on module states
                         array($modulesmodid,'selfilter', '0'),
                         // default modules list sorting order
                         array($modulesmodid,'selsort','nameasc'),
                         // default show-hide modules statistics
                         array($modulesmodid,'hidestats','0'),
                         // default maximum number of modules listed per page
                         array($modulesmodid,'selmax','all'),
                         // default start page
                         array($modulesmodid,'startpage','overview'),
                         // disable overviews
                         array($modulesmodid,'disableoverview',false),
                         // expertlist
                         array($modulesmodid,'expertlist','0'),
                         // the configuration settings pertaining to modules for the base module
                         array($modulesmodid,'defaultmoduletype','user'),
                         array($modulesmodid,'defaultmodule','base'),
                         array($modulesmodid,'defaultmodulefunction','main'),
                         array($modulesmodid,'defaultdatapath','lib/'));
        foreach($modvars as &$modvar) {
            $stmt->executeUpdate($modvar);
        }
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    // Installation complete; check for upgrades
    return modules_upgrade('2.0.1');
}
function modules_activate()
{
    // make sure we dont miss empty variables (which were not passed thru)
    $selstyle = xarModVars::get('modules', 'hidecore');
    $selstyle = xarModVars::get('modules', 'selstyle');
    $selstyle = xarModVars::get('modules', 'selfilter');
    $selstyle = xarModVars::get('modules', 'selsort');
    if (empty($hidecore))  xarModVars::set('modules', 'hidecore', 0);
    if (empty($selstyle))  xarModVars::set('modules', 'selstyle', 'plain');
    if (empty($selfilter)) xarModVars::set('modules', 'selfilter', xarMod::STATE_ANY);
    if (empty($selsort))   xarModVars::set('modules', 'selsort', 'nameasc');
    // New in 1.1.x series but not used
    xarModVars::set('modules', 'disableoverview',0);
    return true;
}
function modules_upgrade($oldversion)
{
    switch ($oldversion) {
        case '2.0.0':
            $dbconn = xarDB::getConn();
            $xartable = xarDB::getTables();
            //Load Table Maintainance API
            sys::import('xaraya.tableddl');
            $hookstable = $xartable['hooks'];
            $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
            $fieldargs = array('command' => 'add', 'field' => 't_file', 'type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset);
            $query = xarTableDDL::alterTable($hookstable,$fieldargs);
            $result = $dbconn->Execute($query);
            if (!$result) return;
        case '2.0.1':
            $dbconn = xarDB::getConn();
            $tables = array('eventsystem' => xarDB::getPrefix() . '_eventsystem');
            xarDB::importTables($tables);
            $tables = xarDB::getTables();
            $prefix = xarDB::getPrefix();
            // Creating the first part inside a transaction
            try {
                $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
                $dbconn->begin();
                // Let's commit this, since we're gonna do some other stuff
                $dbconn->commit();
            } catch (Exception $e) {
                $dbconn->rollback();
                throw $e;
            }
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
        case '2.3.0':
            break;
    }
    return true;
}
function modules_delete()
{
    // this module cannot be removed
    return false;
}
