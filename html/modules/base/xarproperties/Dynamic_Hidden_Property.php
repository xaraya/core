<?php
/**
 * Hidden property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */

/**
 * Class to handle hidden properties
 * @author mikespub <mikespub@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_Hidden_Property extends Dynamic_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'hidden';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 18;
        $info->name = 'hidden';
        $info->desc = 'Hidden';

        return $info;
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }

    function showOutput($args = array())
    {
        extract($args);

        $data=array();
        $data['hiddenvalue']='';

        if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showoutput', $data);
    }
}
?>
