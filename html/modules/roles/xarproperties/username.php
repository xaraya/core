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
class UsernameProperty extends DataProperty
{
    public $id         = 7;
    public $name       = 'username';
    public $desc       = 'Username';
    public $reqmodules = array('roles');

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
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = xarUserGetVar('id');
        }
        // check that the user exists, but dont except
        if (is_numeric($value)) {
            try {
                $user = xarUserGetVar('uname', $value);
            } catch (NotFoundExceptions $e) {
                // Nothing to do?
            }
        } else {
            $role = xarRoles::findRole($value);
            if (!empty($role)) xarRoles::ufindRole($value);
            try {
                $user = $value;
                $value = $role->getID();
            } catch (NotFoundExceptions $e) {}
        }

        if (!is_numeric($value) || empty($user)) {
            $this->invalid = xarML('user: #(1)', $this->name);
            $this->value = null;
            return false;
        } else {
            $this->value = $value;
            return true;
        }
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;
        if (empty($value))  $value = xarUserGetVar('id');

        try {
            $user = xarUserGetVar('name', $value);
            if (empty($user))
                $user = xarUserGetVar('uname', $value);
        } catch(NotFoundExceptions $e) {
            $user = $value;
        }

        $data['user'] = xarVarprepForDisplay($user);
        $data['value']= $value;
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;
        if (empty($value))  $value = xarUserGetVar('id');

        try {
            $user = xarUserGetVar('name', $value);
            if (empty($user))
                $user = xarUserGetVar('uname', $value);
        } catch(NotFoundExceptions $e) {
            $user = $value;
        }

        $data['user']  = xarVarPrepForDisplay($user);
        $data['value'] = $value;

        if ($this->validation) {
            $data['linkurl'] = xarModURL('roles','user','display',array('id' => $value));
        } else {
            $data['linkurl'] = "";
        }
        return parent::showOutput($data);
    }
}
?>
