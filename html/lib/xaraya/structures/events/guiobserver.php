<?php
/**
 * Wrapper for observers calling a gui function
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/
sys::import('xaraya.structures.events.observer');

class GuiEventObserver extends EventObserver
{
    public $module;
    public $type;
    public $func;
            
    public function __construct(array $args = [])
    {
        if (isset($args['module'])) $this->module = $args['module'];
        if (isset($args['type'])) $this->type = $args['type'];
        if (isset($args['func'])) $this->func = $args['func'];
    }
    
    public function notify(ixarEventSubject $subject)
    {
        // function was already imported in events fileLoad, but that doesn't mean the module was loaded
        xarMod::load($this->module, $this->type);
        // note, no try / catch here, subject notify method should handle exceptions
        return xarMod::guiFunc($this->module, $this->type, $this->func, $subject->getArgs());          
    }
}
