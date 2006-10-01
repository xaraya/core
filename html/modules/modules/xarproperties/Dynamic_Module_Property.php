<?php
/**
 * Dynamic Data Module Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * Dynamic Data Module Property
 * @author mikespub
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Handle the module property
 *
 * @package dynamicdata
 */
class Dynamic_Module_Property extends Dynamic_Select_Property
{
    public $id         = 19;
    public $name       = 'module';
    public $desc       = 'Module';
    public $reqmodules = array('modules');

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/modules/xarproperties';
    }

    function getOptions()
    {
        if (count($this->options) == 0) {
            // TODO: wasnt here an $args earlier? where did this go?
            $modlist = xarModAPIFunc('modules', 'admin', 'getlist');
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo['regid'], 'name' => $modinfo['displayname']);
            }
        }
        return $this->options;
    }
}

?>
