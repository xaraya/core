<?php
/**
 * File: $Id$
 *
 * Dynamic Radio Button Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * handle radio buttons property
 *
 * @package dynamicdata
 */
class Dynamic_RadioButtons_Property extends Dynamic_Select_Property
{
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        $data['value']   = $value;
        $data['name']    = $name;
        $data['options'] = $options;


        /*
        $out = '';
        $idx = 1;
        foreach ($options as $option) {
            $out .= '<input type="radio" name="'.$name.'" id="'.$name.'_'.$idx.'" value="'.$option['id'].'"';
            if ($option['id'] == $value) {
                $out .= ' checked="checked">';
            } else {
                $out .= '>';
            }
            $out .= '<label for="'.$name.'_'.$idx.'"> '.$option['name'].' </label></input>';
            $idx++;
        }
        $out .= (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        return $out;
        */
        
        $data['tabindex'] =!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid']  =!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '';

        $template="radio";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);

    }

    // default methods from Dynamic_Select_Property

	/**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
	 **/
	 function getBasePropertyInfo()
	 {
	 	$baseInfo = array(
                              'id'         => 34,
                              'name'       => 'radio',
                              'label'      => 'Radio Buttons',
                              'format'     => '34',
                              'validation' => '',
							'source'     => '',
							'dependancies' => '',
							'requiresmodule' => '',
							// ...
						   );
		return $baseInfo;
	 }


}


?>
