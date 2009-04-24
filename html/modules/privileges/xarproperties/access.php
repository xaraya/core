<?php
sys::import('modules.dynamicdata.class.properties.base');

class AccessProperty extends DataProperty
{
    public $id         = 30092;
    public $name       = 'access';
    public $desc       = 'Access';
    public $reqmodules = array('privileges');

    public $group      = 0;
    public $level      = 100;
    public $failure    = 0;

    public $module     = 'All';
    public $component  = 'All';
    public $instance   = 'All';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'privileges';
        $this->filepath   = 'modules/privileges/xarproperties';
        $this->template  = 'access';
    }

    public function checkInput($name = '', $value = null)
    {
        $dropdown = DataPropertyMaster::getProperty(array('name' => 'dropdown'));        
        $value = array();
        
        // Check the group
        $dropdown->options = $this->getgroupoptions();
        if (!$dropdown->checkInput($name . '_group')) return false;
        $value['group'] = $dropdown->value;
        
        // Check the level
        $dropdown->options = $this->getleveloptions();
        if (!$dropdown->checkInput($name . '_level')) return false;
        $value['level'] = $dropdown->value;
        
        // Check the failure behavior
        $dropdown->options = $this->getfailureoptions();
        if (!$dropdown->checkInput($name . '_failure')) return false;
        $value['failure'] = $dropdown->value;
        
        $this->value = $value;
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['value'])) $this->setValue($data['value']);
        $value = $this->getValue();
        if (!isset($data['level'])) $data['level'] = $value['level'];
        if (!isset($data['group'])) $data['group'] = $value['group'];
        if (!isset($data['failure'])) $data['failure'] = $value['failure'];
        
        $data['groupoptions'] = $this->getgroupoptions();
        $data['leveloptions'] = $this->getleveloptions();
        $data['failureoptions'] = $this->getfailureoptions();
        
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($data['value'])) $this->setValue($data['value']);
        $value = $this->getValue();
        if (!isset($data['level'])) $data['level'] = $value['level'];
        if (!isset($data['group'])) $data['group'] = $value['group'];
        if (!isset($data['failure'])) $data['failure'] = $value['failure'];
        
        $data['groupoptions'] = $this->getgroupoptions();
        $data['leveloptions'] = $this->getleveloptions();
        $data['failureoptions'] = $this->getfailureoptions();

        return parent::showOutput($data);
    }

    function getgroupoptions()
    {
        $anonID = xarConfigVars::get(null,'Site.User.AnonymousUID');
        $options = xarRoles::getgroups();
        $firstlines = array(
            array('id' => 0, 'name' => xarML('No requirement')),
            array('id' => $anonID, 'name' => xarML('Users not logged in')),
            array('id' => -$anonID, 'name' => xarML('Users logged in')),
        );
        return array_merge($firstlines, $options);
    }

    function getleveloptions()
    {
        $accesslevels = SecurityLevel::$displayMap;
        unset($accesslevels[-1]);
        $options = array();
        foreach ($accesslevels as $key => $value) $options[] = array('id' => $key, 'name' => $value);
        return $options;
    }

    function getfailureoptions()
    {
        $options = array(
                        array('id' => 0, 'name' => xarML('Fail silently')),
                        array('id' => 1, 'name' => xarML('Throw exception')),
                    );
        return $options;
    }
    
    function setValue($value=null)
    {
        if (!empty($value) && !is_array($value)) {
            $this->value = $value;
        } else {
            if (empty($value)) {
                $value = array(
                    'group' => $this->group,
                    'level' => $this->level,
                    'failure' => $this->failure,
                );
            }
            $this->value = serialize($value);
        }
    }

    public function getValue()
    {
        try {
            $value = unserialize($this->value);
        } catch(Exception $e) {
            $value = array(
                'group' => $this->group,
                'level' => $this->level,
                'failure' => $this->failure,
            );
        }
        return $value;
    }

    public function check(Array $data=array())
    {
        if (isset($data['group']))     $this->group = $data['group'];
        if (isset($data['level']))     $this->level = $data['level'];
        if (isset($data['module']))    $this->module = $data['module'];
        if (isset($data['component'])) $this->component = $data['component'];
        if (isset($data['instance']))  $this->instance = $data['instance'];
        
        $access = false;
        $anonID = xarConfigVars::get(null,'Site.User.AnonymousUID');
        if (($this->group == $anonID)) {
            if (!xarUserIsLoggedIn()) $access = true;
        } elseif ($this->group == -$anonID) {
            if (xarUserIsLoggedIn()) $access = true;
        } elseif ($this->group) {
            $group = xarRoles::getRole($this->group);
            $thisuser = xarCurrentRole();
            if (is_object($group)) {
                if ($thisuser->isAncestor($group)) $access = true;
            } 
        } else {
            if (xarSecurityCheck('', 
                              0, 
                              $this->component, 
                              $this->instance, 
                              $this->module, 
                              '',
                              0,
                              $this->level)) {$access = true;
            }
        }
        return $access;
    }
}
?>