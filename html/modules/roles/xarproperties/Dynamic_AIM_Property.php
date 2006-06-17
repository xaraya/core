<?php
/**
 * Handle AIM property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * Handle AIM property
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Include the base class
 */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

/**
 * Class to handle the AIM property
 *
 * @package dynamicdata
 */
class Dynamic_AIM_Property extends Dynamic_URLIcon_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'aim';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id   = 29;
        $info->name = 'aim';
        $info->desc = 'AIM Screen Name';
		$info->filepath   = 'modules/roles/xarproperties';

        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_string($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('AIM Address');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    function showInput($data = array())
    {
        if(!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] ='';
        if(!empty($data['value'])) {
            $data['link'] = 'aim:goim?screenname='.$data['value'].'&message='.xarML('Hello+Are+you+there?');
        }
        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        
        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'aim:goim?screenname='.$data['value'].'&message='.xarML('Hello+Are+you+there?');
           
        }
        return parent::showOutput($data);
    }
}
?>
