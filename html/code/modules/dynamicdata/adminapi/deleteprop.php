<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;
use BadParameterException;
use SQLException;
use xarDB;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata adminapi deleteprop function
 * @extends MethodClass<AdminApi>
 */
class DeletepropMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete a property field
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     *        integer  $args['id'] property id of the item field to delete<br/>
     * // TODO: do we want those for security check ? Yes, but the original values...<br/>
     *        integer  $args['module_id'] module id of the item field to delete<br/>
     *        string   $args['itemtype'] item type of the item field to delete<br/>
     *        string   $args['name'] name of the field to delete<br/>
     *        string   $args['label'] label of the field to delete<br/>
     *        string   $args['type'] type of the field to delete<br/>
     *        string   $args['defaultvalue'] default of the field to delete<br/>
     *        string   $args['source'] data source of the field to delete<br/>
     *        string   $args['configuration'] configuration of the field to delete
     * @return bool true on success, false on failure
     * @throws \BadParameterException
     * @see AdminApi::deleteprop()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Required arguments
        $invalid = [];
        if (!isset($id) || !is_numeric($id)) {
            $invalid[] = 'property id';
        }
        if (count($invalid) > 0) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = [join(', ', $invalid), 'admin', 'deleteprop', 'DynamicData'];
            throw new BadParameterException($vars, $msg);
        }

        // TODO: security check on object level

        $dbconn = $this->db()->getConn();
        $xartable =  $this->db()->getTables();
        // It's good practice to name the table and column definitions you
        // are getting - $table and $column don't cut it in more complex
        // modules
        $dynamicprop = $xartable['dynamic_properties'];

        try {
            $dbconn->begin();
            $sql = "DELETE FROM $dynamicprop WHERE id = ?";
            $dbconn->Execute($sql, [$id]);

            // TODO: don't delete if the data source is not in dynamic_data
            // delete all data too !
            $dynamicdata = $xartable['dynamic_data'];
            $sql = "DELETE FROM $dynamicdata WHERE property_id = ?";
            $dbconn->Execute($sql, [$id]);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;
    }
}
