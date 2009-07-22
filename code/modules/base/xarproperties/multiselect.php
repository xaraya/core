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
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the multiselect property
 */
class MultiSelectProperty extends SelectProperty
{
    public $id         = 39;
    public $name       = 'multiselect';
    public $desc       = 'Multiselect';

    public $validation_single = false;
    public $validation_allowempty = false;
    public $validation_single_invalid; // CHECKME: is this a validation or something else?
    public $validation_allowempty_invalid;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template =  'multiselect';
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        $value = $this->getSerializedValue($value);
        $validlist = array();
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist,$option['id']);
        }
        foreach ($value as $val) {
            if (!in_array($val,$validlist)) {
                $this->invalid = xarML('selection: #(1)', $this->name);
                $this->value = null;
                return false;
            }
        }
        $this->value = serialize($value);
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (!empty($data['single'])) $this->validation_single = $data['single'];
        if (!empty($data['allowempty'])) $this->validation_allowempty = $data['allowempty'];
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = $this->getSerializedValue($data['value']);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        $data['value'] = $this->getSerializedValue($data['value']);
        if (!isset($data['options'])) $data['options'] = $this->getOptions();

        return parent::showOutput($data);
    }

    public function getSerializedValue($value)
    {
        if (empty($value)) {
            return array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
            return $value;
        }
    }
}
?>
