<?php
/**
 * Wrapper for observers calling a gui function
**/
sys::import('xaraya.structures.events.observer');
class GuiEventObserver extends EventObserver
{
    public $module;
    public $type;
    public $func;
            
    public function __construct($args)
    {
        if (isset($args['module'])) $this->module = $args['module'];
        if (isset($args['type'])) $this->type = $args['type'];
        if (isset($args['func'])) $this->func = $args['func'];
    }
    
    public function notify(ixarEventSubject $subject)
    {
        // note, no try / catch here, subject notify method should handle exceptions
        return xarMod::guiFunc($this->module, $this->type, $this->func, $subject->getArgs());          
    }
}
?>