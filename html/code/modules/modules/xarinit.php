<?php
/**
 * Module initialization functions
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
// Load Table Maintainance API
sys::import('xaraya.tableddl');
/**
 * Initialise the modules module
 *
 * @return boolean
 * @throws DATABASE_ERROR
 */
function modules_init()
{
    // Get database information
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();

    $tables['modules'] = $prefix . '_modules';
    $tables['module_vars'] = $prefix . '_module_vars';
    $tables['module_itemvars'] = $prefix . '_module_itemvars';
    $tables['hooks'] = $prefix . '_hooks';
    $tables['eventsystem'] = $prefix . '_eventsystem';
    // Create tables
    // This should either go, or fail competely
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();
        /**
         * Here we create all the tables for the module system
         *
         * prefix_modules       - basic module info
         * prefix_module_vars   - module variables table
         * prefix_hooks         - table for hooks
         */
        // prefix_modules
        /**
         * CREATE TABLE xar_modules (
         *   id int(11) NOT NULL auto_increment,
         *   name varchar(64) NOT NULL,
         *   regid int(10) integer unsigned NOT NULL,
         *   directory varchar(64) NOT NULL,
         *   version varchar(10) NOT NULL default '0',
         *   class varchar(64) NOT NULL,
         *   category varchar(64) NOT NULL,
         *   admin_capable INTEGER NOT NULL default '0',
         *   user_capable INTEGER NOT NULL default '0',
         *   state INTEGER NOT NULL default '0'
         *   PRIMARY KEY  (id)
         * )
         */
        $fields = array(
                        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                        'name' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        'regid' => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
                        'directory' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        'version' => array('type' => 'varchar', 'size' => 10, 'null' => false, 'charset' => $charset),
                        'class' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        'category' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        'admin_capable' => array('type' => 'boolean', 'default' => false),
                        'user_capable' => array('type' => 'boolean', 'default' => false),
                        'state' => array('type' => 'integer', 'size' => 'tiny','unsigned'=>true, 'null' => false, 'default' => '1')
                        );

        // Create the modules table
        $query = xarDBCreateTable($tables['modules'], $fields);
        $dbconn->Execute($query);

        // Manually Insert the Base and Modules module into modules table
        $query = "INSERT INTO " . $tables['modules'] . "
              (name, regid, directory, version,
               class, category, admin_capable, user_capable, state )
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $modInfo = xarMod_getFileInfo('modules');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];
        $bindvars = array('modules',1,'modules',(string) $modVersion,'Core Admin','System',true,false,3);
        $dbconn->Execute($query,$bindvars);

        $modInfo = xarMod_getFileInfo('base');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];

        $bindvars = array('base',68,'base',(string) $modVersion,'Core Admin','System',true,true,3);
        $dbconn->Execute($query,$bindvars);

        /** Module vars table is created earlier now (base mod, where config_vars table was created */

        /**
         * CREATE TABLE module_itemvars (
         *   module_var_id    integer unsigned NOT NULL,
         *   item_id          integer unsigned NOT NULL,
         *   value            longtext,
         *   PRIMARY KEY      (module_var_id, item_id)
         * )
         */
        $fields = array(
                        'module_var_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'primary_key' => true),
                        'item_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'unsigned' => true, 'primary_key' => true),
                        'value' => array('type' => 'text', 'size' => 'long', 'charset' => $charset)
                        );

        // Create the module itemvars table
        $query = xarDBCreateTable($tables['module_itemvars'], $fields);
        $dbconn->Execute($query);

        /**
         * CREATE TABLE xar_hooks (
         *   id         integer NOT NULL auto_increment,
         *   object     varchar(64) NOT NULL,
         *   action     varchar(64) NOT NULL,
         *   s_module_id integer unsigned default null,
         *   s_type      varchar(64) NOT NULL,
         *   t_area      varchar(64) NOT NULL,
         *   t_module_id integer unsigned not null,
         *   t_type      varchar(64) NOT NULL,
         *   t_func      varchar(64) NOT NULL,
         *   t_file      varchar(254) NOT NULL,
         *   priority    integer default 0
         *   PRIMARY KEY (id)
         * )
         *
        $fields = array(
                        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                        'object'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        'action'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        's_module_id' => array('type' => 'integer', 'unsigned' => true, 'null' => true, 'default' => null),
                        // TODO: switch to integer for itemtype (see also xarMod.php)
                        's_type'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        't_area'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        't_module_id'  => array('type' => 'integer','unsigned' => true, 'null' => false),
                        't_type'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        't_func'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                        't_file'      => array('type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset),
                        'priority'       => array('type' => 'integer', 'size' => 'tiny', 'unsigned' => true, 'null' => false, 'default' => '0')
                   );
        */
        // TODO: no indexes?


        /**
         * CREATE TABLE xar_hooks (
         *   observer   integer unsigned NOT NULL,
         *   subject    integer unsigned NOT NULL,
         *   itemtype   integer unsigned NOT NULL
        **/
        $fields = array(
            'observer' => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
            'subject'  => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
            'itemtype' => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
            'scope'    => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
        );
        // each entry should be unique
        $index = array(
            'name'   => 'i_'.$prefix.'_hooks',
            'fields' => array('observer', 'subject', 'itemtype', 'scope'),
            'unique' => true
        );
        // Create the hooks table
        $query = xarDBCreateTable($tables['hooks'], $fields);
        $dbconn->Execute($query);

        // <andyv> Add module variables for default user/admin, used in modules list
        /**
         * at this stage of installer mod vars cannot be set, so we use DB calls
         * prolly need to move this closer to installer, not sure yet
         */

        $sql = "INSERT INTO " . $tables['module_vars'] . " (module_id, name, value)
                VALUES (?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $modulesmodid = xarMod::getID('modules');
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
        // We're done, thanks, commit the thingie
        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        throw $e;
    }

    // Installation complete; check for upgrades
    return modules_upgrade('2.0.1');
}

/**
 * Activates the modules module
 *
 * @return boolean true on success, false on failure
 */
function modules_activate()
{
    // make sure we dont miss empty variables (which were not passed thru)
    $selstyle = xarModVars::get('modules', 'hidecore');
    $selstyle = xarModVars::get('modules', 'selstyle');
    $selstyle = xarModVars::get('modules', 'selfilter');
    $selstyle = xarModVars::get('modules', 'selsort');
    if (empty($hidecore)) xarModVars::set('modules', 'hidecore', 0);
    if (empty($selstyle)) xarModVars::set('modules', 'selstyle', 'plain');
    if (empty($selfilter)) xarModVars::set('modules', 'selfilter', XARMOD_STATE_ANY);
    if (empty($selsort)) xarModVars::set('modules', 'selsort', 'nameasc');



    // New in 1.1.x series but not used
    xarModVars::set('modules', 'disableoverview',0);

    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function modules_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
            // Get database information
            $dbconn = xarDB::getConn();
            $xartable = xarDB::getTables();

            //Load Table Maintainance API
            sys::import('xaraya.tableddl');

            $hookstable = $xartable['hooks'];
            $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');

            $fieldargs = array('command' => 'add', 'field' => 't_file', 'type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset);
            $query = xarDBAlterTable($hookstable,$fieldargs);
            $result =& $dbconn->Execute($query);
            if (!$result) return;

        case '2.0.1':
            /**
             * Create Event System table
             * NOTE: we do this here, not in base, because the event system depends on the module system
            **/
            $dbconn = xarDB::getConn();
            $tables =& xarDB::getTables();

            $prefix = xarDB::getPrefix();

            // Creating the first part inside a transaction
            try {
                $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
                $dbconn->begin();
                /**
                 * CREATE TABLE xar_eventsystem (
                 *   id         integer NOT NULL auto_increment,
                 *   event      varchar(254) NOT NULL,
                 *   module_id  integer default 0,
                 *   itemtype   integer default 0
                 *   area       varchar(64) NOT NULL,
                 *   type       varchar(64) NOT NULL,
                 *   func       varchar(64) NOT NULL,
                 *   scope      varchar(64) NOT NULL,
                 *   PRIMARY KEY (id)
                 * )
                 */

                $fields = array(
                    'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                    'event' => array('type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset),
                    'module_id' => array('type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0'),            
                    'itemtype' => array('type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0'),
                    'area' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                    'type' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                    'func' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
                    'scope' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset)
                );

                // Create the eventsystem table
                $query = xarDBCreateTable($tables['eventsystem'], $fields);
                $dbconn->Execute($query);

                // each entry should be unique
                $index = array('name'   => 'i_'.$prefix.'_eventsystem',
                       'fields' => array('event', 'module_id', 'itemtype'),
                       'unique' => true);

                $query = xarDBCreateIndex($tables['eventsystem'],$index);
                $dbconn->Execute($query);
                /**
                 * CREATE TABLE xar_index (
                 *   id         integer NOT NULL auto_increment,
                 *   module_id  integer default 0,
                 *   itemtype   integer default 0,
                 *   item_id    integer default 0,
                 *   PRIMARY KEY (id)
                 * )
                 */
                /* @TODO: index subsystem 
                $fields = array(
                    'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                    'module_id' => array('type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0'),            
                    'itemtype' => array('type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0'),
                    'item_id' => array('type' => 'integer', 'size' => 11, 'unsigned' => true, 'null' => false, 'default' => '0')
                );

                // Create the eventsystem table
                $query = xarDBCreateTable($tables['index'], $fields);
                $dbconn->Execute($query);

                // each entry should be unique
                $index = array('name'   => 'i_'.$prefix.'_index',
                       'fields' => array('module_id', 'itemtype', 'item_id'),
                       'unique' => true);

                $query = xarDBCreateIndex($tables['index'],$index);
                $dbconn->Execute($query);
                */
                // Let's commit this, since we're gonna do some other stuff
                $dbconn->commit();

            } catch (Exception $e) {
                $dbconn->rollback();
                throw $e;
            }
            /* System Events */
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

            /* Hook Events */
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

/**
 * Delete this module
 *
 * @return boolean
 */
function modules_delete()
{
    // this module cannot be removed
    return false;
}

?>
