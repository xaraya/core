<?php
/**
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
/**
 * Export all object items for an object id to XML
 *
 * @author mikespub <mikespub@xaraya.com>
 * @param array<string, mixed> $args
 * with
 *     int $args['objectid'] object id of the object items to export
 * @return string|void
 */
function dynamicdata_utilapi_export_items(array $args = [])
{
    extract($args);

    if (empty($objectid)) {
        return;
    }

    $mylist = DataObjectMaster::getObjectList(['objectid' => $objectid,
                                               'prelist'  => false, ]);     // don't run preList method

    // Export all properties that are not disabled
    foreach ($mylist->properties as $name => $property) {
        $status = $property->getDisplayStatus();
        if ($status == DataPropertyMaster::DD_DISPLAYSTATE_DISABLED) {
            // Remove this property if it is disabled
            unset($mylist->properties[$name]);
        } else {
            // Anything else: set to active
            $mylist->properties[$name]->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE);
        }
    }
    $mylist->getItems(['getvirtuals' => 1]);

    $fieldlist = array_keys($mylist->properties);
    $deferred = [];
    foreach ($fieldlist as $key) {
        if (!empty($mylist->properties[$key]) && $mylist->properties[$key] instanceof DeferredItemProperty) {
            array_push($deferred, $key);
            // @checkme set the targetLoader to null to avoid retrieving the propname values
            if ($mylist->properties[$key] instanceof DeferredManyProperty) {
                $mylist->properties[$key]->getDeferredLoader()->targetLoader = null;
            }
            // @checkme we need to set the item values for relational objects here
            // foreach ($mylist->items as $itemid => $item) {
            //     $mylist->properties[$key]->setItemValue($itemid, $item[$key] ?? null);
            // }
        }
    }

    $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $xml .= "<items>\n";
    foreach ($mylist->items as $itemid => $item) {
        $xml .= '  <'.$mylist->name.' itemid="'.$itemid.'">'."\n";
        foreach ($mylist->properties as $name => $property) {
            if (isset($item[$name]) || in_array($name, $deferred)) {
                $xml .= "    <$name>";
                $xml .= $property->exportValue($itemid, $item);
            } else {
                $xml .= "    <$name>";
            }
            $xml .= "</$name>\n";
        }
        $xml .= '  </'.$mylist->name.">\n";
    }
    $xml .= "</items>\n";

    return $xml;
}
