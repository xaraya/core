<?php
/**
 *
 * Dynamic Data Object Reference Property (foreign key like dropdown)
 * You can specify the to be referenced object and what property values
 * to use for displayinig and to store in the (foreign key) field
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo match the type of the local field to the store property type (must be the same)
 * @todo extra option to limit displaying
 * @todo rules for when the referenced object prop value gets deleted etc.
 * @todo foreign keys which consist of multiple attributes (bad design, but in practice it might come in handy)
 * @todo make the different loops a bit more efficient.
*/

// We base it on the select property
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the objectreference property
 *
 * @package dynamicdata
 */
class Dynamic_ObjectRef_Property extends Dynamic_Select_Property
{
    public $id = 507;
    public $name = 'objectref';
    public $label = 'Select value from other object';
    public $format = '507';
    public $requiresmodule = 'dynamicdata';

    // We explicitly use names here instead of id's, so we are independent of
    // how dd assigns them at a given time. Otherwise the validation is not
    // exportable to other sites.
    var $refobject    = 'objects';    // Name of the object we want to reference
    var $store_prop   = 'objectid';   // Name of the property we want to use for storage
    var $display_prop = 'name';       // Name of the property we want to use for displaying.


    // Prepare data to be rendered when an input function is called on the property
    // We dont use the parent because we use xarTplProperty at the end of this
    // function and do template overriding slightly different.
    function showInput($args = array())
    {
        $data=array(); $template = null;
        extract($args);

        if (!isset($value)) {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }

        if (!isset($options) || count($options) == 0) {
            $data['options'] = $this->getOptions();
        } else {
            $data['options'] = $options;
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name'] = $name;
        }

        if (empty($id)) {
            $data['id'] = $data['name'];
        } else {
            $data['id']= $id;
        }

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        return xarTplProperty('dynamicdata', 'objectref', 'showinput', $data, $template);
    }

    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
        // The object we need to query is in $this->refobject, we display the value of
        // the property in $this->display_prop and the id comes from $this->store_prop
        $objInfo  = Dynamic_Object_Master::getObjectInfo(array('name' => $this->refobject));

        // TODO: do we need to check whether the properties are actually in the object?
        $items =  xarModApiFunc('dynamicdata', 'user', 'getitems', array (
                                    'modid'    => $objInfo['moduleid'],
                                    'itemtype' => $objInfo['itemtype'],
                                    'fieldlist'=> $this->display_prop . ',' . $this->store_prop)
                             );
        $options = array();
        foreach($items as $item) {
            $options[] = array('id' => $item[$this->store_prop], 'name' => $item[$this->display_prop]);
        }
        return $options;
    }


    // Produce option(id,value) and value to pass to template
    // We cant trust the parent right now because that is using xarTplModule and not xarTplProperty
    function showOutput($args = array())
    {
        $data=array(); $template = null;
        extract($args);
        if (isset($value)) $this->value = $value;

        $data['value'] = $this->value;
        // get the option corresponding to this value
        $result = $this->getOption();
        // only apply xarVarPrepForDisplay on strings, not arrays et al.
        if (!empty($result) && is_string($result)) $result = xarVarPrepForDisplay($result);
        $data['option'] = array('id' => $this->value, 'name' => $result);
        // If children call us, they can pass in template
        return xarTplProperty('dynamicdata', 'objectref', 'showoutput', $data, $template);
    }

    // Show the validation output.
    function showValidation($args = array())
    {
        $data = array(); $template = null;  $data['properties'] = array();
        extract($args);

        // If we have a value, store an parse it, so our values are current
        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }

        // Determine the objectid, so we can produce the combobox automatically.
        $object = Dynamic_Object_Master::getObjectInfo(array('name' => $this->refobject));
        $data['objectid'] = $object['objectid'];
        $data['name']     = !empty($name) ? $name : 'dd_'.$this->id;

        // Get the properties which belong to this object to display in the second dropdown
        $props = Dynamic_Property_Master::getProperties(array('objectid' => $object['objectid']));
        $data['properties'] = $props;

        if(isset($props[$this->display_prop])) {
            $data['display_propid'] = $props[$this->display_prop]['id'];
        } else {
            // Just take the first
            $first = array_shift($props);
            $data['display_propid'] = $first['id'];
            array_unshift($props, $first);
        }

        if(isset($props[$this->store_prop])) {
            $data['store_propid'] = $props[$this->store_prop]['id'];
        } else {
            // Just take the first
            $first = array_shift($props);
            $data['store_propid'] = $first['id'];
        }
        return xarTplProperty('dynamicdata','objectref','validation',$data, $template);
    }

    // Parse the validation string and set the appropriate values to the variables of this class
    function parseValidation($validation = '')
    {
        // Validation is supposed to be objectname:display_propname:store_propname
        // See class variables on top for description
        $sep = ':';
        if(is_string($validation) && strchr($validation,$sep)) {
            list($objectname,$display_prop,$store_prop) = explode($sep,$validation);
            if($objectname != '' && is_string($objectname)) $this->refobject = $objectname;
            if($display_prop != '' && is_string($display_prop)) $this->display_prop = $display_prop;
            if($store_prop != '' && is_string($store_prop)) $this->store_prop = $store_prop;
        }
    }

    // Get the modified values and update the validation
    function updateValidation($args = array())
    {
        $sep = ':';
        extract($args['validation']);

        if(isset($objectid))  {
            $object = Dynamic_Object_Master::getObjectInfo(array('objectid' => $objectid));
            $this->refobject = $object['name'];

            // This gets a name index array of the props
            $props =  Dynamic_Property_Master::getProperties(array('objectid' => $objectid));
            // Traverse them in reverse order, so we end up with the first if object and proplist dont match up
            $props = array_reverse($props,true);

            if(isset($display_propid)) {
                foreach($props as $propinfo) {
                    $data['display_propid'] = $propinfo['id'];
                    $this->display_prop = $propinfo['name'];
                    if($propinfo['id'] == $display_propid) {
                        break;
                    }
                }
            }
            if(isset($store_propid)) {
                foreach($props as $propinfo) {
                    $data['store_propid'] = $propinfo['id'];
                    $this->store_prop = $propinfo['name'];
                    if($propinfo['id'] == $store_propid) {
                        break;
                    }
                }
            }
        }
        $this->validation = $this->refobject.$sep.$this->display_prop.$sep.$this->store_prop;
        return true;
    }
}
?>
