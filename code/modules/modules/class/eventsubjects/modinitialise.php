<?php
/**
 * ModInitialise System Event Subject
 * Notifies observers when a module is initialised (via xarMod::apiFunc('modules','admin','initialise')
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
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