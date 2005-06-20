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
            $options = $this->getOptions();
        }
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        
        $data['value']   = $value;
        $data['name']    = $name;
        $data['id']      = $id;
        $data['options'] = $options;


        $data['tabindex'] =!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

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
         $args = array();
         $baseInfo = array(
                              'id'         => 34,
                              'name'       => 'radio',
                              'label'      => 'Radio Buttons',
                              'format'     => '34',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }


}


?>
