<?php
sys::import('modules.base.class.eventsubjects.event');
class ModulesModLoadSubject extends BaseEventSubject implements ixarEventSubject
{
    protected $subject = 'ModLoad';
    public function __construct($modName)
    {
        $args = array();
        if (!empty($modName)) $args['modname'] = $modName;
        parent::__construct($args); // $this->setArgs($args);                              
    }
}
?>