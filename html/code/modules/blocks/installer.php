<?php

/**
 * Handle module installer functions
 *
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks;

use Xaraya\Modules\InstallerClass;
use xarMasks;
use xarPrivileges;
use xarXMLInstaller;
use Exception;

/**
 * Handle module installer functions
 *
 * @internal replaced blocks_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Initialise the blocks module
     * @author Jim McDonald
     * @author Paul Rosania
     */
    public function init()
    {
        // Get database information
        $dbconn = $this->db()->getConn();
        try {
            $dbconn->begin();
            xarXMLInstaller::createTable('table_schema-def', 'blocks');
            // We're done, commit
            $dbconn->commit();
        } catch (Exception $e) {
            $dbconn->rollback();
            throw $e;
        }
        $prefix = $this->db()->getPrefix();

        $this->mod()->setVar('selstyle', 'plain');
        $this->mod()->setVar('noexceptions', 1);

        // checkme: <chris/> The following note seems like a 1x thing
        /* There are old block instances defined previously in privs xarsetup.php file and used in the Block module.
           From this version we are adding management of security for blocks to Blocks module
           Old functionality in modules still exists.
           Note that the old instances and masks and code in the files was not 'matched' so don't think they worked properly in any case.
        */
        // checkme: <chris/> at install, surely we have nothing to remove?
        //xarPrivileges::removeInstances('blocks');
        //$blockGroupsTable    = $prefix . '_block_groups';
        $blockTypesTable     = $prefix . '_block_types';
        $blockInstancesTable = $prefix . '_block_instances';

        // checkme: are these necessary now we have anon privs per block instance?
        // todo: if we do keep them the query definitions need to be assessed
        //The block instances differ and now defined on name (not title)
        //These need to be upgraded <chris/> is this upgrade a 1x thing?
        $query1 = "SELECT DISTINCT module_id FROM $blockTypesTable ";
        $query2 = "SELECT DISTINCT instances.name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id";
        $instances = [['header' => 'Module Name:',
            'query' => $query1,
            'limit' => 20],
            ['header' => 'Block Name:',
                'query' => $query2,
                'limit' => 20]];
        xarPrivileges::defineInstance('blocks', 'Block', $instances);

        //Define an instance that refers to items that a block contains
        // checkme: items that a block contains? what does that mean?
        $query1 = "SELECT DISTINCT instances.name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id";
        $modulesTable = $prefix . '_modules';
        $query2 = "SELECT DISTINCT name FROM $modulesTable ";
        $instances = [['header' => 'Block Name:',
            'query' => $query1,
            'limit' => 20],
            ['header' => 'Module Name:',
                'query' => $query2,
                'limit' => 20]];
        xarPrivileges::defineInstance('blocks', 'BlockItem', $instances);

        xarMasks::register('ViewBlocks', 'All', 'blocks', 'All', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('EditBlocks', 'All', 'blocks', 'All', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddBlocks', 'All', 'blocks', 'All', 'All', 'ACCESS_ADD');
        xarMasks::register('ManageBlocks', 'All', 'blocks', 'All', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminBlocks', 'All', 'blocks', 'All', 'All', 'ACCESS_ADMIN');

        // Installation complete; check for upgrades
        return $this->upgrade('2.2.0');
    }

    /**
     * Upgrade this module from an old version
     * @param string $oldversion
     * @return bool True on success, false on failure
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '2.2.0':
                // Register blocks module event observers
                $this->events()->registerObserver('ModRemove', 'blocks');
                $this->events()->registerObserver('ModActivate', 'blocks');
                $this->events()->registerObserver('ModDeactivate', 'blocks');
                // no break
            default:
                break;
        }
        return true;
    }

    /**
     * Delete this module
     * @return bool Always returns false. This module cannot be deleted
     */
    public function delete()
    {
        //this module cannot be removed
        return false;
    }
}
