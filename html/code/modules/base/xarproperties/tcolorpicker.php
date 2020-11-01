<?php
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
 *  This property displays a Tigra color picker
 */
class TColorPickerProperty extends DataProperty
{
    public $id         = 44;
    public $name       = 'tcolorpicker';
    public $desc       = 'Tigra Color Picker';
    public $reqmodules = array('base');

    public $size      = 10;
    public $maxlength = 7;
    public $min       = 7;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'tcolorpicker';
        $this->filepath = 'modules/base/xarproperties';
    }
	/**
 * Validate the value of a color format
 *  
 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
 */

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            if (strlen($value) > $this->maxlength || !preg_match('/^\#(([a-f0-9]{3})|([a-f0-9]{6}))$/i', $value)) {
                $this->invalid = xarML('color must be in the format "#RRGGBB" or "#RGB"');
                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
                $this->value = null;
                return false;
            }
        }
        return true;
    }
/**
 * Display a color picker for input
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
    public function showInput(Array $data = array())
    {
        if (empty($maxlength) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
        }

        $data['baseuri']  = xarServer::getBaseURI();
        $data['size']     = $this->size;
        $data['maxlength']= $this->maxlength;
        $data['value']    = isset($data['value']) ? xarVar::prepForDisplay($data['value']) : xarVar::prepForDisplay($this->value);

        return parent::showInput($data);
    }

}
?>