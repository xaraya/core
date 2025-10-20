<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UtilApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UtilApi;
use Xaraya\Modules\DynamicData\AdminApi;
use BadParameterException;
use DataObjectFactory;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata utilapi importproperties function
 * @extends MethodClass<UtilApi>
 */
class ImportpropertiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * import property fields from a static table
     * @author the DynamicData module development team
     * @param array<string,mixed> $args
     * with
     *        int $args['module_id'] module id of the table to import
     *        int $args['itemtype'] item type of the table to import
     *     string $args['table'] name of the table you want to import
     *        int $args['objectid'] object id to assign these properties to
     * @return bool|void true on success, false on failure
     * @throws \BadParameterException
     * @see UtilApi::importproperties()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        // Required arguments
        $invalid = [];
        if (empty($module_id)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['module id', 'util', 'importproperties', 'DynamicData'];
            throw new BadParameterException($vars, $msg);
        }

        // Security check - important to do this as early on as possible to
        // avoid potential security holes or just too much wasted processing
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
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
            $object = $this->data()->getObjectInfo(
                ['module_id' => $module_id,
                    'itemtype' => $itemtype]
            );
            if (!isset($object)) {
                $modinfo = $this->mod()->getInfo($module_id);
                $name = $modinfo['name'];
                if (!empty($itemtype)) {
                    $name .= '_' . $itemtype;
                }
                sys::import('modules.dynamicdata.class.objects.factory');
                $objectid = DataObjectFactory::createObject(
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

        $fields = $utilapi->getstatic(['module_id' => $module_id,
            'itemtype' => $itemtype,
            'table' => $table]);
        if (!isset($fields) || !is_array($fields)) {
            return;
        }

        // create new properties
        foreach ($fields as $name => $field) {
            $id = $adminapi->createproperty(['name'       => $name,
                'label'      => $field['label'],
                'objectid'   => $objectid,
                'moduleid'   => $module_id,
                'itemtype'   => $itemtype,
                'type'       => $field['type'],
                'defaultvalue' => $field['default'],
                'source'     => $field['source'],
                'status'     => $field['status'],
                'seq'      => $field['seq'],
                'configuration' => $field['configuration']]);
            if (empty($id)) {
                return;
            }
        }
        return true;
    }
}
