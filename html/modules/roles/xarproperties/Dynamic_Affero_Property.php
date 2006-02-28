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

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data = array();

        if (!empty($value)) {
            $link = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.$value;
            $data['link'] = xarVarPrepForDisplay($link);

            if (!empty($this->icon)) {
                $data['value']= $this->value;
                $data['icon'] = $this->icon;
                $data['name'] = $this->name;
                $data['id']   = $this->id;
                $data['image']= xarVarPrepForDisplay($this->icon);

            if (empty($module)) {
            $module = $this->getModule();
        }
        if (empty($template)) {
            $template = $this->getTemplate();
        }

        return xarTplProperty($module, $template, 'showoutput', $data);
           }
        }
        return '';
    }
}
?>
