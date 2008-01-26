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

/**
 * Handle Username Property
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.textbox');

class UsernameProperty extends TextBoxProperty
{
    public $id         = 7;
    public $name       = 'username';
    public $desc       = 'Username';
    public $reqmodules = array('roles');

    public $rawvalue   = null;

    public $initialization_linkrule                = 0;
    public $initialization_existrule               = 0;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'username';
        $this->filepath   = 'modules/roles/xarproperties';
        $this->parseValidation($this->validation);
    }

    public function validateValue($value = null)
    {
        // Save the incoming value until validation is successful
        $this->rawvalue = $value;

        // Validate as a text box
        if (!parent::validateValue($value)) return false;

        if (!isset($value)) {
            $value = $this->value;
        }

        /* CHECKME: was this good for anything?
        if (empty($value)) {
            $value = xarUserGetVar('id');
        }
        */

        if (empty($value)) return true;

        $role = xarRoles::ufindRole($value);

        switch ((int)$this->initialization_existrule) {
            case 1:
            if (!empty($role)) {
                $this->invalid = xarML('user #(1) already exists', $value);
                return false;
            }
            break;

            case 2:
            if (empty($role)) {
                $this->invalid = xarML('user #(1) does not exist', $value);
                return false;
            }
            break;

            case 0:
            default:
        }

        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        if (isset($this->rawvalue)) {
            $data['value'] = $this->rawvalue;
            $data['user'] = $this->rawvalue;
        } else {
            if (!isset($value)) $value = $this->value;
            if (empty($value))  {
                $data['user'] = '';
                $data['value']= 0;
            } else {
                if(is_numeric($value)) {
                    try {
                        $user = xarUserGetVar('uname', $value);
                        // Does this make sense? The user should already have been checked before storing
                        if (empty($user))
                            $user = xarUserGetVar('name', $value);
                    } catch(NotFoundExceptions $e) {
                        $user = $value;
                    }
                } else {
                    $user = $value;
                }
                    $data['user'] = xarVarprepForDisplay($user);
                    $data['value']= $value;
            }
        }

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($this->rawvalue)) {
            $data['value'] = $this->rawvalue;
            $data['user'] = $this->rawvalue;
        } else {
            extract($data);
            if (!isset($value)) $value = $this->value;
            if (empty($value))  $value = xarUserGetVar('id');
            if(is_numeric($value)) {
                try {
                    $user = xarUserGetVar('name', $value);
                    if (empty($user))
                        $user = xarUserGetVar('uname', $value);
                } catch(NotFoundExceptions $e) {
                    $user = $value;
                }
            } else {
                $user = $value;
            }

            $data['user']  = xarVarPrepForDisplay($user);
            $data['value'] = $value;

            if ($this->validation) {
                $data['linkurl'] = xarModURL('roles','user','display',array('id' => $value));
            } else {
                $data['linkurl'] = "";
            }
        }
        return parent::showOutput($data);
    }
}
?>
