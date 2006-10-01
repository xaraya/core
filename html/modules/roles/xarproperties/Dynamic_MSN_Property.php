<?php
/**
 * Handle MSN property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Handle MSN property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
sys::import('modules.base.xarproperties.Dynamic_URLIcon_Property');

class Dynamic_MSN_Property extends Dynamic_URLIcon_Property
{
    public $id         = 30;
    public $name       = 'msn';
    public $desc       = 'MSN Messenger';
    public $reqmodules = array('roles');

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'msn';
        $this->filepath   = 'modules/roles/xarproperties';
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

    function showOutput($data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = "TODO: what's the link for MSN ?".$data['value'];
        }
        return parent::showOutput($data);
    }
}
?>
