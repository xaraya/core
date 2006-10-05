<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Class to handle hidden property
 */
class HiddenProperty extends DataProperty
{
    public $id         = 18;
    public $name       = 'hidden';
    public $desc       = 'Hidden';
    public $reqmodules = array('base');

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'hidden';
        $this->filepath   = 'modules/base/xarproperties';
    }

    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }
}
?>
