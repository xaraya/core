<?php
/**
 * Float box property
 *
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
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * Class to handle floatbox property
 *
 * @package dynamicdata
 */
class Dynamic_FloatBox_Property extends Dynamic_TextBox_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->size      = 10;
        $this->maxlength = 30;
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 17;
        $info->name = 'floatbox';
        $info->desc = 'Number Box (float)';

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
            $this->value = (float) $value;
            if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
                $this->invalid = xarML('float : allowed range is between #(1) and #(2)',$this->min,$this->max);
                $this->value = null;
                return false;
            } elseif (isset($this->min) && $this->min > $value) {
                $this->invalid = xarML('float : must be #(1) or more',$this->min);
                $this->value = null;
                return false;
            } elseif (isset($this->max) && $this->max < $value) {
                $this->invalid = xarML('float : must be #(1) or less',$this->max);
                $this->value = null;
                return false;
            }
        } else {
            $this->invalid = xarML('float');
            $this->value = null;
            return false;
        }
        return true;
    }

    // Trick: use the parent method with a different template :-)
    // No trick: that how it should have been from the start :-)
    function showValidation($args = array())
    {
        // allow template override by child classes
        if (!isset($args['template'])) {
            $args['template'] = $this->getTemplate();
        }

        return parent::showValidation($args);
    }
}

?>
