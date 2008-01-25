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
/* include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle checkbox mask property
 */
class CheckboxMaskProperty extends SelectProperty
{
    public $id         = 1114;
    public $name       = 'checkboxmask';
    public $desc       = 'Checkbox Mask';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template =  'checkboxmask';
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if(is_array($value)) {
            $this->value = maskImplode($value);
        } else {
            $this->value = $value;
        }

        return true;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        else $this->value = $data['value'];

/*        if (!is_array($data['value']) && is_string($data['value'])) {
            $data['value'] = maskExplode($data['value']);
        }

        if (!isset($data['options']) || count($data['options']) == 0) {
            $this->getOptions();
            $options = array();
            foreach($this->options as $key => $option) {
                $option['checked'] = in_array($option['id'], $data['value']);
                $data['options'][$key] = $option;
            }
        }
*/
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        if (!is_array($value)) $value = maskExplode($value);

        $this->getOptions();
        $numOptionsSelected = 0;
        $options = array();
        foreach($this->options as $key => $option)
        {
            $option['checked'] = in_array($option['id'], $value);
            $options[$key] = $option;
            if ($option['checked']) {
                $numOptionsSelected++;
            }
        }

        $data['options'] = $options;
        $data['numOptionsSelected'] = $numOptionsSelected;

        return parent::showOutput($data);
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        foreach ($options as $key => $option) {
            $option['checked'] = in_array($option['id'],$this->value);
            $options[$key] = $option;
        }
        return $options;
    }

}

function maskImplode($anArray)
{
    $output = '';
    if(is_array($anArray)) {
        foreach($anArray as $entry) {
            $output .= $entry;
        }
    }
    return $output;
}

function maskExplode($aString)
{
    return explode(',', substr(chunk_split($aString, 1, ','), 0, -1));
}
?>
