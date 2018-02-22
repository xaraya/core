<?php
/**
 * Include the base class
 */
sys::import('modules.dynamicdata.class.properties.base');
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
 * This property displays a text area
 */

class TextAreaProperty extends DataProperty
{
    public $id         = 3;
    public $name       = 'textarea';
    public $desc       = 'Small Text Area';
    public $reqmodules = array('base');

    public $display_rows    = 0;
    public $display_columns = 0;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'textarea';
        $this->filepath   = 'modules/base/xarproperties';

        // Add in the alias information
        if (!empty($this->args)) {
            $this->display_rows = $this->args['rows'];
        }
    }
/**
 
 * @return array   array of provided elements
 */
    function aliases()
    {
        $a1['id']   = 4;
        $a1['name'] = 'textarea_medium';
        $a1['desc'] = 'Medium Text Area';
        $a1['args'] = array('rows' => 8);
        $a1['reqmodules'] = array('base');

        $a2['id']   = 5;
        $a2['name'] = 'textarea_large';
        $a2['desc'] = 'Large Text Area';
        $a2['args'] = array('rows' => 20);
        $a2['reqmodules'] = array('base');

        return array($a1, $a2);
    }
/**
 * Display a textarea for input
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
    public function showInput(Array $data = array())
    {
        // TODO: the way the template is organized now, this only works when an id is set.
        $data['value'] = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);
        if(empty($data['rows'])) $data['rows'] = $this->display_rows;
        if(empty($data['cols'])) $data['cols'] = $this->display_columns;

        return parent::showInput($data);
    }
}

?>