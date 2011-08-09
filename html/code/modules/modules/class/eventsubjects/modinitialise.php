<?php
/**
 * ModInitialise System Event Subject
 * Notifies observers when a module is initialised (via xarMod::apiFunc('modules','admin','initialise')
**/
sys::import('xaraya.structures.events.subject');
class ModulesModInitialiseSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModInitialise';
    /*
     * Constructor
     *
     * @param string $modName name of initialised module
    **/
    public function __construct($modName)
    {
        parent::__construct($modName);                             
    }
}
?>