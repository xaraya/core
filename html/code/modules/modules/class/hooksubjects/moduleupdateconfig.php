<?php
/**
 * ModuleUpdateconfig Hook Subject
 *
 * Handles updateconfig hook observers (these typically return array of $extrainfo)
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
/**
 *
 * The notify method returns an array of cumulative extrainfo from the observers
 * Called in (api|gui) function after item is created as...
 * $item = array('module' => $module [, 'itemtype' => $itemtype]);
 * New way of calling hooks
 * xarHooks::notify('ModuleUpdateconfig', $item);
 * Legacy way, supported for now, deprecated in future 
 * xarModCallHooks('module', 'updateconfig', $module, $item); 
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleUpdateconfigSubject extends ApiHookSubject
{
    public $subject = 'ModuleUpdateconfig';
    // methods inherited from parent

    public function __construct($args=array())
    {
        // pass args to parent constructor, it validates module and extrainfo values 
        parent::__construct($args);
        // get args populated by constuctor array('objectid', 'extrainfo')
        $args = $this->getArgs();
        // Legacy Module observers expect an objectid with the name of the module 
        if (!isset($args['objectid'])) {
            // when called as xarHooks::notify() objectid will be empty
            // we instead get it from the module name in $args['extrainfo']
            $args['objectid'] = $args['extrainfo']['module'];
            // update args        
            $this->setArgs($args);    
        }        
        if (empty($args['objectid']) || !is_string($args['objectid']))
            throw new BadParameterException('objectid');

        // From this point on, any observers notified can safely assume arguments are valid
        // API and GUI observers will be passed $this->getArgs()
        // Class observers can obtain the same args from $subject->getArgs() or
        // just retrieve extrainfo from $subject->getExtrainfo() 
    } 
}
?>