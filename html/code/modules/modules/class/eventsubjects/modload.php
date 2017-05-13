<?php
/**
 * ModApiLoad System Event Subject
 * Notifies observers when a module is loaded (via xarMod::load)
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
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