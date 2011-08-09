<?php
/**
 * ModRemove System Event Subject
 * Notifies observers when a module is removed (via xarMod::apiFunc('modules','admin','remove')
**/
sys::import('xaraya.structures.events.subject');
class ModulesModRemoveSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModRemove';
    /*
     * Constructor
     *
     * @param string $modName name of removed module
    **/
    public function __construct($modName)
    {
        parent::__construct($modName);                             
    }
}
?>