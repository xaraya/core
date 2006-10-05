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
sys::import('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * Handle radio buttons property
 */
class RadioButtonsProperty extends SelectProperty
{
    public $id         = 34;
    public $name       = 'radio';
    public $desc       = 'Radio Buttons';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'radio';

    }

    function showOutput($data = array())
    {
        $this->template  = 'dropdown';
        return parent::showOutput($data);
    }
}
?>
