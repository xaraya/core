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
 * import property fields from a static table
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args
 * with
 *        int $args['module_id'] module id of the table to import
 *        int $args['itemtype'] item type of the table to import
 *     string $args['table'] name of the table you want to import
 *        int $args['objectid'] object id to assign these properties to
 * @return boolean|void true on success, false on failure
 * @throws BadParameterException
 */
function dynamicdata_utilapi_importproperties(array $args = [])
{
    extract($args);

    // Required arguments
    $invalid = [];
    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = ['module id', 'util', 'importproperties', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    // search for an object, or create one
    if (empty($objectid)) {
        $object = DataObjectMaster::getObjectInfo(
            ['module_id' => $module_id,
                                      'itemtype' => $itemtype]
        );
        if (!isset($object)) {
            $modinfo = xarMod::getInfo($module_id);
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            sys::import('modules.dynamicdata.class.objects.master');
            $objectid = DataObjectMaster::createObject(
                ['moduleid' => $module_id,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)]
            );
            if (empty($objectid)) {
                return;
            }
        } else {
            $objectid = $object['objectid'];
        }
    }

    $fields = xarMod::apiFunc(
        'dynamicdata',
        'util',
        'getstatic',
        ['module_id' => $module_id,
                                  'itemtype' => $itemtype,
                                  'table' => $table]
    );
    if (!isset($fields) || !is_array($fields)) {
        return;
    }

    // create new properties
    foreach ($fields as $name => $field) {
        $id = xarMod::apiFunc(
            'dynamicdata',
            'admin',
            'createproperty',
            ['name'       => $name,
                                      'label'      => $field['label'],
                                      'objectid'   => $objectid,
                                      'moduleid'   => $module_id,
                                      'itemtype'   => $itemtype,
                                      'type'       => $field['type'],
                                      'defaultvalue' => $field['default'],
                                      'source'     => $field['source'],
                                      'status'     => $field['status'],
                                      'seq'      => $field['seq'],
                                      'configuration' => $field['configuration']]
        );
        if (empty($id)) {
            return;
        }
    }
    return true;
}
