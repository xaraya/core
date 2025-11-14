<?php

/**
 * ModAcativate System Event Subject
 * Notifies observers when a module is activated (via xar::mod()->apiFunc('modules','admin','activate')
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
class ModulesModActivateSubject extends EventSubject implements ixarEventSubject
{
    public $subject = 'ModActivate';
    /*
     * Constructor
     *
     * @param string $modName name of activated module
    **/
    public function __construct($modName, $xar = null)
    {
        parent::__construct($modName, $xar);
    }
}
