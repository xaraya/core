<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle radio buttons property
 */
class RadioButtonsProperty extends SelectProperty
{
    public $id         = 34;
    public $name       = 'radio';
    public $desc       = 'Radio Buttons';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'radio';
    }

    public function showInput(Array $data = array())
    {
        if (!empty($data['checked'])) $data['value'] = $data['checked'];
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        $this->template  = 'dropdown';
        return parent::showOutput($data);
    }
}
?>