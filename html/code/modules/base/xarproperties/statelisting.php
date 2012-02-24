<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author John Cox
 */
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle the StateList property
 *
 * Show a dropdown of US states
 */
class StateListProperty extends SelectProperty
{
    public $id         = 43;
    public $name       = 'statelisting';
    public $desc       = 'State Dropdown';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'statelisting';
    }   
}

?>