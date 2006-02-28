<?php
/**
 * Handle MSN property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/** 
 * Handle MSN property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

class Dynamic_MSN_Property extends Dynamic_URLIcon_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'msn';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->id     = 30;
        $info->name   = 'msn';
        $info->desc  = 'MSN Messenger';
        $info->reqmodules = array('roles');
        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui'; // TODO: verify this !
            if (preg_match($regexp,$value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('MSN Messenger');
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
            $data['link'] = xarVarPrepForDisplay("TODO: what's the link for MSN ?" .$data['value']);
        }
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
            $link = "TODO: what's the link for MSN ?".$value;
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
