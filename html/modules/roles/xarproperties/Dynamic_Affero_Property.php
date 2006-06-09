<?php
/**
 * Handle Affero property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/**
 * Handle Affero property
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * include the base class
 */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

class Dynamic_Affero_Property extends Dynamic_URLIcon_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'affero';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id   = 40;
        $info->name = 'affero';
        $info->desc = 'Affero Username';

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
                $this->invalid = xarML('Affero Name');
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
        extract($data);
        if (!isset($value)) $value = $this->value;

        $link = '';
        if (!empty($value)) {
            $link = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.$value;
        } 
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['link']     = xarVarPrepForDisplay($link);
        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        
        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.$data['value'];
           
        }
        return parent::showOutput($data);
    }
}
?>
