<?php
/**
 *
 * Dynamic Data Object Parent Property
 * A dropdown giving the extensions based on a given module
 * to use for displaying possible parents of an extension
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author Marc Lutolf <random@xaraya.com>
*/

// We base it on the object property
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the object parent property
 *
 * @package dynamicdata
 */
class Dynamic_ObjectParent_Property extends Dynamic_Select_Property
{
    function Dynamic_ObjectParent_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (count($this->options) == 0) {
            $objects =& Dynamic_Object_Master::getObjects();
            if (!isset($objects)) {
                $objects = array();
            }
			$this->options = $this->filterOptions($objects, $this->validation);
        }
    }

    function showInput($args = array())
    {
        $data=array();
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

        if (isset($validation)) {
            $objects =& Dynamic_Object_Master::getObjects();
            if (!isset($objects)) {
                $objects = array();
            }
		}
		$data['options'] = $this->filterOptions($objects, $validation);

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        if (empty($template)) {
            $template = 'dropdown';
        }
        return xarTplProperty('base', $template, 'showinput', $data);
    }

    // Return a list of array(id => value) for the possible options
    function filterOptions($objects, $option_value=null)
    {
		$options[] = array('id' => 0, 'name' => xarML('current module'));
		foreach ($objects as $objectid => $object) {
			if (!empty($option_value)) {
				if ($object['moduleid'] == $option_value) {
					$ancestors = xarModAPIFunc('dynamicdata','user','getancestors',array('objectid' => $objectid));
					$name ="";
					foreach ($ancestors as $parent) $name .= $parent['name'] . ".";
					$name = trim($name,".");
					$options[] = array('id' => $object['itemtype'], 'name' => $name);
					// Now get the module defined itemtypes
				}
			} else {
				$ancestors = xarModAPIFunc('dynamicdata','user','getancestors',array('objectid' => $objectid));
				$name ="";
				foreach ($ancestors as $parent) $name .= $parent['name'] . ".";
				$name = trim($name,".");
				$options[] = array('id' => $object['itemtype'], 'name' => $name);
			}
		}
		if ($object['moduleid'] == $option_value) {
			$info = xarModGetInfo($object['moduleid']);
			xarModAPILoad($info['name']);
			if (function_exists($info['name'] . "_userapi_getitemtypes")) {
				$types = xarModAPIFunc($info['name'],'user','getitemtypes');
//				$types = xarModAPIFunc('blocks','user','getitemtypes');
				foreach ($types as $key => $value) $options[] = array('id' => $key, 'name' => $value['label']);
			}
		}
		return $options;
    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'             => 600,
                              'name'           => 'objectparent',
                              'label'          => 'Parent',
                              'format'         => '600',
                              'validation'     => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}
?>
