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
 *  This property implement a hidden textbox.used to hide value on web page.
 */
class HiddenProperty extends DataProperty
{
    public $id         = 18;
    public $name       = 'hidden';
    public $desc       = 'Hidden';
    public $reqmodules = array('base');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'hidden';
        $this->filepath   = 'modules/base/xarproperties';
    }
/**
 * Validate the value of a hidden field
 *
 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
 */
    public function validateValue($value = null)
    {
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name);

        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('hidden field');
            xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }
}
?>