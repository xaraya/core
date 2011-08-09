<?php
/**
 * ModAcativate System Event Subject
 * Notifies observers when a module is activated (via xarMod::apiFunc('modules','admin','activate')
**/
sys::import('xaraya.structures.events.subject');
class ModulesModActivateSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModActivate';
    /*
     * Constructor
     *
     * @param string $modName name of activated module
    **/
    public function __construct($modName)
    {
        parent::__construct($modName);                             
    }
}
?>