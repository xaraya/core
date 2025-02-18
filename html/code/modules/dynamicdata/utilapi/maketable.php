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
use DataObject;
use DataObjectFactory;
use DataPropertyMaster;
use Query;
use xarDB;
use xarMod;
use xarSecurity;
use xarTableDDL;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata utilapi maketable function
 * @extends MethodClass<UtilApi>
 */
class MaketableMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create a flat table corresponding to some dynamic object definition, e.g.
     * for performance reasons or when moving from a prototype to the real thing
     *
     * This will create the table [prefix]_dd_[objectname] with fields [propname],
     * possibly with an additional itemid field to store the itemid if it's an
     * extension object (fully dynamic objects will already have an itemid property)
     *
     * Next steps to finish the move from xar_dynamic_data to a dedicated table :
     * 2. export all items to an XML file (Admin - DynamicData - View Objects - Edit - Export to XML - Export all items to file)
     * 3. UPDATE [prefix]_dynamic_properties
     *       SET source=CONCAT('[prefix]_dd_[objectname].',name)
     *     WHERE objectid = [objectid]
     * 4. add an itemid property to the object if it's an extension (see above)
     * 5. import all items from the XML file (Admin - DynamicData - Utilities - Import - change dir)
     * 6. (for extension objects) skip the extra itemid property in display / input templates
     * 7. in case of problems, report to http://bugs.xaraya.com/
     * @param array<string,mixed> $args
     * @return bool|void true on succes
     * @see UtilApi::maketable()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // restricted to DD Admins
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        if (isset($args['objectref'])) {
            /** @var DataObject $myobject */
            $myobject = & $args['objectref'];

        } else {
            extract($args);

            if (empty($objectid)) {
                $objectid = null;
            }
            if (empty($module_id)) {
                $module_id = $this->mod()->getRegID('dynamicdata');
            }
            if (empty($itemtype)) {
                $itemtype = 0;
            }
            if (empty($itemid)) {
                $itemid = null;
            }

            $myobject = $this->data()->getObject(
                ['objectid' => $objectid,
                    'moduleid' => $module_id,
                    'itemtype' => $itemtype,
                    'itemid'   => $itemid,
                    'allprops' => true]
            );
        }

        if (!isset($myobject) || empty($myobject->label)) {
            return;
        }

        // get the list of properties for a Dynamic Property
        $property_properties = $this->prop()->getProperties(['objectid' => 2]);

        $proptypes = $this->prop()->getPropertyTypes();

        $prefix = $this->db()->getPrefix();
        $prefix .= '_';

        $dbconn = $this->db()->getConn();

        //Load Table Maintenance API
        sys::import('xaraya.tableddl');

        $table = $prefix . 'dd_' . $myobject->name;

        // check if this table already exists
        $meta = $utilapi->getmeta();
        if (!empty($meta[$table])) {
            return true;
        }

        if (!empty($myobject->objectid)) {
            // get the property info directly from the database again to avoid default eval()
            $properties = $this->prop()->getProperties(['objectid' => $myobject->objectid]);
        } else {
            $properties = [];
            foreach (array_keys($myobject->properties) as $name) {
                $properties[$name] = [];
                foreach (array_keys($property_properties) as $key) {
                    if (isset($myobject->properties[$name]->$key)) {
                        $properties[$name][$key] = $myobject->properties[$name]->$key;
                    }
                }
            }
        }

        $fields = [];
        $isprimary = false;
        foreach (array_keys($properties) as $name) {
            $field = $name;
            $type = $proptypes[$properties[$name]['type']]['name'];
            $definition = [];
            switch ($type) {
                case 'itemid':
                    $definition = [
                        'type'        => 'integer',
                        'null'        => false,
                        'default'     => '0',
                        'increment'   => true,
                        'primary_key' => true,
                    ];
                    $isprimary = true;
                    break;

                case 'textbox':
                    if (!empty($properties[$name]['configuration']) && preg_match('/^\d*:(\d+)$/', $properties[$name]['configuration'], $matches)) {
                        $maxlength = $matches[1];
                    } else {
                        $maxlength = 254;
                    }
                    if (!empty($properties[$name]['defaultvalue'])) {
                        $default = $properties[$name]['defaultvalue'];
                    } else {
                        $default = '';
                    }
                    $definition = [
                        'type'        => 'varchar',
                        'size'        => $maxlength,
                        'null'        => false,
                        'default'     => $default,
                    ];
                    break;

                case 'textarea':
                case 'textarea_small':
                case 'textarea_medium':
                case 'textarea_large':
                    $definition = [
                        'type'        => 'text',
                        'size'        => 'medium',
                        'null'        => true,
                    ];
                    break;

                default:
                    $definition = [
                        'type'        => 'varchar',
                        'size'        => 254,
                        'null'        => false,
                        'default'     => '',
                    ];
                    break;
            }
            $fields[$field] = $definition;
        }
        if (!$isprimary) {
            $fields['itemid'] = [
                'type'      => 'integer',
                'null'        => false,
                'default'     => '0',
                'increment'   => false, // unique id depends on other object/table here
                'primary_key' => true,
            ];
        }

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $query is empty
        $query = xarTableDDL::createTable($table, $fields);
        if (empty($query)) {
            return;
        } // throw back
        $dbconn->Execute($query);

        sys::import('xaraya.structures.query');
        $objectlist = $this->data()->getObjectList(['name' => $myobject->name]);
        $items = $objectlist->getItems();
        $q = new Query('INSERT', $table);
        foreach ($items as $row) {
            foreach ($row as $key => $value) {
                $q->addfield($key, $value);
            }
            if (!$q->run()) {
                return;
            }
        }

        return true;
    }
}
