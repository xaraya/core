<?php
/**
 * Handle Yahoo property
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
 * Handle Yahoo property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

class Dynamic_Yahoo_Property extends Dynamic_URLIcon_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'yahoo';
		$this->filepath   = 'modules/roles/xarproperties';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('roles');
        $info->id     = 31;
        $info->name   = 'yahoo';
        $info->desc   = 'Yahoo Messenger';
        $info->reqmodules = array('roles');
        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (preg_match('/^[a-z0-9_-]+$/i',$value)) { // TODO: refine this !?
                $this->value = $value;
            } else {
                $this->invalid = xarML('Yahoo Messenger');
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
            $link = 'http://edit.yahoo.com/config/send_webmesg?.target='.$value.'&.src=pg';
        }
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['link']     = xarVarPrepForDisplay($link);

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        if (!empty($data['value'])) {
            $data['link'] = 'http://edit.yahoo.com/config/send_webmesg?.target='.$data['value'].'&.src=pg';
            $data['link']=xarVarPrepForDisplay($data['link']);
        }
        return parent::showOutput($data);
    }
}
?>
