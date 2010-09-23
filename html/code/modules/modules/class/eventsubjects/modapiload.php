<?php
/**
 * ModApiLoad System Event Subject
 * Notifies observers when a module api is loaded (via xarMod::apiLoad)
**/
sys::import('modules.base.class.eventsubjects.event');
class ModulesModApiLoadSubject extends BaseEventSubject implements ixarEventSubject
{
    public $subject = 'ModApiLoad';
    /*
     * Constructor
     *
     * @param string $modName name of loaded api module
    **/
    public function __construct($modName)
    {
        $args = array();
        if (!empty($modName)) $args['modname'] = $modName;
        parent::__construct($args); // $this->setArgs($args);                              
    }
}
?>