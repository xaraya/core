<?php
/**
 * File: $Id$
 *
 * Dynamic Data Number Box Property
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
include_once "includes/properties/Dynamic_TextBox_Property.php";

/**
 * handle a numberbox property
 *
 * @package dynamicdata
 */
class Dynamic_NumberBox_Property extends Dynamic_TextBox_Property
{
    var $size = 10;
    var $maxlength = 30;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($value) || $value === '') {
            if (isset($this->min)) {
                $this->value = $this->min;
            } elseif (isset($this->max)) {
                $this->value = $this->max;
            } else {
                $this->value = 0;
            }
        } elseif (is_numeric($value)) {
            $value = intval($value);
            if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
                $this->invalid = xarML('integer : allowed range is between #(1) and #(2)',$this->min,$this->max);
                $this->value = null;
                return false;
            } elseif (isset($this->min) && $this->min > $value) {
                $this->invalid = xarML('integer : must be #(1) or more',$this->min);
                $this->value = null;
                return false;
            } elseif (isset($this->max) && $this->max < $value) {
                $this->invalid = xarML('integer : must be #(1) or less',$this->max);
                $this->value = null;
                return false;
            }
            $this->value = $value;
        } else {
            $this->invalid = xarML('integer');
            $this->value = null;
            return false;
        }
        return true;
    }

    // default showInput() from Dynamic_TextBox_Property

    // default showOutput() from Dynamic_TextBox_Property


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
                          'id'         => 15,
                          'name'       => 'integerbox',
                          'label'      => 'Number Box',
                          'format'     => '15',
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

    // Trick: use the parent method with a different template :-)
    function showValidation($args = array())
    {
        // allow template override by child classes
        if (!isset($args['template'])) {
            $args['template'] = 'floatbox';
        }

        return parent::showValidation($args);
    }

    // default updateValidation() from Dynamic_TextBox_Property

}

?>
