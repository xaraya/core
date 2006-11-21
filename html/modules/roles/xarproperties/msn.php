<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/* Include the base class */
sys::import('modules.base.xarproperties.urlicon');
/**
 * Handle MSN property
 * @author mikespub <mikespub@xaraya.com>
 */
class MSNProperty extends URLIconProperty
{
    public $id         = 30;
    public $name       = 'msn';
    public $desc       = 'MSN Messenger';
    public $reqmodules = array('roles');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'msn';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function validateValue($value = null)
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

    public function showInput(Array $data = array())
    {
        if(!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] ='';
        if(!empty($data['value'])) {
            $data['link'] = xarVarPrepForDisplay("TODO: what's the link for MSN ?" .$data['value']);
        }
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
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
