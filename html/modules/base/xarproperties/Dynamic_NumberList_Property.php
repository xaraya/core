<?php
/**
 * Numberlist property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the numberlist property
 *
 * @package dynamicdata
 */
class Dynamic_NumberList_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);

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

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 16;
        $info->name = 'integerlist';
        $info->desc = 'Number List';

        return $info;
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
}

?>
