<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * import property fields from a static table
 *
 * @author the DynamicData module development team
 * @param id $args['module_id'] module id of the table to import
 * @param int $args['itemtype'] item type of the table to import
 * @param string $args['table'] name of the table you want to import
 * @param id $args['objectid'] object id to assign these properties to
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_utilapi_importproperties($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module id', 'util', 'importproperties', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    // search for an object, or create one
    if (empty($objectid)) {
        $object = DataObjectMaster::getObjectInfo(
                                array('module_id' => $module_id,
                                      'itemtype' => $itemtype));
        if (!isset($object)) {
            $modinfo = xarMod::getInfo($module_id);
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            sys::import('modules.dynamicdata.class.objects.master');
            $objectid = DataObjectMaster::createObject(
                                      array('moduleid' => $module_id,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        } else {
            $objectid = $object['objectid'];
        }
    }

    $fields = xarMod::apiFunc('dynamicdata','util','getstatic',
                            array('module_id' => $module_id,
                                  'itemtype' => $itemtype,
                                  'table' => $table));
    if (!isset($fields) || !is_array($fields)) return;

    // create new properties
    foreach ($fields as $name => $field) {
        $id = xarMod::apiFunc('dynamicdata','admin','createproperty',
                                array('name'       => $name,
                                      'label'      => $field['label'],
                                      'objectid'   => $objectid,
                                      'moduleid'   => $module_id,
                                      'itemtype'   => $itemtype,
                                      'type'       => $field['type'],
                                      'defaultvalue'=> $field['default'],
                                      'source'     => $field['source'],
                                      'status'     => $field['status'],
                                      'seq'      => $field['seq'],
                                      'configuration' => $field['configuration']));
        if (empty($id)) {
            return;
        }
    }
    return true;
}


?>