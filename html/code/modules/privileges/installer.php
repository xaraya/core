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
use xarPrivileges;
use xarMasks;
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

    /**
     * Default setup for roles and privileges
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     */
    public function initializeSetup()
    {

        /*********************************************************************
        * Define instances for the core modules
        * Format is
        * xarPrivileges::defineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
        *********************************************************************/
        $prefix = $this->db()->getPrefix();

        $blockTypesTable     = $prefix . '_block_types';
        $blockInstancesTable = $prefix . '_block_instances';
        $modulesTable        = $prefix . '_modules';
        $rolesTable          = $prefix . '_roles';
        $roleMembersTable    = $prefix . '_rolemembers';
        $privilegesTable     = $prefix . '_privileges';
        $privMembersTable    = $prefix . '_privmembers';
        $themesTable         = $prefix . '_themes';
        $categorytable       = $prefix . '_categories';

        //--------------------------------- Roles Module
        $info = $this->mod()->getBaseInfo('roles');
        $sysid = $info['systemid'];
        $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
        $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $instances = [['header' => 'Block Type:',
            'query' => $query1,
            'limit' => 20],
            ['header' => 'Block Title:',
                'query' => $query2,
                'limit' => 20],
            ['header' => 'Block ID:',
                'query' => $query3,
                'limit' => 20]];
        xarPrivileges::defineInstance('roles', 'Block', $instances);

        $query = "SELECT DISTINCT name FROM $rolesTable";
        $instances = [['header' => 'Users and Groups',
            'query' => $query,
            'limit' => 20]];
        xarPrivileges::defineInstance('roles', 'Roles', $instances, 0, $roleMembersTable, 'id', 'parentid', 'Instances of the roles module, including multilevel nesting');

        $instances = [['header' => 'Parent:',
            'query' => $query,
            'limit' => 20],
            ['header' => 'Child:',
                'query' => $query,
                'limit' => 20]];
        xarPrivileges::defineInstance('roles', 'Relation', $instances, 0, $roleMembersTable, 'id', 'parentid', 'Instances of the roles module, including multilevel nesting');

        // ----------------------------- Privileges Module
        $query = "SELECT DISTINCT name FROM $privilegesTable";
        $instances = [['header' => 'Privileges',
            'query' => $query,
            'limit' => 20]];
        xarPrivileges::defineInstance('privileges', 'Privileges', $instances, 0, $privMembersTable, 'privilege_id', 'parent_id', 'Instances of the privileges module, including multilevel nesting');

        // ----------------------------- Base Module
        $info = $this->mod()->getBaseInfo('base');
        $sysid = $info['systemid'];
        $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
        $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $instances = [['header' => 'Block Type:',
            'query' => $query1,
            'limit' => 20],
            ['header' => 'Block Title:',
                'query' => $query2,
                'limit' => 20],
            ['header' => 'Block ID:',
                'query' => $query3,
                'limit' => 20]];
        xarPrivileges::defineInstance('base', 'Block', $instances);

        // ------------------------------- Themes Module
        $query1 = "SELECT DISTINCT name FROM $themesTable";
        $query2 = "SELECT DISTINCT regid FROM $themesTable";
        $instances = [['header' => 'Theme Name:',
            'query' => $query1,
            'limit' => 20],
            ['header' => 'Theme ID:',
                'query' => $query2,
                'limit' => 20]];
        xarPrivileges::defineInstance('themes', 'Themes', $instances);

        $info = $this->mod()->getBaseInfo('themes');
        $sysid = $info['systemid'];
        $query1 = "SELECT DISTINCT name FROM $blockTypesTable WHERE module_id = $sysid";
        $query2 = "SELECT DISTINCT instances.title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $query3 = "SELECT DISTINCT instances.id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $instances = [['header' => 'Block Type:',
            'query' => $query1,
            'limit' => 20],
            ['header' => 'Block Title:',
                'query' => $query2,
                'limit' => 20],
            ['header' => 'Block ID:',
                'query' => $query3,
                'limit' => 20]];
        xarPrivileges::defineInstance('themes', 'Block', $instances);

        // ------------------------------- Categories Module
        $info = $this->mod()->getBaseInfo('categories');
        $sysid = $info['systemid'];
        $query = "SELECT DISTINCT instances.title FROM blockInstancesTable as instances LEFT JOIN blockTypesTable as btypes ON btypes.id = instances.type_id WHERE module_id = $sysid";
        $instances = [
            ['header' => 'Category Block Title:',
                'query' => $query,
                'limit' => 20,
            ],
        ];
        xarPrivileges::defineInstance('categories', 'Block', $instances);

        // use external privilege wizard for 'Category' and 'Link' instances
        $instances = [
            ['header' => 'external', // this keyword indicates an external "wizard"
                'query'  => $this->ctl()->getModuleURL('categories', 'admin', 'privileges'),
                'limit'  => 0,
            ],
        ];
        xarPrivileges::defineInstance('categories', 'Link', $instances);
        // TODO: get this parent/child stuff to work someday, or implement some other way ?
        xarPrivileges::defineInstance(
            'categories',
            'Category',
            $instances,
            1,
            $categorytable,
            'id',
            'parent_id',
            'Instances of the categories module, including multilevel nesting'
        );

        /*********************************************************************
        * Register the module components that are privileges objects
        * Format is
        * xarMasks::register(Name,Realm,Module,Component,Instance,Level,Description)
        *********************************************************************/

        xarMasks::register('ViewBase', 'All', 'base', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('ReadBase', 'All', 'base', 'All', 'All', 'ACCESS_READ');
        xarMasks::register('EditBase', 'All', 'base', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('ManageBase', 'All', 'base', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminBase', 'All', 'base', 'All', 'All', 'ACCESS_ADMIN');

        xarMasks::register('AdminInstaller', 'All', 'installer', 'All', 'All', 'ACCESS_ADMIN');

        xarMasks::register('ViewRoles', 'All', 'roles', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('ReadRoles', 'All', 'roles', 'All', 'All', 'ACCESS_READ');
        xarMasks::register('EditRoles', 'All', 'roles', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddRoles', 'All', 'roles', 'All', 'All', 'ACCESS_ADD');
        xarMasks::register('ManageRoles', 'All', 'roles', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminRoles', 'All', 'roles', 'All', 'All', 'ACCESS_ADMIN');
        xarMasks::register('MailRoles', 'All', 'roles', 'Mail', 'All', 'ACCESS_ADMIN');

        xarMasks::register('AttachRole', 'All', 'roles', 'Relation', 'All', 'ACCESS_ADD');
        xarMasks::register('RemoveRole', 'All', 'roles', 'Relation', 'All', 'ACCESS_DELETE');

        xarMasks::register('ViewPrivileges', 'All', 'privileges', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('EditPrivileges', 'All', 'privileges', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddPrivileges', 'All', 'privileges', 'All', 'All', 'ACCESS_ADD');
        xarMasks::register('ManagePrivileges','All','privileges','All','All','ACCESS_DELETE');
        xarMasks::register('AdminPrivileges','All','privileges','All','All','ACCESS_ADMIN');

        xarMasks::register('ViewModules','All','modules','All','All','ACCESS_OVERVIEW');
        xarMasks::register('EditModules','All','modules','All','All','ACCESS_EDIT');
        xarMasks::register('ManageModules','All','modules','All','All','ACCESS_DELETE');
        xarMasks::register('AdminModules','All','modules','All','All','ACCESS_ADMIN');

        return true;
    }
}
