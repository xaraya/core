<?php
/**
 * OrderSelect Property
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
sys::import("modules.base.xarproperties.multiselect");
/**
 * handle the orderselect property
 * @author Dracos <dracos@xaraya.com>
 * @package dynamicdata
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

    public function checkInput($name = '', $value = null)
    {
        if (parent::checkInput($name, $value)) return false;
        list($found, $order) = $this->fetchValue($name . '_order');
        if (!$found) return false;
        return $this->validateOrder($order);

    }
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
?>