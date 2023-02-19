<?php
/**
 * ItemDelete Hook Subject
 *
 * Notifies hooked observers when a module item has been deleted
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
/**
 * API type hook, observers should return array of $extrainfo
 *
 * The notify method returns an array of cumulative extrainfo from the observers
 * Called in (api|gui) function after item is deleted as...
 * $item = array('module' => $module, 'itemid' => $itemid [, 'itemtype' => $itemtype, ...]);
 * New way of calling hooks
 * xarHooks::notify('ItemDelete', $item);
 * Legacy way, supported for now, deprecated in future 
 * xarModHooks::call('item', 'delete', $itemid, $item); 
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesItemDeleteSubject extends ApiHookSubject
{
    public $subject = 'ItemDelete';

    public function __construct($args=array())
    {
        // pass args to parent constructor, it validates module and extrainfo values 
        parent::__construct($args);
        // get args populated by constuctor array('objectid', 'extrainfo')
        $args = $this->getArgs();
        // Item observers expect an objectid, if it isn't valid it's pointless notifying them, bail
        if (!isset($args['objectid']) || !is_numeric($args['objectid']))
            throw new BadParameterException('objectid');
        // From this point on, any observers notified can safely assume arguments are valid
        // API and GUI observers will be passed $this->getArgs()
        // Class observers can obtain the same args from $subject->getArgs() or
        // just retrieve extrainfo from $subject->getExtrainfo() 
    } 

}
