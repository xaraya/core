<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/* Include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle Userlist property
 * @author mikespub <mikespub@xaraya.com>
 */
class UserListProperty extends SelectProperty
{
    public $id         = 37;
    public $name       = 'userlist';
    public $desc       = 'User List';
    public $reqmodules = array('roles');

    public $grouplist = array();
    public $userstate = -1;
    public $showlist  = array();
    public $orderlist = array();
    public $showglue  = '; ';

    public $initialization_userlist_user_state = xarRoles::ROLES_STATE_ALL; // Select only users of the given state
    public $initialization_userlist_userlist   = '';                        // Select only these usera
    public $initialization_orderlist = '';
    public $initialization_userlist_group_list = '';                        // Select only users who are members of the given group(s)
    public $validation_override                = true;                      // Allow values not in the dropdown
    public $display_showfields = '';
    public $display_showglue = ', ';

    /*
    * Options available to user selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   show:field[,field] - show the specified field(s) in the select item
    *   showglue:string - string to join multiple fields together
    *   order:field[,field] - order the selection by the specified field
    * where
    *   field - name|uname|email|id
    */

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template  = 'userlist';
        $this->filepath  = 'modules/roles/xarproperties';

        if (count($this->options) == 0) {
            $select_options = array();
            if (!empty($this->initialization_orderlist)) $select_options['order'] = explode(',', $this->initialization_orderlist);
//            $users = xarMod::apiFunc('roles', 'user', 'getall', $select_options);
            // FIXME: this function needs to be reviewed
            $users = array();
            // Loop for each user retrived and populate the options array.
            if (empty($this->display_showfields)) {
                // Simple case (default) -
                foreach ($users as $user) {
                    $this->options[] = array('id' => $user['id'], 'name' => $user['name']);
                }
            } else {
                $showfields = explode(',',$this->display_showfields);
                // Complex case: allow specific fields to be selected.
                foreach ($users as $user) {
                    $namevalue = array();
                    foreach ($showfields as $showfield) {
                        $namevalue[] = $user[$showfield];
                    }
                    $this->options[] = array('id' => $user['id'], 'name' => implode($this->showglue, $namevalue));
                }
            }
        }
    }

    // TODO: validate the selected user against the specified group(s).
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            // check if this is a valid user id
            try {
                $uname = xarUserGetVar('uname', $value);
                if (isset($uname)) {
                    return true;
                }
            } catch (NotFoundExceptions $e) {
                // Nothing to do?
            }
        } elseif (empty($value)) {
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
        $this->value = null;
        return false;
    }

    public function showInput(Array $data = array())
    {
        // CHECKME: Remove this?
        if (isset($data['group_list'])) $this->validation_userlist_group_list = $data['group_list'];
        
        if (isset($data['group']))      $this->validation_userlist_group_list = $data['group'];
        if (isset($data['state']))      $this->initialization_userlist_user_state = $data['state'];

        return parent::showInput($data);
    }

    // TODO: format the output according to the 'showfields'.
    // TODO: provide an option to allow admin to decide whether to wrap the user
    // in a link or not.
    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;

        if (empty($value)) {
            $user = '';
        } else {
            try {
                $user = xarUserGetVar('name', $value);
                if (empty($user)) {
                    $user = xarUserGetVar('uname', $value);
                }
            } catch (NotFoundExceptions $e) {
                // Nothing to do?
            }
        }
        $data['value'] = $value;
        $data['user'] = $user;

        return parent::showOutput($data);
    }

    public function getOptions()
    {
        $select_options = array();
        /*
        if (!empty($this->validation_ancestorgroup_list)) {
            $select_options['ancestor'] = $this->validation_ancestorgroup_list;
        }
        if (!empty($this->validation_parentgroup_list)) {
            $select_options['parent'] = $this->validation_parentgroup_list;
        }
        */
        $select_options['state'] = $this->initialization_userlist_user_state;
        if (!empty($this->initialization_userlist_group_list)) {
            $select_options['grouplist'] = $this->initialization_userlist_group_list;
        }

        // Get the candidates
        $options = xarMod::apiFunc('roles', 'user', 'getall', $select_options);
        
        // Adjust for the fields to show
        if (!empty($options) && !empty($this->display_showfields)) {        
            $testrow = $options[0];
            $fields = explode(',',$this->display_showfields);
            foreach ($fields as $k => $v) {
                if (!isset($testrow[trim($v)])) unset($fields[$k]);
            }
            foreach($options as $key => $value) {
                $namestring = '';
                foreach ($fields as $v) {
                    $v = $value[trim($v)];
                    if (empty($v)) continue;
                    $namestring .= $v . $this->display_showglue;
                }
                $namestring = substr($namestring, 0, -strlen($this->display_showglue));
                $options[$key]['name'] = $namestring;
            }
        }
        return $options;
    }
}

sys::import('modules.dynamicdata.class.properties.interfaces');

class UserListPropertyInstall extends UserListProperty implements iDataPropertyInstall
{
    public function install(Array $data=array())
    {
        $dat_file = sys::code() . 'modules/roles/xardata/userlist_configurations-dat.xml';
        $data = array('file' => $dat_file);
        try {
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
    
}
?>