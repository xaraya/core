<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 * @link http://xaraya.com/index.php/release/1.html
 */

/* include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle module property
 * @author mikespub
 */
class ModuleProperty extends SelectProperty
{
    public $id         = 19;
    public $name       = 'module';
    public $desc       = 'Module';
    public $reqmodules = array('modules');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/modules/xarproperties';
    }

    function getOptions()
    {
        if (count($this->options) == 0) {
            if ($this->validation == 'systemid') {
                $key = 'systemid';
            } else {
                $key = 'regid';
            }
            // TODO: wasnt here an $args earlier? where did this go?
            $modlist = xarModAPIFunc('modules', 'admin', 'getlist');
            foreach ($modlist as $modinfo) {
                $this->options[] = array('id' => $modinfo[$key], 'name' => $modinfo['displayname']);
            }
        }
        return $this->options;
    }

    /**
     * Get Option
     * @todo finish this once we're able to get modinfo by systemid
     */
    /*function getOption($check = false)
    {
        debug($this);
        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }
    }*/
}
?>
