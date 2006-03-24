<?php
/**
 * Dynamic Item Id property Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_NumberBox_Property.php";

/**
 * handle item id property
 *
 * @package dynamicdata
 */
class Dynamic_ItemID_Property extends Dynamic_NumberBox_Property
{
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

// TODO: evaluate if we want some other output here
//    function showInput($name = '', $value = null)
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (isset($value)) {
            $data['value']= xarVarPrepForDisplay($value);
        } else {
            $data['value']= xarVarPrepForDisplay($this->value);
        }

        // Note: item ids are read-only, even (especially) in input forms

        if (!isset($template)) {
            $template = 'itemid';
        }
        return xarTplProperty('dynamicdata', $template, 'showinput', $data);
    }
    // default methods from Dynamic_NumberBox_Property

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
                              'id'         => 21,
                              'name'       => 'itemid',
                              'label'      => 'Item ID',
                              'format'     => '21',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>