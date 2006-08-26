<?php
/**
 * Get list of modules and itemtypes with dynamic properties
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get the list of modules + itemtypes for which dynamic properties are defined
 *
 * @author the DynamicData module development team
 * @return array of modid + itemtype + number of properties
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getmodules($args)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $query = "SELECT xar_prop_moduleid,
                     xar_prop_itemtype,
                     COUNT(xar_prop_id)
              FROM $dynamicprop
              GROUP BY xar_prop_moduleid, xar_prop_itemtype
              ORDER BY xar_prop_moduleid ASC, xar_prop_itemtype ASC";

    $result =& $dbconn->Execute($query);

    $modules = array();
    while (!$result->EOF) {
        list($modid, $itemtype, $count) = $result->fields;
        if(xarSecurityCheck('ViewDynamicDataItems',0,'Item',"$modid:$itemtype:All")) {
            $modules[] = array('modid' => $modid,
                               'itemtype' => $itemtype,
                               'numitems' => $count);
        }
        $result->MoveNext();
    }

    $result->Close();

    return $modules;
}

?>
