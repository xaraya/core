<?php

/**
 * Handle module installer functions
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories;

use Xaraya\Modules\InstallerClass;
use Exception;
use xarMasks;
use xarModHooks;
use xarPrivileges;
use xarXMLInstaller;
use sys;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @internal replaced categories_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * * Initialise the categories module
     * @author Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>
     * @author mikespub <postnuke@mikespub.net>
     * @return bool True on success null/false on failure.
     */
    public function init()
    {
        # --------------------------------------------------------
        #
        # Set up tables
        #
        // Get database information
        $dbconn = $this->db()->getConn();
        try {
            $dbconn->begin();
            sys::import('xaraya.tableddl');
            xarXMLInstaller::createTable('table_schema-def', 'categories');
            // We're done, commit
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }
        # --------------------------------------------------------
        #
        # Set up hooks
        #
        // when a new module item is being specified
        if (!xarModHooks::register('item', 'new', 'GUI', 'categories', 'admin', 'newhook')) {
            return false;
        }

        // when a module item is created (uses 'cids')
        if (!xarModHooks::register('item', 'create', 'API', 'categories', 'admin', 'createhook')) {
            return false;
        }

        // when a module item is being modified (uses 'cids')
        if (!xarModHooks::register('item', 'modify', 'GUI', 'categories', 'admin', 'modifyhook')) {
            return false;
        }

        // when a module item is updated (uses 'cids')
        if (!xarModHooks::register('item', 'update', 'API', 'categories', 'admin', 'updatehook')) {
            return false;
        }

        // when a module item is deleted
        if (!xarModHooks::register('item', 'delete', 'API', 'categories', 'admin', 'deletehook')) {
            return false;
        }

        // when a module configuration is being modified (uses 'cids')
        if (!xarModHooks::register('module', 'modifyconfig', 'GUI', 'categories', 'admin', 'modifyconfighook')) {
            return false;
        }

        // when a module configuration is updated (uses 'cids')
        if (!xarModHooks::register('module', 'updateconfig', 'API', 'categories', 'admin', 'updateconfighook')) {
            return false;
        }

        // when a whole module is removed, e.g. via the modules admin screen
        // (set object ID to the module name !)
        if (!xarModHooks::register('module', 'remove', 'API', 'categories', 'admin', 'removehook')) {
            return false;
        }

        /*********************************************************************
        * Define instances for this module
        * Format is
        * setInstance(Module,Type,ModuleTable,IDField,NameField,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
        *********************************************************************/
        # --------------------------------------------------------
        #
        # Set up masks
        #
        xarMasks::register('ViewCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_OVERVIEW');
        xarMasks::register('ReadCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_READ');
        xarMasks::register('CommmentCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_COMMENT');
        xarMasks::register('ModerateCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_MODERATE');
        xarMasks::register('EditCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_EDIT');
        xarMasks::register('AddCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_ADD');
        xarMasks::register('ManageCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_DELETE');
        xarMasks::register('AdminCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_ADMIN');
        xarMasks::register('ReadCategoryBlock', 'All', 'categories', 'Block', 'All:All:All', 'ACCESS_READ');
        xarMasks::register('ViewCategoryLink', 'All', 'categories', 'Link', 'All:All:All:All', 'ACCESS_OVERVIEW');
        xarMasks::register('SubmitCategoryLink', 'All', 'categories', 'Link', 'All:All:All:All', 'ACCESS_COMMENT');
        xarMasks::register('EditCategoryLink', 'All', 'categories', 'Link', 'All:All:All:All', 'ACCESS_EDIT');
        xarMasks::register('ManageCategoryLink', 'All', 'categories', 'Link', 'All:All:All:All', 'ACCESS_DELETE');
        # --------------------------------------------------------
        #
        # Set up privileges
        #
        xarPrivileges::register('ViewCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_OVERVIEW');
        xarPrivileges::register('ReadCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_READ');
        xarPrivileges::register('CommmentCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_COMMENT');
        xarPrivileges::register('ModerateCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_MODERATE');
        xarPrivileges::register('EditCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_EDIT');
        xarPrivileges::register('AddCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_ADD');
        xarPrivileges::register('ManageCategories', 'All', 'categories', 'Category', 'All:All', 'ACCESS_DELETE');
        xarPrivileges::register('AdminCategories', 'All', 'categories', 'Category', 'All', 'ACCESS_ADMIN');
        # --------------------------------------------------------
        #
        # Set up modvars
        #
        $this->mod()->setVar('usejsdisplay', 0);
        $this->mod()->setVar('numstats', 100);
        $this->mod()->setVar('showtitle', 1);
        $this->mod()->setVar('categoriesobject', 'categories');
        // Initialisation successful
        return true;
    }

    /**
     * Upgrade the categories module from an old version
     * @author Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
     * @access public
     * @param mixed $oldVersion
     * @return true on success or false on failure
     * @todo nothing
     */
    public function upgrade($oldversion)
    {
        // Get database information
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '2.6.0':
                // Code to upgrade from version 2.6.0 goes here
                // fall through to the next upgrade
                break;
        }
        // Upgrade successful
        return true;
    }

    public function delete()
    {
        //this module cannot be removed
        return false;
    }
}
