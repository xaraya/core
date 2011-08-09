<?php
/**
 * ModDeactivate System Event Subject
 * Notifies observers when a module is deactivated (via xarMod::apiFunc('modules','admin','deactivate')
**/
sys::import('xaraya.structures.events.subject');
class ModulesModDeactivateSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModDeactivate';
    /*
     * Constructor
     *
     * @param string $modName name of deactivated module
    **/
    public function __construct($modName)
    {
        parent::__construct($modName);                             
    }
}
?>