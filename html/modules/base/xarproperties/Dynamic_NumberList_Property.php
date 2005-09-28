<?php
/**
 * File: $Id$
 *
 * Dynamic Data Number List Property
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
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the numberlist property
 *
 * @package dynamicdata
 */
class Dynamic_NumberList_Property extends Dynamic_Select_Property
{
    var $min = null;
    var $max = null;

    function Dynamic_NumberList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        // check validation for allowed min/max values
        if (count($this->options) == 0 && !empty($this->validation) && strchr($this->validation,':')) {
            list($min,$max) = explode(':',$this->validation);
            if ($min !== '' && is_numeric($min)) {
                $this->min = intval($min);
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = intval($max);
            }
            if (isset($this->min) && isset($this->max)) {
                for ($i = $this->min; $i <= $this->max; $i++) {
                    $this->options[] = array('id' => $i, 'name' => $i);
                }
            } else {
                // you're in trouble :)
            }
        }
    }

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
            $this->value = intval($value);
        } else {
            $this->invalid = xarML('integer');
            $this->value = null;
            return false;
        }
        if (count($this->options) == 0 && (isset($this->min) || isset($this->max)) ) {
            if ( (isset($this->min) && $this->value < $this->min) ||
                 (isset($this->max) && $this->value > $this->max) ) {
                $this->invalid = xarML('integer in range');
                $this->value = null;
                return false;
            }
        } elseif (count($this->options) > 0) {
            foreach ($this->options as $option) {
                if ($option['id'] == $this->value) {
                    return true;
                }
            }
            $this->invalid = xarML('integer in selection');
            $this->value = null;
            return false;
        } else {
            $this->invalid = xarML('integer selection');
            $this->value = null;
            return false;
        }
    }

    // default showInput() from Dynamic_Select_Property

    // default showOutput() from Dynamic_Select_Property


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
                              'id'         => 16,
                              'name'       => 'integerlist',
                              'label'      => 'Number List',
                              'format'     => '16',
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
