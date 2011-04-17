<?php
/**
 * ModApiLoad System Event Subject
 * Notifies observers when a module is loaded (via xarMod::load)
**/
sys::import('xaraya.structures.events.subject');
class ModulesModLoadSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModLoad';
    /*
     * Constructor
     *
     * @param string $modName name of loaded module
    **/
    public function __construct($modName)
    {
        parent::__construct($modName);                             
    }
}
?>