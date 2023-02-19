<?php
/**
 * ModRemove System Event Subject
 * Notifies observers when a module is removed (via xarMod::apiFunc('modules','admin','remove')
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
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