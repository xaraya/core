<?php
/**
 * Pass individual item links
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
 * utility function to pass individual item links to whoever
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['itemtype'] item type (optional)<br/>
 *        array    $args['itemids'] array of item ids to get
 * @return array<mixed> containing the itemlink(s) for the item(s).
 */
function dynamicdata_userapi_getitemlinks(array $args = [], $context = null)
{
    extract($args);

    $itemlinks = [];
    if (empty($itemtype)) {
        $itemtype = null;
    }
    if (empty($itemids)) {
        $itemids = null;
    }
    // for items managed by DD itself only
    $module_id = xarMod::getRegID('dynamicdata');
    $args = DataObjectDescriptor::getObjectID(['moduleid'  => $module_id,
                                       'itemtype'  => $itemtype]);
    if (empty($args['objectid'])) {
        return $itemlinks;
    }
    $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    $object = DataObjectFactory::getObjectList(['objectid'  => $args['objectid'],
                                           'itemids' => $itemids,
                                           'status' => $status]);
    if (!isset($object) || (empty($object->objectid) && empty($object->table))) {
        return $itemlinks;
    }
    // set context if available in function
    $object->setContext($context);
    if (!$object->checkAccess('view')) {
        return $itemlinks;
    }

    $object->getItems();

    $properties = & $object->getProperties();
    $items = & $object->items;
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
            $label = xarML('Item #(1)', $itemid);
        }
        // $object->getActionURL('display', $itemid)
        $itemlinks[$itemid] = ['url'   => xarController::URL(
            'dynamicdata',
            'user',
            'display',
            ['name' => $args['name'],
                                                               'itemid' => $itemid]
        ),
                                    'title' => xarML('Display Item'),
                                    'label' => $label];
    }
    return $itemlinks;
}
