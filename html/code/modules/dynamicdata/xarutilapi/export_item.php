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
 * Export a single object item for an object id and item id to XML
 *
 * @author mikespub <mikespub@xaraya.com>
 * @param id $args['objectid'] object id of the object item to export
 * @param id $args['itemid'] item id of the object item to export
 */
function dynamicdata_utilapi_export_item(array $args=[])
{
    extract($args);

    if (empty($objectid) || empty($itemid)) {
        return;
    }

    $myobject = DataObjectMaster::getObject(['objectid' => $objectid,
                                             'itemid'   => $itemid,
                                             'allprops' => true, ]);

    if (!isset($myobject) || empty($myobject->label)) {
        return;
    }

    $myobject->getItem();
    $item = $myobject->getFieldValues();

    $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $xml .= '<'.$myobject->name.' itemid="'.$itemid.'">'."\n";
    foreach ($myobject->properties as $name => $property) {
        if (method_exists($property, 'getDeferredData')) {
            $property->setDataToDefer($itemid, $item[$name]);
            // @checkme set the targetLoader to null to avoid retrieving the propname values
            if (!empty($property->targetname)) {
                $property->getDeferredLoader()->targetLoader = null;
            }
            $xml .= "  <$name>" . $property->exportValue($itemid, $item) . "</$name>\n";
        } else {
            $xml .= "  <$name>" . $property->exportValue($itemid, $item) . "</$name>\n";
        }
    }
    $xml .= '</'.$myobject->name.">\n";

    return $xml;
}
