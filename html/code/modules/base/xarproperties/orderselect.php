<?php
/**
 * Include the base class
 */
sys::import("modules.base.xarproperties.multiselect");
/**
 * OrderSelect Property
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 * @author Dracos <dracos@xaraya.com>
 */

/**
 * This property displays a multiselect box with the contents ordered alphabetically
 * 
 */
class OrderSelectProperty extends MultiSelectProperty
{
    public $id   = 50;
    public $name = 'orderselect';
    public $desc = 'Order Select';

    public $initialization_order        = null;

    public $order = null;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'orderselect';
    }
/**
 * Get the value of a checkbox from a web page<br/>
 * 
 * @param  string value The value of the input
 * @return bool   This method passes the value gotten to the validateOrder method and returns its output.Returns true if the function has been successfully completed. Returns false if the function completion has failed.
 */
    public function checkInput($name = '', $value = null)
    {
        if (parent::checkInput($name, $value)) return false;
        list($found, $order) = $this->fetchValue($name . '_order');
        if (!$found) return false;
        return $this->validateOrder($order);

    }
    
    /**
     * Validates the order of values
     * 
     * @param string $order Order of the values
     * @return boolean Returns true on success, false on failure
     */
    function validateOrder($order = null)
    {
        if (!isset($order)) $order = $this->order;
        $options = array_keys($this->getOptions());

        $tmp = array();
        if (empty($order) || strstr($order, ';') === false) {
            foreach ($options as $k => $v) {
                $tmp[] = $v['id'];
            }
        } else {
            $tmp = explode(';', $order);
        }

        if(count(array_diff($options, $tmp)) != 0) {
            $this->invalid = xarML('incorrect order value: #(1) for #(2)', implode(';', $tmp), $this->name);
            $this->order = null;
            return false;
        }
        $this->order = implode(';', $tmp);
        return true;
    }
/**
 * Display a options for input
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
    public function showInput(Array $data = array())
    {
        if (empty($data['options'])) $data['options'] = $this->getOptions();

        if (empty($data['order']) || strstr($data['order'], ';') === false) {
            $data['order'] = '';
            foreach ($data['options'] as $option) {
                if (is_array($option) && isset($option['id'])) $data['order'] .= $option['id'] . ';';
                else $data['order'] .= $option . ';';
            }
        } else {
            $tmpval = explode(';', $data['order']);
            $tmpopts = array();
            foreach($tmpval as $v) {
                foreach($data['options'] as $k) {
                    if($k['id'] == $v) {
                        $tmpopts[] = $k;
                        continue;
                    }
                }
            }
            $data['options'] = $tmpopts;
        }
        return parent::showInput($data);
    }
/**
 * Display options for output
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for output on a web page
 */	
    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        if (!isset($data['options'])) $data['options'] = $this->options;

        if (empty($data['order']) || strstr($data['order'], ';') === false) {
            $data['order'] = '';
            foreach ($data['options'] as $option) {
                if (is_array($option) && isset($option['id'])) $data['order'] .= $option['id'] . ';';
                else $data['order'] .= $option . ';';
            }
        } else {
            $tmpval = explode(';', $data['order']);
            $tmpopts = array();
            foreach($tmpval as $v) {
                foreach($data['options'] as $k) {
                    if($k['id'] == $v) {
                        $tmpopts[] = $k;
                        continue;
                    }
                }
            }
            $data['options'] = $tmpopts;
        }
        return parent::showOutput($data);
    }
}