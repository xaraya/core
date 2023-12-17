<?php
/**
 * Wrapper for observers calling an api function
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/
sys::import('xaraya.structures.events.observer');

class ApiEventObserver extends EventObserver
{
    /** @var string */
    public $module;
    /** @var ?string */
    public $type;
    /** @var ?string */
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
        xarMod::apiLoad($this->module, $this->type);
        // note, no try / catch here, subject notify method should handle exceptions
        return xarMod::apiFunc($this->module, $this->type, $this->func, $subject->getArgs(), $subject->getContext());
    }
}
