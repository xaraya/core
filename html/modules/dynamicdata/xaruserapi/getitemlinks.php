<?php
/**
 * Pass individual item links
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
 * utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @returns array
 * @return array containing the itemlink(s) for the item(s).
 */
function dynamicdata_userapi_getitemlinks($args)
{
    extract($args);

    $itemlinks = array();
    if (empty($itemtype)) {
        $itemtype = null;
    }
    $status = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
    list($properties,$items) = xarModAPIFunc('dynamicdata','user','getitemsforview',
                                                   // for items managed by DD itself only
                                             array('modid' => xarModGetIDFromName('dynamicdata'),
                                                   'itemtype' => $itemtype,
                                                   'itemids' => $itemids,
                                                   'status' => $status,
                                                  )
                                            );
    if (!isset($items) || !is_array($items) || count($items) == 0) {
       return $itemlinks;
    }

// TODO: make configurable
    $titlefield = '';
    foreach ($properties as $name => $property) {
        // let's use the first textbox property we find for now...
        if ($property->type == 2) {
            $titlefield = $name;
            break;
        }
    }

    // if we didn't have a list of itemids, return all the items we found
    if (empty($itemids)) {
        $itemids = array_keys($items);
    }

    foreach ($itemids as $itemid) {
        if (!empty($titlefield) && isset($items[$itemid][$titlefield])) {
            $label = $items[$itemid][$titlefield];
        } else {
            $label = xarML('Item #(1)',$itemid);
        }
        $itemlinks[$itemid] = array('url'   => xarModURL('dynamicdata', 'user', 'display',
                                                         array('itemtype' => $itemtype,
                                                               'itemid' => $itemid)),
                                    'title' => xarML('Display Item'),
                                    'label' => $label);
    }
    return $itemlinks;
}

?>
