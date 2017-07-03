<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property displays a cluster of radio buttons
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
/**
 * Display a radio button for input
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
    public function showInput(Array $data = array())
    {
        if (!empty($data['checked'])) $data['value'] = $data['checked'];
        return parent::showInput($data);
    }
/**
 * Display a radio button for output on dropdown template
 * 
 * @param  array data An array of input parameters 
 * @return string     HTML markup to display the property for output on a web page
 */
    public function showOutput(Array $data = array())
    {
        $this->template  = 'dropdown';
        return parent::showOutput($data);
    }
}
?>