<?php
/**
 * ModApiLoad System Event Subject
 * Notifies observers when a module api is loaded (via xarMod::apiLoad)
**/
sys::import('xaraya.structures.events.subject');
class ModulesModApiLoadSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModApiLoad';
    /*
     * Constructor
     *
     * @param string $modName name of loaded api module
    **/
    public function __construct($modName)
    {
        parent::__construct($modName);                               
    }
}
?>