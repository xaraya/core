<?php

/**
 * Delete all dynamicdata fields for a module
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use Xaraya\Database\ConnectionInterface;
use Xaraya\Database\StatementInterface;
use xarDB;
use xarMod;
use xarSecurity;
use BadParameterException;
use SQLException;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');
sys::import('xaraya.database.interface');

class ModuleRemove extends DataObjectHookObserver
{
    /**
     * delete all dynamicdata fields for a module - hook for ('module','remove','API')
     *
     * @param array<string, mixed> $extrainfo extra information
     * @return array<mixed> true on success, false on failure
     * @throws BadParameterException
     */
    public function run(array $extrainfo = [])
    {
        // everything is already validated in HookSubject, except possible empty objectid/itemid for create/display
        $modname = $extrainfo['module'];
        $itemtype = $extrainfo['itemtype'];
        $module_id = $extrainfo['module_id'];

        // don't allow hooking to yourself in DD
        if ($modname == 'dynamicdata') {
            return $extrainfo;
        }

        if (!xarSecurity::check('DeleteDynamicDataItem', 0, 'Item', "$module_id:All:All")) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['security check', 'admin', 'moduleremove', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
        }

        // Get database setup
        /** @var ConnectionInterface $dbconn */
        $dbconn = xarDB::getConn();
        $xartable =  xarDB::getTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $sql = "SELECT id FROM $dynamicprop WHERE moduleid = ?";
        /** @var StatementInterface $stmt */
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery([$module_id]);

        // TODO: do we want to catch the exception here? or in the callee?
        //return $extrainfo;
        $ids = [];
        while ($result->next()) {
            [$id] = $result->fields;
            $ids[] = $id;
        }
        //    $result->close();

        if (count($ids) == 0) {
            return $extrainfo;
        }

        $dynamicdata = $xartable['dynamic_data'];

        // TODO: don't delete if the data source is not in dynamic_data
        try {
            $dbconn->begin();

            // Delete the item fields
            $bindmarkers = '?' . str_repeat(',?', count($ids) - 1);
            $sql = "DELETE FROM $dynamicdata WHERE property_id IN ($bindmarkers)";
            /** @var StatementInterface $stmt */
            $stmt = $dbconn->prepareStatement($sql);
            $stmt->executeUpdate($ids);

            // Delete the properties
            $sql = "DELETE FROM $dynamicprop WHERE id IN ($bindmarkers)";
            /** @var StatementInterface $stmt */
            $stmt = $dbconn->prepareStatement($sql);
            $stmt->executeUpdate($ids);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        // Return the extra info
        return $extrainfo;
    }
}
