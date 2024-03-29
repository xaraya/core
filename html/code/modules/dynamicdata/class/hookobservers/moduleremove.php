<?php
/**
 * Delete all dynamicdata fields for a module
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use xarDB;
use xarMod;
use xarSecurity;
use BadParameterException;
use SQLException;
use sys;

sys::import('modules.dynamicdata.class.hookobservers.generic');

class ModuleRemove extends DataObjectHookObserver
{
    /**
     * delete all dynamicdata fields for a module - hook for ('module','remove','API')
     *
     * @param array<string, mixed> $args array of optional parameters<br/>
     *        integer  $args['objectid'] ID of the object (must be the module name here !!)<br/>
     *        string   $args['extrainfo'] extra information
     * @return array<mixed> true on success, false on failure
     * @throws BadParameterException
     */
    public static function run(array $args = [], $context = null)
    {
        extract($args);

        if (!isset($extrainfo)) {
            $extrainfo = [];
        }

        // When called via hooks, we should get the real module name from objectid
        // here, because the current module is probably going to be 'modules' !!!
        if (!isset($objectid) || !is_string($objectid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['object ID (= module name)', 'admin', 'removehook', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
            // we *must* return $extrainfo for now, or the next hook will fail
            // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
            //return $extrainfo;
        }

        // don't allow hooking to yourself in DD
        if ($objectid == 'dynamicdata') {
            return $extrainfo;
        }

        $module_id = xarMod::getRegID($objectid);
        if (empty($module_id)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['module ID', 'admin', 'removehook', 'dynamicdata'];
            throw new BadParameterException($vars, $msg);
            // we *must* return $extrainfo for now, or the next hook will fail
            // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
            //return $extrainfo;
        }

        if(!xarSecurity::check('DeleteDynamicDataItem', 0, 'Item', "$module_id:All:All")) {
            // we *must* return $extrainfo for now, or the next hook will fail
            // CHECKME: not anymore now, exceptions are either fatal or caught, in this case, we probably want to catch it in the callee.
            //return $extrainfo;
        }

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable =  xarDB::getTables();

        $dynamicprop = $xartable['dynamic_properties'];

        $sql = "SELECT id FROM $dynamicprop WHERE moduleid = ?";
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
            $stmt = $dbconn->prepareStatement($sql);
            $stmt->executeUpdate($ids);

            // Delete the properties
            $sql = "DELETE FROM $dynamicprop WHERE id IN ($bindmarkers)";
            $stmt = $dbconn->prepareStatement($sql);
            $stmt->executeUpdate($ids);
            $dbconn->commit();
        } catch(SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        // Return the extra info
        return $extrainfo;
    }
}
