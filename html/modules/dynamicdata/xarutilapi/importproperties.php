<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * import property fields from a static table
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id of the table to import
 * @param $args['itemtype'] item type of the table to import
 * @param $args['table'] name of the table you want to import
 * @param $args['objectid'] object id to assign these properties to
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_utilapi_importproperties($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'util', 'importproperties', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AddDynamicDataField')) return;

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    // search for an object, or create one
    if (empty($objectid)) {
        $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
        if (!isset($object)) {
            $modinfo = xarModGetInfo($modid);
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        } else {
            $objectid = $object['objectid'];
        }
    }

    $fields = xarModAPIFunc('dynamicdata','util','getstatic',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'table' => $table));
    if (!isset($fields) || !is_array($fields)) return;

    // create new properties
    foreach ($fields as $name => $field) {
        $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                array('name' => $name,
                                      'label' => $field['label'],
                                      'objectid' => $objectid,
                                      'moduleid' => $modid,
                                      'itemtype' => $itemtype,
                                      'type' => $field['type'],
                                      'default' => $field['default'],
                                      'source' => $field['source'],
                                      'status' => $field['status'],
                                      'order' => $field['order'],
                                      'validation' => $field['validation']));
        if (empty($prop_id)) {
            return;
        }
    }
    return true;
}


?>