<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use DataObjectFactory;
use Exception;
use xarMasks;
use xarModVars;
use xarPrivileges;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi standarddeinstall function
 * @extends MethodClass<AdminApi>
 */
class StandarddeinstallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Perform standard module removal actions
     * @author Marc Lutolf (mfl@netspan.ch)
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return bool|void result of action
     * @see AdminApi::standarddeinstall()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($module)) {
            return false;
        }

        # --------------------------------------------------------
        #
        # Delete all DD objects created by this module
        #
        try {
            $dd_objects = unserialize($this->mod($module)->getVar('dd_objects'));
            foreach ($dd_objects as $key => $value) {
                $result = DataObjectFactory::deleteObject(['objectid' => $value]);
            }
        } catch (Exception $e) {
        }

        # --------------------------------------------------------
        #
        # Remove database tables
        #
        $dbconn = $this->db()->getConn();
        $xartables = $this->db()->getTables();

        $this->mod()->apiLoad($module);
        $tablenameprefix = $this->db()->getPrefix() . '_' . $module;
        foreach ($xartables as $table) {
            if (is_array($table)) {
                continue;
            }
            if (strpos($table, $tablenameprefix) === 0) {
                $query = 'DROP TABLE ' . $table;
                try {
                    $dbconn->Execute($query);
                } catch (Exception $e) {
                }
            }
        }

        # --------------------------------------------------------
        #
        # Remove all blocks created by this module
        #
        try {
            $blocks = unserialize($this->mod($module)->getVar('blocks'));

            foreach ($blocks as $blockid) {
                $this->mod()->apiFunc('blocks', 'instances', 'deleteitem', ['block_id' => $blockid]);
            }
        } catch (Exception $e) {
        }

        # --------------------------------------------------------
        #
        # Delete the base group created by this module if it exists
        #
        // Move the descendants to the Users group
        try {
            $role = xarRoles::findRole(ucfirst($module) . 'Group');
            if (!empty($role)) {
                $usersgroup = xarRoles::findRole('Users');
                $descendants = $role->getDescendants();
                foreach ($descendants as $item) {
                    $parents = $item->getParents();
                    if (count($parents) > 1) {
                        $usersgroup->addMember($item);
                    }
                    if (!$role->removeMember($item)) {
                        return;
                    }
                }
                if (!$role->purge()) {
                    return;
                }
            }
        } catch (Exception $e) {
        }

        // Remove the categories created by this module
        try {
            $this->mod()->apiFunc(
                'categories',
                'admin',
                'deletecat',
                ['cid' => $this->mod($module)->getVar('basecategory')]
            );
        } catch (Exception $e) {
        }

        # --------------------------------------------------------
        #
        # Remove hooks
        /*
        Since this is hooks, the ModuleRemove subject deals with it
        */

        # --------------------------------------------------------
        #
        # Remove modvars, masks and privilege instances
        #
        xarMasks::removemasks($module);
        xarPrivileges::removeInstances($module);
        $this->mod($module)->flushVars();

        // Deinstall successful
        return true;
    }
}
