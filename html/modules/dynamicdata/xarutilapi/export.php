<?php

/**
 * Export an object definition or an object item to XML
 */
function dynamicdata_utilapi_export($args)
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
        if (empty($modid)) {
            $modid = xarModGetIDFromName('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid)) {
            $itemid = null;
        }

        $myobject = new Dynamic_Object(array('objectid' => $objectid,
                                             'moduleid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid'   => $itemid,
                                             'allprops' => true));
    }

    if (!isset($myobject) || empty($myobject->label)) {
        return;
    }

    // get the list of properties for a Dynamic Object
    $object_properties = Dynamic_Property_Master::getProperties(array('objectid' => 1));

    // get the list of properties for a Dynamic Property
    $property_properties = Dynamic_Property_Master::getProperties(array('objectid' => 2));

    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');

    $xml = '';

    $xml .= '<object name="'.$myobject->name.'">'."\n";
    foreach (array_keys($object_properties) as $name) {
        if ($name != 'name' && isset($myobject->$name)) {
            if (is_array($myobject->$name)) {
                $xml .= "  <$name>\n";
                foreach ($myobject->$name as $field => $value) {
                    $xml .= "    <$field>" . xarVarPrepForDisplay($value) . "</$field>\n";
                }
                $xml .= "  </$name>\n";
            } else {
                $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->$name) . "</$name>\n";
            }
        }
    }
    $xml .= "  <properties>\n";
    foreach (array_keys($myobject->properties) as $name) {
        $xml .= '    <property name="'.$name.'">' . "\n";
        foreach (array_keys($property_properties) as $key) {
            if ($key != 'name' && isset($myobject->properties[$name]->$key)) {
                if ($key == 'type') {
                    $xml .= "      <$key>".xarVarPrepForDisplay($proptypes[$myobject->properties[$name]->$key]['name'])."</$key>\n";
                } else {
                    $xml .= "      <$key>".xarVarPrepForDisplay($myobject->properties[$name]->$key)."</$key>\n";
                }
            }
        }
        $xml .= "    </property>\n";
    }
    $xml .= "  </properties>\n";
    $xml .= "</object>\n";

    return $xml;
}

?>
