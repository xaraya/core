<?php
/**
 * Pass individual item links
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to pass individual item links to whoever
 *
 * @param array    $args array of optional parameters<br/>
 *        string   $args['itemtype'] item type (optional)<br/>
 *        array    $args['itemids'] array of item ids to get
 * @return array containing the itemlink(s) for the item(s).
 */
function dynamicdata_userapi_getitemlinks(Array $args=array())
{
    extract($args);

    $itemlinks = array();
    if (empty($itemtype)) {
        $itemtype = null;
    }
    $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    list($properties,$items) = xarMod::apiFunc('dynamicdata','user','getitemsforview',
                                                   // for items managed by DD itself only
                                             array('module_id' => xarMod::getRegID('dynamicdata'),
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
