<?php
/**
 * Create a flat table corresponding to some dynamic object definition
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
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
 *
 * @return boolean true on succes
 */
function dynamicdata_utilapi_maketable(Array $args=array())
{
    // restricted to DD Admins
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if (isset($args['objectref'])) {
        $myobject =& $args['objectref'];

    } else {
        extract($args);

        if (empty($objectid)) {
            $objectid = null;
        }
        if (empty($module_id)) {
            $module_id = xarMod::getRegID('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid)) {
            $itemid = null;
        }

        $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                             'moduleid' => $module_id,
                                             'itemtype' => $itemtype,
                                             'itemid'   => $itemid,
                                             'allprops' => true));
    }

    if (!isset($myobject) || empty($myobject->label)) {
        return;
    }

    // get the list of properties for a Dynamic Property
    $property_properties = DataPropertyMaster::getProperties(array('objectid' => 2));

    $proptypes = DataPropertyMaster::getPropertyTypes();

    $prefix = xarDB::getPrefix();
    $prefix .= '_';

    $dbconn = xarDB::getConn();

    //Load Table Maintenance API
    sys::import('xaraya.tableddl');

    $table = $prefix . 'dd_' . $myobject->name;

    // check if this table already exists
    $meta = xarMod::apiFunc('dynamicdata','util','getmeta');
    if (!empty($meta[$table])) {
        return true;
    }

    if (!empty($myobject->objectid)) {
        // get the property info directly from the database again to avoid default eval()
        $properties = DataPropertyMaster::getProperties(array('objectid' => $myobject->objectid));
    } else {
        $properties = array();
        foreach (array_keys($myobject->properties) as $name) {
            $properties[$name] = array();
            foreach (array_keys($property_properties) as $key) {
                if (isset($myobject->properties[$name]->$key)) {
                    $properties[$name][$key] = $myobject->properties[$name]->$key;
                }
            }
        }
    }

    $fields = array();
    $isprimary = false;
    foreach (array_keys($properties) as $name) {
        $field = $name;
        $type = $proptypes[$properties[$name]['type']]['name'];
        $definition = array();
        switch ($type) {
            case 'itemid':
                $definition = array('type'        => 'integer',
                                    'null'        => false,
                                    'default'     => '0',
                                    'increment'   => true,
                                    'primary_key' => true);
                $isprimary = true;
                break;

            case 'textbox':
                if (!empty($properties[$name]['validation']) && preg_match('/^\d*:(\d+)$/',$properties[$name]['validation'],$matches)) {
                    $maxlength = $matches[1];
                } else {
                    $maxlength = 254;
                }
                if (!empty($properties[$name]['defaultvalue'])) {
                    $default = $properties[$name]['defaultvalue'];
                } else {
                    $default = '';
                }
                $definition = array('type'        => 'varchar',
                                    'size'        => $maxlength,
                                    'null'        => false,
                                    'default'     => $default);
                break;

            case 'textarea':
            case 'textarea_small':
            case 'textarea_medium':
            case 'textarea_large':
                $definition = array('type'        => 'text',
                                    'size'        => 'medium',
                                    'null'        => true);
                break;

            default:
                $definition = array('type'        => 'varchar',
                                    'size'        => 254,
                                    'null'        => false,
                                    'default'     => '');
                break;
        }
        $fields[$field] = $definition;
    }
    if (!$isprimary) {
        $fields['itemid'] = array('type'      => 'integer',
                                'null'        => false,
                                'default'     => '0',
                                'increment'   => false, // unique id depends on other object/table here
                                'primary_key' => true);
    }

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($table,$fields);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    sys::import('xaraya.structures.query');
    $objectlist = DataObjectMaster::getObjectList(array('name' => $myobject->name));
    $items = $objectlist->getItems();
    $q = new Query('INSERT', $table);
    foreach ($items as $row) {
        foreach ($row as $key => $value) {
            $q->addfield($key, $value);
        }
        if (!$q->run()) return;
    }

    return true;
}

?>