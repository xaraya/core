<?php
/**
 * ModApiLoad System Event Subject
 * Notifies observers when a module is loaded (via xarMod::load)
**/
sys::import('modules.base.class.eventsubjects.event');
class ModulesModLoadSubject extends BaseEventSubject implements ixarEventSubject
{
    public $subject = 'ModLoad';
    /*
     * Constructor
     *
     * @param string $modName name of loaded module
    **/
    public function __construct($modName)
    {
        $args = array();
        if (!empty($modName)) $args['modname'] = $modName;
        parent::__construct($args); // $this->setArgs($args);                              
    }
}
?>